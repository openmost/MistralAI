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
use Piwik\Plugins\MistralAI\Settings\SiteSettingsStorage;

/**
 * @group MistralAI
 * @group RecommendedModelTest
 * @group Plugins
 */
class RecommendedModelTest extends TestCase
{
    public function test_theRecommendedModel_isMinistral14b(): void
    {
        $this->assertSame('ministral-14b-latest', Config::RECOMMENDED_MODEL);
        $this->assertSame('Ministral 3 14B', Config::getModelLabel(Config::RECOMMENDED_MODEL));
        $this->assertTrue(Config::isAvailableModel(Config::RECOMMENDED_MODEL));
        $this->assertFalse(Config::isDeprecatedModel(Config::RECOMMENDED_MODEL));
    }

    public function test_theDefaultModel_followsTheRecommendation(): void
    {
        $this->assertSame(Config::LATEST_RECOMMENDED_MODEL, Config::DEFAULT_MODEL);
        $this->assertSame('ministral-14b-latest', Config::resolveModel(Config::DEFAULT_MODEL));
        $this->assertSame('ministral-14b-latest', Config::resolveModel('  '));
    }

    public function test_anExplicitModel_isKept(): void
    {
        $this->assertSame('mistral-medium-latest', Config::resolveModel('mistral-medium-latest'));
        $this->assertSame('my-fine-tuned-model', Config::resolveModel(' my-fine-tuned-model '));
    }

    public function test_theRecommendedAgentModel_isMinistral14b_andTheDefault(): void
    {
        $this->assertSame('ministral-14b-latest', Config::RECOMMENDED_AGENT_MODEL);
        $this->assertTrue(Config::isAvailableModel(Config::RECOMMENDED_AGENT_MODEL));
        $this->assertSame(Config::LATEST_RECOMMENDED_MODEL, Config::DEFAULT_AGENT_MODEL);
    }

    /**
     * @dataProvider getAgentModels
     */
    public function test_theAgentModel(string $agentModel, string $chatModel, string $expected): void
    {
        $this->assertSame($expected, Config::resolveAgentModel($agentModel, $chatModel));
    }

    public function getAgentModels(): array
    {
        return [
            'latest recommended by default' => [Config::LATEST_RECOMMENDED_MODEL, 'ministral-8b-latest', Config::RECOMMENDED_AGENT_MODEL],
            'no agent model' => ['', 'ministral-8b-latest', Config::RECOMMENDED_AGENT_MODEL],
            'same as the chat: the saved chat model is honoured' => [Config::AGENT_MODEL_SAME_AS_CHAT, 'ministral-8b-latest', 'ministral-8b-latest'],
            'same as the chat, latest recommended chat model' => [Config::AGENT_MODEL_SAME_AS_CHAT, Config::LATEST_RECOMMENDED_MODEL, Config::RECOMMENDED_MODEL],
            'same as the chat, custom chat model' => [Config::AGENT_MODEL_SAME_AS_CHAT, 'my-fine-tuned-model', 'my-fine-tuned-model'],
            'same as the chat, 3B model unreliable with the tools' => [Config::AGENT_MODEL_SAME_AS_CHAT, 'ministral-3b-latest', Config::RECOMMENDED_AGENT_MODEL],
            'a chosen agent model' => ['mistral-large-latest', 'ministral-8b-latest', 'mistral-large-latest'],
            'a chosen 3B agent model' => ['Ministral-3B-2512', 'ministral-8b-latest', Config::RECOMMENDED_AGENT_MODEL],
        ];
    }

    public function test_theModelList_isConsistent(): void
    {
        $models = Config::getAvailableModels();

        $this->assertArrayNotHasKey(Config::LATEST_RECOMMENDED_MODEL, $models);
        foreach ($models as $model => $label) {
            $this->assertNotSame('', $label, $model);
            $this->assertFalse(Config::isDeprecatedModel($model), $model);
            $this->assertSame($model, Config::resolveModel($model));
        }
        $this->assertSame(count($models), count(array_unique($models)));
    }

    /**
     * @dataProvider getLegacySiteModels
     */
    public function test_theLegacyModelOfAWebsite_isHonoured(array $storedValues, string $expectedPreset, string $expectedCustom): void
    {
        $values = SiteSettingsStorage::fromStoredValues($storedValues);

        $this->assertSame($expectedPreset, $values['modelPreset']);
        $this->assertSame($expectedCustom, $values['modelCustom']);
    }

    public function getLegacySiteModels(): array
    {
        return [
            'listed legacy model' => [['model' => ['mistral-medium-latest']], 'mistral-medium-latest', ''],
            'legacy model saved as a string' => [['model' => 'mistral-large-latest'], 'mistral-large-latest', ''],
            'unlisted legacy model kept as custom model' => [['model' => ['open-mistral-7b']], '', 'open-mistral-7b'],
            'the model settings saved since win' => [['model' => ['mistral-large-latest'], 'modelPreset' => ['ministral-8b-latest']], 'ministral-8b-latest', ''],
            'a custom model saved since wins' => [['model' => ['mistral-large-latest'], 'modelCustom' => 'custom-model'], '', 'custom-model'],
            'no model at all' => [[], '', ''],
        ];
    }
}
