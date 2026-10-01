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
use Piwik\Plugins\MistralAI\Settings\EffectiveSettings;
use Piwik\Plugins\MistralAI\Settings\SiteSettingsStorage;
use Piwik\Plugins\MistralAI\Settings\SystemSettingsForm;
use Piwik\Plugins\MistralAI\SystemSettings;
use Piwik\Settings\Storage\Backend\PluginSettingsTable;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * An empty API key keeps the saved key: only the explicit deleteApiKey parameter removes it.
 *
 * @group MistralAI
 * @group MistralAIApiKeyDeletionTest
 * @group Plugins
 */
class ApiKeyDeletionTest extends IntegrationTestCase
{
    private const SECRET_KEY = 'mistral-test-secret-0123456789';

    /** @var int */
    private $idSite;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        FakeAccess::clearAccess(true);
        $this->idSite = (int) Fixture::createWebsite('2024-01-01 00:00:00');

        StaticContainer::get('Piwik\Translation\Translator')->addDirectory(__DIR__ . '/../../lang');

        Request::processRequest('MistralAI.setSystemSettings', ['apiKey' => self::SECRET_KEY]);
    }

    public function test_deleteApiKey_removesTheSavedKey(): void
    {
        Request::processRequest('MistralAI.setSystemSettings', ['deleteApiKey' => '1']);

        $this->assertSame('', (string) (new SystemSettings())->apiKey->getValue());
        $this->assertArrayNotHasKey('apiKey', (new PluginSettingsTable('MistralAI', ''))->load());
        $this->assertSame('', (new SystemSettingsForm())->getValues()['apiKey']);
    }

    public function test_deleteApiKey_wins_overAKeySentInTheSameRequest_andKeepsTheOtherFields(): void
    {
        Request::processRequest('MistralAI.setSystemSettings', ['host' => 'https://llm.example.com/v1/chat/completions']);

        Request::processRequest('MistralAI.setSystemSettings', ['apiKey' => 'other-key', 'deleteApiKey' => '1']);

        $settings = new SystemSettings();
        $this->assertSame('', (string) $settings->apiKey->getValue());
        $this->assertSame('https://llm.example.com/v1/chat/completions', $settings->host->getValue());
    }

    /**
     * @dataProvider getKeptValues
     */
    public function test_anEmptyKey_stillKeepsTheSavedKey(array $params): void
    {
        Request::processRequest('MistralAI.setSystemSettings', $params);

        $this->assertSame(self::SECRET_KEY, (new SystemSettings())->apiKey->getValue());
    }

    public function getKeptValues(): array
    {
        return [
            'empty value' => [['apiKey' => '']],
            'placeholder' => [['apiKey' => SystemSettingsForm::API_KEY_PLACEHOLDER]],
            'delete not requested' => [['apiKey' => '', 'deleteApiKey' => '0']],
        ];
    }

    /**
     * @dataProvider getDeniedAccess
     */
    public function test_deleteApiKey_isDenied_toAdminAndViewUsers(string $access): void
    {
        if ($access === 'admin') {
            FakeAccess::clearAccess(false, [$this->idSite], [], 'admin_user');
        } else {
            FakeAccess::clearAccess(false, [], [$this->idSite], 'view_user');
        }

        try {
            Request::processRequest('MistralAI.setSystemSettings', ['deleteApiKey' => '1']);
            $this->fail('The deletion must be denied');
        } catch (\Exception $e) {
            $this->assertStringContainsString('checkUserHasSuperUserAccess', $e->getMessage());
        }

        FakeAccess::clearAccess(true);
        $this->assertSame(self::SECRET_KEY, (new SystemSettings())->apiKey->getValue());
    }

    public function getDeniedAccess(): array
    {
        return [
            'admin' => ['admin'],
            'view' => ['view'],
        ];
    }

    public function test_theKeyCascade_fallsThrough_afterTheDeletion(): void
    {
        Request::processRequest('MistralAI.setSystemSettings', ['deleteApiKey' => '1']);

        $this->assertSame(EffectiveSettings::SOURCE_NONE, EffectiveSettings::forSite($this->idSite)->getKeySource());

        Request::processRequest('MistralAI.setSiteSettings', ['idSite' => $this->idSite, 'apiKey' => 'site-key']);
        $siteSettings = EffectiveSettings::forSite($this->idSite);
        $this->assertSame(EffectiveSettings::SOURCE_SITE, $siteSettings->getKeySource());
        $this->assertSame('site-key', $siteSettings->getApiKey());
    }

    public function test_deleteApiKey_removesTheWebsiteKey_andAnEmptyValueKeepsIt(): void
    {
        Request::processRequest('MistralAI.setSiteSettings', ['idSite' => $this->idSite, 'apiKey' => 'site-key', 'modelCustom' => 'site-model']);

        Request::processRequest('MistralAI.setSiteSettings', ['idSite' => $this->idSite, 'apiKey' => '', 'modelCustom' => 'site-model']);
        $this->assertSame('site-key', SiteSettingsStorage::read($this->idSite)['apiKey']);

        Request::processRequest('MistralAI.setSiteSettings', ['idSite' => $this->idSite, 'apiKey' => SiteSettingsStorage::API_KEY_PLACEHOLDER]);
        $this->assertSame('site-key', SiteSettingsStorage::read($this->idSite)['apiKey']);

        Request::processRequest('MistralAI.setSiteSettings', ['idSite' => $this->idSite, 'deleteApiKey' => '1']);
        $stored = SiteSettingsStorage::read($this->idSite);
        $this->assertSame('', $stored['apiKey']);
        $this->assertSame('site-model', $stored['modelCustom']);

        // the website then follows the general key
        $general = EffectiveSettings::forSite($this->idSite);
        $this->assertSame(EffectiveSettings::SOURCE_SYSTEM, $general->getKeySource());
        $this->assertSame(self::SECRET_KEY, $general->getApiKey());
    }

    public function test_deleteApiKey_ofAWebsite_isAllowedToItsAdmin_andDeniedToViewUsers(): void
    {
        Request::processRequest('MistralAI.setSiteSettings', ['idSite' => $this->idSite, 'apiKey' => 'site-key']);

        FakeAccess::clearAccess(false, [], [$this->idSite], 'view_user');
        try {
            Request::processRequest('MistralAI.setSiteSettings', ['idSite' => $this->idSite, 'deleteApiKey' => '1']);
            $this->fail('The deletion must be denied');
        } catch (\Exception $e) {
            $this->assertStringContainsString('checkUserHasAdminAccess', $e->getMessage());
        }
        $this->assertSame('site-key', SiteSettingsStorage::read($this->idSite)['apiKey']);

        FakeAccess::clearAccess(false, [$this->idSite], [], 'admin_user');
        Request::processRequest('MistralAI.setSiteSettings', ['idSite' => $this->idSite, 'deleteApiKey' => '1']);
        $this->assertSame('', SiteSettingsStorage::read($this->idSite)['apiKey']);
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}
