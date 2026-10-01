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
use Piwik\Plugins\MistralAI\Config;
use Piwik\Plugins\MistralAI\Settings\ModelUpgradeNotice;
use Piwik\Plugins\MistralAI\Settings\SingleValue;
use Piwik\Plugins\MistralAI\Settings\SiteSettingsStorage;

/**
 * @group MistralAI
 * @group ModelSettingsTest
 * @group Plugins
 */
class ModelSettingsTest extends TestCase
{
    public function testLatestRecommendedResolvesToTheRecommendedModel(): void
    {
        $this->assertSame(Config::RECOMMENDED_MODEL, Config::resolveModel(Config::LATEST_RECOMMENDED_MODEL));
        $this->assertSame(Config::RECOMMENDED_MODEL, Config::resolveModel(''));
        $this->assertSame('ministral-8b-latest', Config::resolveModel(' ministral-8b-latest '));
        $this->assertSame(Config::LATEST_RECOMMENDED_MODEL, Config::DEFAULT_MODEL);
    }

    public function testRecommendedModelIsListedAndNotDeprecated(): void
    {
        $this->assertArrayHasKey(Config::RECOMMENDED_MODEL, Config::getAvailableModels());
        $this->assertTrue(Config::isAvailableModel(Config::LATEST_RECOMMENDED_MODEL));

        foreach (array_keys(Config::getAvailableModels()) as $model) {
            $this->assertFalse(Config::isDeprecatedModel($model), $model);
        }
    }

    public function testRemovedModelsAreDeprecated(): void
    {
        $this->assertTrue(Config::isDeprecatedModel('open-mistral-nemo'));
        $this->assertTrue(Config::isDeprecatedModel('magistral-medium-latest'));
        $this->assertFalse(Config::isAvailableModel('open-mistral-nemo'));
    }

    public function testReadsSiteValuesSavedByTheWebsiteForm(): void
    {
        $values = SiteSettingsStorage::fromStoredValues([
            'host' => '',
            'apiKey' => 'key',
            'modelPreset' => ['mistral-small-latest'],
            'modelCustom' => '',
            'chatBasePrompt' => 'Chat',
            'model' => ['ministral-8b-latest'],
        ]);

        $this->assertSame('mistral-small-latest', $values['modelPreset']);
        $this->assertSame('', $values['modelCustom']);
        $this->assertSame('key', $values['apiKey']);
        $this->assertSame('Chat', $values['chatBasePrompt']);
        $this->assertSame('', $values['insightBasePrompt']);
    }

    public function testReadsTheLegacyModelWhenTheModelSettingsWereNeverSaved(): void
    {
        $this->assertSame('ministral-8b-latest', SiteSettingsStorage::fromStoredValues([
            'model' => ['ministral-8b-latest'],
        ])['modelPreset']);

        // a legacy model that is no longer listed is kept as a custom model
        $values = SiteSettingsStorage::fromStoredValues(['model' => ['open-mistral-nemo']]);
        $this->assertSame('', $values['modelPreset']);
        $this->assertSame('open-mistral-nemo', $values['modelCustom']);

        // an empty preset saved by the website form means the general settings apply
        $this->assertSame('', SiteSettingsStorage::fromStoredValues([
            'modelPreset' => [''],
            'model' => ['ministral-8b-latest'],
        ])['modelPreset']);
    }

    public function testUnwrap(): void
    {
        $this->assertSame('a', SingleValue::toString(['a']));
        $this->assertSame('a', SingleValue::toString(' a '));
        $this->assertSame('', SingleValue::toString([]));
        $this->assertSame('', SingleValue::toString(null));
    }

    public function testClassifiesModelErrors(): void
    {
        $this->assertSame(ModelUpgradeNotice::REASON_UNAVAILABLE, ModelUpgradeNotice::classifyApiError(
            400,
            '{"object":"error","message":"Invalid model: magistral-medium-2509","type":"invalid_model","param":null,"code":"1500"}'
        ));
        $this->assertSame(ModelUpgradeNotice::REASON_NOT_IN_PLAN, ModelUpgradeNotice::classifyApiError(
            403,
            '{"object":"error","message":"This model is not available in your subscription tier","type":"tier_not_allowed","param":null,"code":"1910"}'
        ));

        $rateLimited = '{"object":"error","message":"Rate limit exceeded","type":"rate_limited","param":null,"code":"1300"}';
        $this->assertSame(ModelUpgradeNotice::REASON_NOT_IN_PLAN, ModelUpgradeNotice::classifyApiError(
            429,
            $rateLimited,
            ['x-ratelimit-limit-req-minute' => '0']
        ));
        $this->assertSame(ModelUpgradeNotice::REASON_RATE_LIMITED, ModelUpgradeNotice::classifyApiError(
            429,
            $rateLimited,
            ['x-ratelimit-limit-req-minute' => '60']
        ));

        $this->assertNull(ModelUpgradeNotice::classifyApiError(401, '{"message":"Unauthorized","request_id":"x"}'));
        $this->assertNull(ModelUpgradeNotice::classifyApiError(500, '<html>Bad gateway</html>'));
    }
}
