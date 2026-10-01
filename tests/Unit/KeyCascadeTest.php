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
use Piwik\Plugins\MistralAI\Settings\EffectiveSettings;

/**
 * Any one key is enough: key of the website, then the general key. AI Providers has no Mistral AI provider, so the
 * credential of another provider is never used.
 *
 * @group MistralAI
 * @group KeyCascadeTest
 * @group Plugins
 */
class KeyCascadeTest extends TestCase
{
    private const SITE_KEY = 'site-key';
    private const GENERAL_KEY = 'general-key';

    /**
     * @dataProvider getCascadeCases
     */
    public function test_resolvesTheKeySource(string $siteKey, string $generalKey, string $expectedSource, string $expectedApiKey): void
    {
        $settings = $this->settings(['apiKey' => $siteKey], ['apiKey' => $generalKey]);

        $this->assertSame($expectedSource, $settings->getKeySource());
        $this->assertSame($expectedSource !== EffectiveSettings::SOURCE_NONE, $settings->isConfigured());
        $this->assertSame($expectedApiKey, $settings->getApiKey());
        $this->assertSame(Config::DEFAULT_HOST, $settings->getHost());
    }

    public function getCascadeCases(): array
    {
        return [
            'site key only' => [self::SITE_KEY, '', EffectiveSettings::SOURCE_SITE, self::SITE_KEY],
            'general key only' => ['', self::GENERAL_KEY, EffectiveSettings::SOURCE_SYSTEM, self::GENERAL_KEY],
            'both: the site key wins' => [self::SITE_KEY, self::GENERAL_KEY, EffectiveSettings::SOURCE_SITE, self::SITE_KEY],
            'none' => ['', '', EffectiveSettings::SOURCE_NONE, ''],
        ];
    }

    public function test_aBlankSiteKey_isNoKey(): void
    {
        $this->assertSame(EffectiveSettings::SOURCE_SYSTEM, EffectiveSettings::resolveKeySource('  ', true));
        $this->assertSame(EffectiveSettings::SOURCE_NONE, EffectiveSettings::resolveKeySource('', false));
    }

    public function test_aCustomGeneralHost_isEnoughWithoutKey(): void
    {
        $settings = $this->settings([], ['host' => 'https://llm.example.com/v1/chat/completions']);

        $this->assertSame(EffectiveSettings::SOURCE_SYSTEM, $settings->getKeySource());
    }

    public function test_aCustomSiteHost_doesNotReceiveTheGeneralKey(): void
    {
        $settings = $this->settings(['host' => 'https://evil.example.com/v1'], ['apiKey' => self::GENERAL_KEY]);

        $this->assertSame('', $settings->getApiKey());
    }

    public function test_theModelOfTheSite_overridesTheGeneralModel(): void
    {
        $site = $this->settings(['modelPreset' => 'mistral-large-latest'], ['model' => Config::LATEST_RECOMMENDED_MODEL]);
        $general = $this->settings([], ['model' => 'mistral-small-latest']);

        $this->assertSame('mistral-large-latest', $site->getModel());
        $this->assertSame(EffectiveSettings::SOURCE_SITE, $site->getModelSource());
        $this->assertSame('mistral-small-latest', $general->getModel());
        $this->assertSame(EffectiveSettings::SOURCE_SYSTEM, $general->getModelSource());
    }

    public function test_theLatestRecommendedModel_fallsBackToTheRecommendedMistralModel(): void
    {
        $settings = $this->settings([], ['model' => Config::LATEST_RECOMMENDED_MODEL]);
        $emptyModel = $this->settings([], ['model' => '']);

        $this->assertSame('ministral-14b-latest', $settings->getModel());
        $this->assertSame('ministral-14b-latest', $emptyModel->getModel());
    }

    public function test_theAgentModel_hasItsOwnGeneralSetting(): void
    {
        $default = $this->settings(['modelPreset' => 'ministral-8b-latest'], []);
        $chosen = $this->settings(['modelPreset' => 'ministral-8b-latest'], ['agentModel' => 'mistral-large-latest']);

        $this->assertSame('ministral-8b-latest', $default->getModel());
        $this->assertSame(Config::RECOMMENDED_AGENT_MODEL, $default->getAgentModel());
        $this->assertSame(EffectiveSettings::SOURCE_AGENT, $default->getAgentModelSource());
        $this->assertSame('mistral-large-latest', $chosen->getAgentModel());
        $this->assertSame(EffectiveSettings::SOURCE_AGENT, $chosen->getAgentModelSource());
    }

    public function test_theAgentModel_canFollowTheModelOfTheWebsite_exceptA3bModel(): void
    {
        $sameAsChat = ['agentModel' => Config::AGENT_MODEL_SAME_AS_CHAT];

        $site = $this->settings(['modelPreset' => 'ministral-8b-latest'], $sameAsChat);
        $this->assertSame('ministral-8b-latest', $site->getAgentModel());
        $this->assertSame(EffectiveSettings::SOURCE_SITE, $site->getAgentModelSource());

        $general = $this->settings([], $sameAsChat + ['model' => 'ministral-8b-latest']);
        $this->assertSame('ministral-8b-latest', $general->getAgentModel());
        $this->assertSame(EffectiveSettings::SOURCE_SYSTEM, $general->getAgentModelSource());

        $small = $this->settings(['modelCustom' => 'ministral-3b-latest'], $sameAsChat);
        $this->assertSame(Config::RECOMMENDED_AGENT_MODEL, $small->getAgentModel());
        $this->assertSame('ministral-3b-latest', $small->getModel());
    }

    public function test_theKeyResolution_takesNoAiProvidersInput(): void
    {
        $parameters = array_map(function (\ReflectionParameter $parameter) {
            return $parameter->getName();
        }, (new \ReflectionMethod(EffectiveSettings::class, 'fromValues'))->getParameters());

        $this->assertSame(['idSite', 'site', 'system'], $parameters);
    }

    public function test_thePluginNeverUsesTheCredentialOfAnotherAiProvider(): void
    {
        $root = dirname(__DIR__, 2);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        $checked = 0;
        foreach ($files as $file) {
            $path = str_replace('\\', '/', substr((string) $file, strlen($root)));
            if (substr($path, -4) !== '.php' || preg_match('#^/(tests|node_modules|vendor)/#', $path)) {
                continue;
            }
            $checked++;
            $source = (string) file_get_contents((string) $file);
            $this->assertSame(0, preg_match('/Plugins\\\\AIProviders|AIProviderService|AIConversationRequest/', $source), $path);
        }

        $this->assertGreaterThan(5, $checked);
    }

    /**
     * @param array<string, string> $site
     * @param array<string, string> $system
     */
    private function settings(array $site, array $system): EffectiveSettings
    {
        return EffectiveSettings::fromValues(1, $site, $system + [
            'host' => Config::DEFAULT_HOST,
            'apiKey' => '',
            'model' => Config::LATEST_RECOMMENDED_MODEL,
            'chatBasePrompt' => 'Chat',
            'insightBasePrompt' => 'Insight',
        ]);
    }
}
