<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\MistralAI\Agent;

/**
 * Maps the MCP tools and their results to the function calling format of the Mistral AI chat completions API, and
 * reads the tool calls of its answers.
 */
final class MistralFormat
{
    // Mistral AI rejects the tool messages whose tool_call_id is not 9 alphanumeric characters
    public const TOOL_CALL_ID_PATTERN = '/^[a-zA-Z0-9]{9}$/';

    public const MAX_TOOL_RESULT_CHARS = 40000;

    /**
     * @param list<array<string, mixed>> $catalog tools of McpServer: name, description, inputSchema
     * @return list<array{type: string, function: array{name: string, description: string, parameters: array<string, mixed>}}>
     */
    public static function toTools(array $catalog): array
    {
        $tools = [];
        foreach ($catalog as $tool) {
            if (!is_array($tool) || !is_string($tool['name'] ?? null) || $tool['name'] === '') {
                continue;
            }
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => is_string($tool['description'] ?? null) ? $tool['description'] : '',
                    'parameters' => self::toParametersSchema(is_array($tool['inputSchema'] ?? null) ? $tool['inputSchema'] : []),
                ],
            ];
        }

        return $tools;
    }

    /**
     * The API rejects combinators at the top level of the parameters schema. They are only validation hints:
     * McpServer validates the arguments again when the tool is called.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public static function toParametersSchema(array $schema): array
    {
        unset($schema['oneOf'], $schema['anyOf'], $schema['allOf'], $schema['not'], $schema['enum'], $schema['const']);
        $schema['type'] = 'object';
        if (!isset($schema['properties']) || !is_array($schema['properties']) || $schema['properties'] === []) {
            // an empty JSON array would not be an object for the API
            $schema['properties'] = new \stdClass();
        }

        return $schema;
    }

    /**
     * Text of an assistant message, the content is a string or a list of chunks.
     *
     * @param array<string, mixed> $message
     */
    public static function getText(array $message): string
    {
        $content = $message['content'] ?? '';
        if (is_string($content)) {
            return $content;
        }
        if (!is_array($content)) {
            return '';
        }

        $parts = [];
        foreach ($content as $chunk) {
            if (is_array($chunk) && ($chunk['type'] ?? '') === 'text' && is_string($chunk['text'] ?? null)) {
                $parts[] = $chunk['text'];
            }
        }

        return implode('', $parts);
    }

    /**
     * Tool calls of an assistant message. The arguments are a JSON string or an object, null when they cannot be read.
     *
     * @param array<string, mixed> $message
     * @return list<array{id: string, name: string, arguments: array<string, mixed>|null}>
     */
    public static function getToolCalls(array $message): array
    {
        $toolCalls = [];
        $usedIds = [];
        foreach (is_array($message['tool_calls'] ?? null) ? $message['tool_calls'] : [] as $toolCall) {
            $name = is_array($toolCall) ? ($toolCall['function']['name'] ?? null) : null;
            if (!is_string($name) || $name === '') {
                continue;
            }

            $id = is_string($toolCall['id'] ?? null) ? $toolCall['id'] : '';
            if (!preg_match(self::TOOL_CALL_ID_PATTERN, $id) || isset($usedIds[$id])) {
                $id = self::generateToolCallId($usedIds);
            }
            $usedIds[$id] = true;

            $toolCalls[] = [
                'id' => $id,
                'name' => $name,
                'arguments' => self::decodeArguments($toolCall['function']['arguments'] ?? null),
            ];
        }

        return $toolCalls;
    }

    /**
     * @param mixed $arguments
     * @return array<string, mixed>|null null when the arguments are not a JSON object
     */
    public static function decodeArguments($arguments): ?array
    {
        if ($arguments === null || $arguments === '' || $arguments === []) {
            return [];
        }
        if (is_string($arguments)) {
            $arguments = json_decode($arguments, true);
        }
        if (!is_array($arguments)) {
            return null;
        }

        $decoded = [];
        foreach ($arguments as $key => $value) {
            if (!is_string($key)) {
                return null;
            }
            $decoded[$key] = $value;
        }

        return $decoded;
    }

    /**
     * Assistant message replayed in the conversation, with the tool calls the agent executes
     *
     * @param list<array{id: string, name: string, arguments: array<string, mixed>|null}> $toolCalls
     * @return array<string, mixed>
     */
    public static function assistantMessage(string $text, array $toolCalls): array
    {
        $message = ['role' => 'assistant', 'content' => $text];
        if ($toolCalls !== []) {
            $message['tool_calls'] = [];
            foreach ($toolCalls as $toolCall) {
                $message['tool_calls'][] = [
                    'id' => $toolCall['id'],
                    'type' => 'function',
                    'function' => [
                        'name' => $toolCall['name'],
                        'arguments' => (string) json_encode($toolCall['arguments'] === null || $toolCall['arguments'] === [] ? new \stdClass() : $toolCall['arguments']),
                    ],
                ];
            }
        }

        return $message;
    }

    /**
     * @param array{content: list<array<string, mixed>>, structuredContent: array<string, mixed>|null, isError: bool} $result
     * @return array{role: string, tool_call_id: string, name: string, content: string}
     */
    public static function toolMessage(string $toolCallId, string $name, array $result): array
    {
        return [
            'role' => 'tool',
            'tool_call_id' => $toolCallId,
            'name' => $name,
            'content' => self::toolResultText($result),
        ];
    }

    /**
     * The structured result when there is one, the text of the MCP content otherwise. Truncated so a large report
     * does not exceed the context of the model or the tokens per minute of the plan.
     *
     * @param array{content: list<array<string, mixed>>, structuredContent: array<string, mixed>|null, isError: bool} $result
     */
    public static function toolResultText(array $result): string
    {
        if (is_array($result['structuredContent'] ?? null)) {
            $text = (string) json_encode($result['structuredContent'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $parts = [];
            foreach (is_array($result['content'] ?? null) ? $result['content'] : [] as $block) {
                if (is_array($block) && ($block['type'] ?? '') === 'text' && is_string($block['text'] ?? null)) {
                    $parts[] = $block['text'];
                } else {
                    $parts[] = (string) json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            $text = implode("\n", $parts);
        }

        if (!empty($result['isError'])) {
            $text = 'Error: ' . ($text !== '' ? $text : 'the tool failed.');
        }

        if (mb_strlen($text) > self::MAX_TOOL_RESULT_CHARS) {
            $text = mb_substr($text, 0, self::MAX_TOOL_RESULT_CHARS) . "\n[truncated: ask for fewer rows or a narrower report]";
        }

        return $text;
    }

    /**
     * @param array<string, bool> $usedIds
     */
    private static function generateToolCallId(array $usedIds): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $id = '';
            for ($i = 0; $i < 9; $i++) {
                $id .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (isset($usedIds[$id]));

        return $id;
    }
}
