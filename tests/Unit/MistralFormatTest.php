<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\MistralAI\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\MistralAI\Agent\MistralFormat;

/**
 * @group MistralAI
 * @group MistralFormatTest
 * @group Plugins
 */
class MistralFormatTest extends TestCase
{
    public function test_toTools_mapsTheMcpCatalogToFunctions(): void
    {
        $tools = MistralFormat::toTools([
            [
                'name' => 'matomo_api_call_create',
                'title' => 'Create',
                'description' => 'Calls a create method',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => ['method' => ['type' => 'string', 'anyOf' => [['minLength' => 1]]]],
                    'not' => ['required' => ['module']],
                    'anyOf' => [['required' => ['method']]],
                    'oneOf' => [], 'allOf' => [], 'enum' => [], 'const' => 'x',
                    'additionalProperties' => false,
                ],
                'readOnly' => false,
            ],
            ['name' => 'matomo_site_list', 'description' => null, 'inputSchema' => ['type' => 'object', 'properties' => []]],
            ['name' => '', 'description' => 'No name'],
            'not a tool',
        ]);

        $this->assertCount(2, $tools);
        $this->assertSame([
            'type' => 'function',
            'function' => [
                'name' => 'matomo_api_call_create',
                'description' => 'Calls a create method',
                'parameters' => [
                    'type' => 'object',
                    // nested schemas are kept
                    'properties' => ['method' => ['type' => 'string', 'anyOf' => [['minLength' => 1]]]],
                    'additionalProperties' => false,
                ],
            ],
        ], $tools[0]);
        $this->assertSame('', $tools[1]['function']['description']);
        $this->assertSame('{"type":"object","properties":{}}', json_encode($tools[1]['function']['parameters']));
    }

    public function test_toParametersSchema_forcesAnObjectSchema(): void
    {
        $this->assertSame('{"type":"object","properties":{}}', json_encode(MistralFormat::toParametersSchema([])));
        $this->assertSame('object', MistralFormat::toParametersSchema(['type' => 'string'])['type']);
    }

    /**
     * @dataProvider getArguments
     */
    public function test_decodeArguments($arguments, ?array $expected): void
    {
        $this->assertSame($expected, MistralFormat::decodeArguments($arguments));
    }

    public function getArguments(): array
    {
        return [
            'json string' => ['{"idSite": 1, "period": "week"}', ['idSite' => 1, 'period' => 'week']],
            'object' => [['idSite' => 1, 'nested' => ['a' => 1]], ['idSite' => 1, 'nested' => ['a' => 1]]],
            'empty string' => ['', []],
            'empty object string' => ['{}', []],
            'null' => [null, []],
            'empty array' => [[], []],
            'invalid json' => ['{"idSite": 1', null],
            'json list' => ['[1, 2]', null],
            'list' => [[1, 2], null],
            'json scalar' => ['42', null],
        ];
    }

    public function test_getToolCalls_readsTheCallsWithArgumentsAsStringOrObject(): void
    {
        $toolCalls = MistralFormat::getToolCalls([
            'role' => 'assistant',
            'content' => '',
            'tool_calls' => [
                ['id' => 'D681PevKs', 'type' => 'function', 'function' => ['name' => 'matomo_site_list', 'arguments' => '{"limit": 5}']],
                ['id' => 'a1B2c3D4e', 'function' => ['name' => 'matomo_goal_get', 'arguments' => ['idGoal' => 2]]],
                ['id' => 'b1B2c3D4e', 'function' => ['name' => 'matomo_goal_get', 'arguments' => 'not json']],
                ['id' => 'noname001', 'function' => ['arguments' => '{}']],
                'invalid',
            ],
        ]);

        $this->assertSame([
            ['id' => 'D681PevKs', 'name' => 'matomo_site_list', 'arguments' => ['limit' => 5]],
            ['id' => 'a1B2c3D4e', 'name' => 'matomo_goal_get', 'arguments' => ['idGoal' => 2]],
            ['id' => 'b1B2c3D4e', 'name' => 'matomo_goal_get', 'arguments' => null],
        ], $toolCalls);
    }

    public function test_getToolCalls_replacesTheIdsTheApiWouldReject(): void
    {
        $toolCalls = MistralFormat::getToolCalls(['tool_calls' => [
            ['id' => 'call_abc123456', 'function' => ['name' => 'a']],
            ['function' => ['name' => 'b']],
            ['id' => 'Same12345', 'function' => ['name' => 'c']],
            ['id' => 'Same12345', 'function' => ['name' => 'd']],
            ['id' => 'short', 'function' => ['name' => 'e']],
        ]]);

        $ids = array_column($toolCalls, 'id');
        $this->assertCount(5, $ids);
        $this->assertSame('Same12345', $ids[2]);
        $this->assertCount(5, array_unique($ids));
        foreach ($ids as $id) {
            $this->assertSame(1, preg_match(MistralFormat::TOOL_CALL_ID_PATTERN, $id), $id);
        }
    }

    public function test_getToolCalls_isEmpty_withoutCalls(): void
    {
        $this->assertSame([], MistralFormat::getToolCalls(['content' => 'Hi']));
        $this->assertSame([], MistralFormat::getToolCalls(['tool_calls' => null]));
    }

    /**
     * @dataProvider getContents
     */
    public function test_getText($content, string $expected): void
    {
        $this->assertSame($expected, MistralFormat::getText(['role' => 'assistant', 'content' => $content]));
    }

    public function getContents(): array
    {
        return [
            'string' => ['Hello', 'Hello'],
            'null' => [null, ''],
            'chunks' => [[['type' => 'thinking', 'thinking' => [['type' => 'text', 'text' => 'hidden']]], ['type' => 'text', 'text' => 'Hel'], ['type' => 'text', 'text' => 'lo']], 'Hello'],
            'unexpected' => [42, ''],
        ];
    }

    public function test_assistantMessage_replaysTheCallsWithStringArguments(): void
    {
        $message = MistralFormat::assistantMessage('Checking', [
            ['id' => 'D681PevKs', 'name' => 'matomo_site_list', 'arguments' => ['limit' => 5]],
            ['id' => 'a1B2c3D4e', 'name' => 'matomo_goal_get', 'arguments' => []],
            ['id' => 'b1B2c3D4e', 'name' => 'matomo_goal_get', 'arguments' => null],
        ]);

        $this->assertSame([
            'role' => 'assistant',
            'content' => 'Checking',
            'tool_calls' => [
                ['id' => 'D681PevKs', 'type' => 'function', 'function' => ['name' => 'matomo_site_list', 'arguments' => '{"limit":5}']],
                ['id' => 'a1B2c3D4e', 'type' => 'function', 'function' => ['name' => 'matomo_goal_get', 'arguments' => '{}']],
                ['id' => 'b1B2c3D4e', 'type' => 'function', 'function' => ['name' => 'matomo_goal_get', 'arguments' => '{}']],
            ],
        ], $message);
        $this->assertSame(['role' => 'assistant', 'content' => 'Done'], MistralFormat::assistantMessage('Done', []));
    }

    public function test_toolMessage_carriesTheIdAndTheNameOfTheCall(): void
    {
        $this->assertSame(
            ['role' => 'tool', 'tool_call_id' => 'D681PevKs', 'name' => 'matomo_site_list', 'content' => '{"sites":2}'],
            MistralFormat::toolMessage('D681PevKs', 'matomo_site_list', [
                'content' => [['type' => 'text', 'text' => 'ignored']],
                'structuredContent' => ['sites' => 2],
                'isError' => false,
            ])
        );
    }

    public function test_toolResultText(): void
    {
        $this->assertSame("First\n{\"type\":\"image\",\"data\":\"x\"}", MistralFormat::toolResultText([
            'content' => [['type' => 'text', 'text' => 'First'], ['type' => 'image', 'data' => 'x']],
            'structuredContent' => null,
            'isError' => false,
        ]));
        $this->assertSame('{"url":"https://example.com/é"}', MistralFormat::toolResultText([
            'content' => [],
            'structuredContent' => ['url' => 'https://example.com/é'],
            'isError' => false,
        ]));
        $this->assertSame('Error: Report not found.', MistralFormat::toolResultText([
            'content' => [['type' => 'text', 'text' => 'Report not found.']],
            'structuredContent' => null,
            'isError' => true,
        ]));
        $this->assertSame('Error: the tool failed.', MistralFormat::toolResultText(['content' => [], 'structuredContent' => null, 'isError' => true]));
    }

    public function test_toolResultText_truncatesLargeResults(): void
    {
        $text = MistralFormat::toolResultText([
            'content' => [['type' => 'text', 'text' => str_repeat('a', MistralFormat::MAX_TOOL_RESULT_CHARS + 100)]],
            'structuredContent' => null,
            'isError' => false,
        ]);

        $this->assertStringStartsWith(str_repeat('a', MistralFormat::MAX_TOOL_RESULT_CHARS) . "\n[truncated", $text);
        $this->assertLessThan(MistralFormat::MAX_TOOL_RESULT_CHARS + 100, mb_strlen($text));
    }
}
