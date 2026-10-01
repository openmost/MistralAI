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
use Piwik\Plugins\MistralAI\Settings\SystemSettingsForm;

/**
 * @group MistralAI
 * @group SystemSettingsFormTest
 * @group Plugins
 */
class SystemSettingsFormTest extends TestCase
{
    /**
     * @dataProvider getSubmittedKeys
     */
    public function test_keepsSavedApiKey(string $submitted, bool $expected): void
    {
        $this->assertSame($expected, SystemSettingsForm::keepsSavedApiKey($submitted));
    }

    public function getSubmittedKeys(): array
    {
        return [
            'placeholder' => [SystemSettingsForm::API_KEY_PLACEHOLDER, true],
            'empty' => ['', true],
            'blank' => ['   ', true],
            'new key' => ['sk-new', false],
            'longer mask' => ['*******', false],
        ];
    }

    public function test_getUrl_pointsToTheSettingsPage(): void
    {
        $this->assertSame('index.php?module=MistralAI&action=settings', SystemSettingsForm::getUrl());
        $this->assertSame(
            'index.php?module=MistralAI&action=settings&idSite=3&period=day&date=yesterday',
            SystemSettingsForm::getUrl(['idSite' => 3, 'period' => 'day', 'date' => 'yesterday'])
        );
    }
}
