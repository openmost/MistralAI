<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\MistralAI\tests\Integration;

use Piwik\API\Request;
use Piwik\Container\StaticContainer;
use Piwik\FrontController;
use Piwik\Plugins\MistralAI\Config;
use Piwik\Plugins\MistralAI\Controller;
use Piwik\Plugins\MistralAI\Settings\SingleValue;
use Piwik\Plugins\MistralAI\Settings\SystemSettingsForm;
use Piwik\Plugins\MistralAI\SystemSettings;
use Piwik\Settings\Storage\Backend\PluginSettingsTable;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * General settings edited on the Mistral AI page of the System administration instead of the general settings.
 *
 * @group MistralAI
 * @group MistralAISystemSettingsPageTest
 * @group Plugins
 */
class SystemSettingsPageTest extends IntegrationTestCase
{
    private const SECRET_KEY = 'mistral-test-secret-0123456789';

    /** @var int */
    private $idSite;

    /** @var array<string, mixed> */
    private $originalGet;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        FakeAccess::clearAccess(true);
        $this->idSite = (int) Fixture::createWebsite('2024-01-01 00:00:00');

        // the test environment only loads the translations of the core plugins
        StaticContainer::get('Piwik\Translation\Translator')->addDirectory(__DIR__ . '/../../lang');

