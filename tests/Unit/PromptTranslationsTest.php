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

/**
 * The default prompts and the settings keys are translated in every language of the plugin.
 *
 * @group MistralAI
 * @group MistralAIPromptTranslationsTest
 * @group Plugins
 */
class PromptTranslationsTest extends TestCase
{
    private const REQUIRED_KEYS = [
        'ChatBasePromptDefault',
        'InsightBasePromptDefault',
        'SettingsConnectionTitle',
        'SettingsPromptsTitle',
        'ResetPromptToDefault',
        'ResetPromptToDefaultHelp',
        'UseGeneralPrompt',
        'UseGeneralPromptHelp',
        'SiteSettingsPromptsIntro',
        'DeleteApiKey',
        'DeleteApiKeyConfirmTitle',
        'DeleteApiKeyConfirmText',
        'DeleteSiteApiKeyConfirmText',
        'DeleteApiKeyDone',
    ];

    /**
     * @dataProvider getLanguageFiles
     */
    public function test_theLanguageFile_isValid_withEveryKey_andNoEmDash(string $file): void
    {
        $content = (string) file_get_contents($file);
        $translations = json_decode($content, true);

        $this->assertIsArray($translations, json_last_error_msg());
        $this->assertStringNotContainsString("\u{2014}", $content);

        foreach (self::REQUIRED_KEYS as $key) {
            $this->assertArrayHasKey($key, $translations['MistralAI'], $key);
            $this->assertNotSame('', trim($translations['MistralAI'][$key]), $key);
        }
    }

    /**
     * @dataProvider getLanguageFiles
     */
    public function test_theInsightPrompt_endsWithALeadInToTheReportData(string $file): void
    {
        $prompt = json_decode((string) file_get_contents($file), true)['MistralAI']['InsightBasePromptDefault'];

        // the legacy path sends "<prompt> <report JSON>"
        $this->assertSame(1, preg_match('/[:\x{FF1A}]$/u', $prompt), $prompt);
        $this->assertStringContainsString('JSON', $prompt);
        $this->assertStringContainsString('**', $prompt);
    }

    /**
     * @dataProvider getLanguageFiles
     */
    public function test_thePrompts_keepTheStructureOfTheEnglishPrompts(string $file): void
    {
        $english = json_decode((string) file_get_contents(__DIR__ . '/../../lang/en.json'), true)['MistralAI'];
        $translations = json_decode((string) file_get_contents($file), true)['MistralAI'];

        foreach (['ChatBasePromptDefault', 'InsightBasePromptDefault'] as $key) {
            $this->assertSame(substr_count($english[$key], "\n"), substr_count($translations[$key], "\n"), $key);
            $this->assertSame(substr_count($english[$key], '**'), substr_count($translations[$key], '**'), $key);
            $this->assertStringContainsString('Matomo', $translations[$key], $key);
        }
    }

    public function getLanguageFiles(): array
    {
        $files = [];
        foreach (glob(__DIR__ . '/../../lang/*.json') as $file) {
            $files[basename($file)] = [$file];
        }

        return $files;
    }
}
