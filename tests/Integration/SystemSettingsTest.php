<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\MistralAI\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\MistralAI\Config;
use Piwik\Plugins\MistralAI\Settings\EffectiveSettings;
use Piwik\Plugins\MistralAI\Settings\SiteSettingsStorage;
use Piwik\Plugins\MistralAI\SystemSettings;
use Piwik\Settings\Storage\Factory as StorageFactory;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group MistralAI
 * @group MistralAISystemSettingsTest
 * @group Plugins
 */
class SystemSettingsTest extends IntegrationTestCase
{
    /** @var int */
    private $idSite;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        FakeAccess::clearAccess(true);
        $this->idSite = (int) Fixture::createWebsite('2024-01-01 00:00:00');
    }

    public function test_newInstalls_useTheLatestRecommendedModel(): void
    {
        $settings = new SystemSettings();

        $this->assertSame(Config::LATEST_RECOMMENDED_MODEL, $settings->getConfiguredModel());
        $this->assertSame('ministral-14b-latest', EffectiveSettings::forSite($this->idSite)->getModel());
    }

    public function test_newInstalls_useTheRecommendedAgentModel_andKeepTheChatModel(): void
    {
        $settings = new SystemSettings();

        $this->assertSame(Config::LATEST_RECOMMENDED_MODEL, $settings->getConfiguredAgentModel());
        $this->assertSame(Config::RECOMMENDED_AGENT_MODEL, EffectiveSettings::forSite($this->idSite)->getAgentModel());

        $settings->modelPreset->setValue(['ministral-8b-latest']);
        $settings->agentModel->setValue(Config::AGENT_MODEL_SAME_AS_CHAT);
        $settings->save();

        $effective = EffectiveSettings::forSite($this->idSite);
        $this->assertSame('ministral-8b-latest', $effective->getModel());
        $this->assertSame('ministral-8b-latest', $effective->getAgentModel());
    }

    public function test_theAgentModelOptions_startWithTheRecommendedOption(): void
    {
        $options = SystemSettings::getAgentModelOptions();

        $this->assertSame([Config::LATEST_RECOMMENDED_MODEL, Config::AGENT_MODEL_SAME_AS_CHAT], array_slice(array_keys($options), 0, 2));
        $this->assertArrayHasKey(Config::RECOMMENDED_AGENT_MODEL, $options);
        $this->assertArrayHasKey('retired-model', SystemSettings::getAgentModelOptions('retired-model'));
    }

    public function test_theLegacyModelSetting_isHonoured(): void
    {
        $this->saveLegacyModel('mistral-large-latest');

        $settings = new SystemSettings();

        $this->assertSame('mistral-large-latest', $settings->getConfiguredModel());
        $this->assertSame('mistral-large-latest', EffectiveSettings::forSite($this->idSite)->getModel());
    }

    public function test_anUnlistedLegacyModel_isKeptAsCustomModel(): void
    {
        $this->saveLegacyModel('open-mistral-7b');

        $settings = new SystemSettings();

        $this->assertSame('open-mistral-7b', $settings->modelCustom->getValue());
        $this->assertSame('open-mistral-7b', $settings->getConfiguredModel());
    }

    public function test_theLegacyModel_isIgnored_onceTheModelSettingsAreSaved(): void
    {
        $this->saveLegacyModel('mistral-large-latest');
        $settings = new SystemSettings();
        $settings->modelPreset->setValue(['ministral-8b-latest']);
        $settings->save();

        $this->assertSame('ministral-8b-latest', (new SystemSettings())->getConfiguredModel());
    }

    public function test_theLegacyModelOfAWebsite_isHonoured(): void
    {
        $storage = new \Piwik\Settings\Storage\Storage(
            new \Piwik\Settings\Storage\Backend\MeasurableSettingsTable($this->idSite, SiteSettingsStorage::PLUGIN_NAME)
        );
        $storage->setValue('model', ['mistral-small-latest']);
        $storage->save();

        $settings = EffectiveSettings::forSite($this->idSite);

        $this->assertSame('mistral-small-latest', $settings->getModel());
        $this->assertSame(EffectiveSettings::SOURCE_SITE, $settings->getModelSource());
    }

    /**
     * @dataProvider getStoredCascadeCases
     */
    public function test_forSite_resolvesTheKeyCascade_fromTheStoredSettings(string $siteKey, string $generalKey, string $expectedSource): void
    {
        if ($generalKey !== '') {
            $settings = new SystemSettings();
            $settings->apiKey->setValue($generalKey);
            $settings->save();
        }
        if ($siteKey !== '') {
            SiteSettingsStorage::save($this->idSite, ['apiKey' => $siteKey]);
        }

        $this->assertSame($expectedSource, EffectiveSettings::forSite($this->idSite)->getKeySource());
    }

    public function getStoredCascadeCases(): array
    {
        return [
            'site key only' => ['site-key', '', EffectiveSettings::SOURCE_SITE],
            'general key only' => ['', 'general-key', EffectiveSettings::SOURCE_SYSTEM],
            'both' => ['site-key', 'general-key', EffectiveSettings::SOURCE_SITE],
            'none' => ['', '', EffectiveSettings::SOURCE_NONE],
        ];
    }

    public function test_hasAnySiteApiKey(): void
    {
        $this->assertFalse(SiteSettingsStorage::hasAnySiteApiKey());

        SiteSettingsStorage::save($this->idSite, ['apiKey' => '']);
        $this->assertFalse(SiteSettingsStorage::hasAnySiteApiKey());

        SiteSettingsStorage::save($this->idSite, ['apiKey' => 'site-key']);
        $this->assertTrue(SiteSettingsStorage::hasAnySiteApiKey());
    }

    public function test_theConnectionFields_stayEditable(): void
    {
        $settings = new SystemSettings();

        foreach (['host', 'apiKey', 'modelPreset', 'modelCustom'] as $name) {
            $this->assertArrayNotHasKey('disabled', $settings->{$name}->configureField()->uiControlAttributes, $name);
        }
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }

    private function saveLegacyModel(string $model): void
    {
        $storage = StaticContainer::get(StorageFactory::class)->getPluginStorage('MistralAI', '');
        $storage->setValue('model', [$model]);
        $storage->save();
    }
}