        $this->originalGet = $_GET;
    }

    public function tearDown(): void
    {
        $_GET = $this->originalGet;

        parent::tearDown();
    }

    public function test_thePage_isRenderedForTheSuperUser_withTheKeyMasked(): void
    {
        $this->saveViaApi(['apiKey' => self::SECRET_KEY]);

        $html = $this->renderPage();

        $this->assertStringContainsString('vue-entry="MistralAI.ManageSystemSettings"', $html);
        $this->assertStringContainsString(SystemSettingsForm::API_KEY_PLACEHOLDER, html_entity_decode($html));
        $this->assertStringNotContainsString(self::SECRET_KEY, $html);
    }

    /**
     * @dataProvider getDeniedAccess
     */
    public function test_thePage_isDenied_toAdminAndViewUsers(string $access): void
    {
        $this->setAccess($access);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('checkUserHasSuperUserAccess');
        (new Controller())->settings();
    }

    /**
     * @dataProvider getDeniedAccess
     */
    public function test_theSave_isDenied_toAdminAndViewUsers(string $access): void
    {
        $this->setAccess($access);

        try {
            $this->saveViaApi(['chatBasePrompt' => 'Hijacked']);
            $this->fail('The save must be denied');
        } catch (\Exception $e) {
            $this->assertStringContainsString('checkUserHasSuperUserAccess', $e->getMessage());
            $this->assertNotSame('Hijacked', (new SystemSettings())->chatBasePrompt->getValue());
        }
    }

    public function getDeniedAccess(): array
    {
        return [
            'admin' => ['admin'],
            'view' => ['view'],
        ];
    }

    public function test_theValues_roundTrip_throughTheSamePluginSettings(): void
    {
        $model = array_keys(Config::getAvailableModels())[0];

        $this->saveViaApi([
            'host' => 'https://llm.example.com/v1/chat/completions',
            'apiKey' => self::SECRET_KEY,
            'modelPreset' => $model,
            'modelCustom' => 'my-model',
            'agentModel' => 'mistral-large-latest',
            'chatBasePrompt' => 'Chat & prompt',
            'insightBasePrompt' => 'Insight prompt',
        ]);

        $settings = new SystemSettings();
        $this->assertSame('https://llm.example.com/v1/chat/completions', $settings->host->getValue());
        $this->assertSame(self::SECRET_KEY, $settings->apiKey->getValue());
        $this->assertSame($model, SingleValue::toString($settings->modelPreset->getValue()));
        $this->assertSame('my-model', $settings->getConfiguredModel());
        $this->assertSame('mistral-large-latest', $settings->getConfiguredAgentModel());
        $this->assertSame('Chat & prompt', $settings->chatBasePrompt->getValue());

        $stored = (new PluginSettingsTable('MistralAI', ''))->load();
        $this->assertSame(self::SECRET_KEY, $stored['apiKey']);
        $this->assertSame([$model], $stored['modelPreset']);
        $this->assertSame('Insight prompt', $stored['insightBasePrompt']);
        $this->assertSame('mistral-large-latest', $stored['agentModel']);

        $values = (new SystemSettingsForm())->getValues();
        $this->assertSame(SystemSettingsForm::API_KEY_PLACEHOLDER, $values['apiKey']);
        $this->assertSame($model, $values['modelPreset']);
        $this->assertSame('mistral-large-latest', $values['agentModel']);
        $this->assertSame('Chat & prompt', $values['chatBasePrompt']);
    }

    public function test_theValuesSavedFromTheGeneralSettings_areRead(): void
    {
        (new PluginSettingsTable('MistralAI', ''))->save([
            'host' => 'https://old.example.com/v1/chat/completions',
            'apiKey' => self::SECRET_KEY,
            'model' => ['my-legacy-model'],
        ]);

        $values = (new SystemSettingsForm())->getValues();

        $this->assertSame('https://old.example.com/v1/chat/completions', $values['host']);
        $this->assertSame(SystemSettingsForm::API_KEY_PLACEHOLDER, $values['apiKey']);
        $this->assertSame('my-legacy-model', $values['modelCustom']);
    }

    public function test_theDefaults_areUnchanged(): void
    {
        $values = (new SystemSettingsForm())->getValues();

        $this->assertSame(Config::DEFAULT_HOST, $values['host']);
        $this->assertSame('', $values['apiKey']);
        $this->assertSame(Config::LATEST_RECOMMENDED_MODEL, $values['modelPreset']);
        $this->assertSame(Config::DEFAULT_AGENT_MODEL, $values['agentModel']);
        $this->assertNotSame('', $values['chatBasePrompt']);
    }

    /**
     * @dataProvider getKeptKeys
     */
    public function test_theSavedKey_isKept_whenTheFieldIsLeftUntouchedOrEmpty(?string $submitted): void
    {
        $this->saveViaApi(['apiKey' => self::SECRET_KEY]);

        $this->saveViaApi(['apiKey' => $submitted, 'chatBasePrompt' => 'Other prompt']);

        $settings = new SystemSettings();
        $this->assertSame(self::SECRET_KEY, $settings->apiKey->getValue());
        $this->assertSame('Other prompt', $settings->chatBasePrompt->getValue());
    }

    public function getKeptKeys(): array
    {
        return [
            'placeholder' => [SystemSettingsForm::API_KEY_PLACEHOLDER],
            'empty' => [''],
            'left out' => [null],
        ];
    }

    public function test_aNewKey_replacesTheSavedKey(): void
    {
        $this->saveViaApi(['apiKey' => self::SECRET_KEY]);
        $this->saveViaApi(['apiKey' => 'sk-new']);

        $this->assertSame('sk-new', (new SystemSettings())->apiKey->getValue());
    }

    public function test_theValidation_isUnchanged(): void
    {
        $this->expectException(\Exception::class);

        $this->saveViaApi(['host' => '']);
    }

    /**
     * @dataProvider getNonHttpsHosts
     */
    public function test_aHostThatIsNotHttps_isRefused_withATranslatedError(string $host): void
    {
        $this->saveViaApi(['host' => 'https://kept.example.com/v1/chat/completions']);

        try {
            $this->saveViaApi(['host' => $host]);
            $this->fail('A host that is not an HTTPS URL must be refused');
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Invalid API host URL - HTTPS required', $e->getMessage());
        }

        $this->assertSame('https://kept.example.com/v1/chat/completions', (new SystemSettings())->host->getValue());
    }

    public function getNonHttpsHosts(): array
    {
        return [
            'http' => ['http://llm.example.com/v1/chat/completions'],
            'no scheme' => ['llm.example.com/v1/chat/completions'],
            'no host' => ['https:///v1/chat/completions'],
        ];
    }

    public function test_theFields_stayEditable_withoutTakeoverNotice(): void
    {
        $fields = (new SystemSettingsForm())->getFields();

        $this->assertSame(
            [
                'host', 'apiKey', 'modelPreset', 'modelCustom', 'agentModel', 'chatBasePrompt', 'insightBasePrompt',
                'dataSharingAllowed', 'maskPersonalData', 'stripUrlQueryStrings', 'excludeVisitorData',
            ],
            array_column($fields, 'name')
        );
        foreach ($fields as $field) {
            $this->assertFalse($field['disabled'], $field['name']);
        }

        // AI Providers has no Mistral AI provider: no takeover notice, no link to its settings
        $this->assertSame(1, preg_match('/<div vue-entry="MistralAI\.ManageSystemSettings"[^>]*>/', $this->renderPage(), $entry));
        $this->assertStringNotContainsString('ai-providers', $entry[0]);
        $this->assertStringNotContainsString('AIProviders', html_entity_decode($entry[0]));

        // the super user sees where the data goes before allowing the data sharing
        $this->assertStringContainsString('destination=', $entry[0]);
        $this->assertStringContainsString('api.mistral.ai', html_entity_decode($entry[0]));
    }

    public function test_theAgentModel_isInTheConnectionFields_withItsOptions(): void
    {
        $fields = array_column((new SystemSettingsForm())->getFields(), null, 'name');

        $options = array_column($fields['agentModel']['options'], 'key');
        $this->assertSame([Config::LATEST_RECOMMENDED_MODEL, Config::AGENT_MODEL_SAME_AS_CHAT], array_slice($options, 0, 2));
        $this->assertContains(Config::RECOMMENDED_AGENT_MODEL, $options);
    }

    public function test_theFields_areAbsentFromTheGeneralSettings(): void
    {
        $this->assertSame([], (new SystemSettings())->getSettingsWritableByCurrentUser());

        $general = Request::processRequest('CorePluginsAdmin.getSystemSettings', ['format' => 'original']);

        $this->assertNotContains('MistralAI', array_column($general, 'pluginName'));
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }

    private function renderPage(): string
    {
        $_GET = ['module' => 'MistralAI', 'action' => 'settings'];

        return (string) FrontController::getInstance()->fetchDispatch('MistralAI', SystemSettingsForm::ACTION);
    }

    /**
     * @param array<string, string|null> $values
     */
    private function saveViaApi(array $values): void
    {
        Request::processRequest('MistralAI.setSystemSettings', array_filter($values, static function ($value) {
            return $value !== null;
        }));
    }

    private function setAccess(string $access): void
    {
        if ($access === 'admin') {
            FakeAccess::clearAccess(false, [$this->idSite], [], 'admin_user');
        } else {
            FakeAccess::clearAccess(false, [], [$this->idSite], 'view_user');
        }
    }
}
