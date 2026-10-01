<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MistralAI\Agent;

/**
 * Steps that unlock the agent mode (the assistant queries the real reports through the Matomo tools of McpServer), in
 * the order they have to be taken. Super users get a link to take each step, other users are asked to contact them.
 */
final class Recommendations
{
    public const INSTALL_MCP_SERVER = 'installMcpServer';
    public const ACTIVATE_MCP_SERVER = 'activateMcpServer';
    public const ENABLE_MCP = 'enableMcp';
    public const MCP_UNAVAILABLE = 'mcpUnavailable';
    public const ENABLE_WRITE_MODE = 'enableWriteMode';

    private const MESSAGES = [
        self::INSTALL_MCP_SERVER => ['MistralAI_RecommendInstallMcpServer', 'MistralAI_RecommendInstallMcpServerAction'],
        self::ACTIVATE_MCP_SERVER => ['MistralAI_RecommendActivateMcpServer', 'MistralAI_RecommendActivateMcpServerAction'],
        self::ENABLE_MCP => ['MistralAI_RecommendEnableMcp', 'MistralAI_RecommendEnableMcpAction'],
        self::MCP_UNAVAILABLE => ['MistralAI_AgentMcpUnavailable', ''],
        self::ENABLE_WRITE_MODE => ['MistralAI_RecommendEnableWriteMode', 'MistralAI_RecommendEnableWriteModeAction'],
    ];

    /**
     * @param array{mcp: string, canPerformActions: bool} $state mcp is a McpAgent::STATUS_* status
     * @param array<string, string|int> $urlParams idSite, period and date of the current page
     * @return list<array{id: string, message: string, action: string, url: string, askAdministrator: bool}>
     *         translation keys, the action and the url are empty when the user cannot take the step
     */
    public static function build(array $state, bool $isSuperUser, array $urlParams = []): array
    {
        $recommendations = [];
        foreach (self::getApplicableIds($state) as $id) {
            $url = $isSuperUser ? self::getUrl($id, $urlParams) : '';
            $recommendations[] = [
                'id' => $id,
                'message' => self::MESSAGES[$id][0],
                'action' => $url !== '' ? self::MESSAGES[$id][1] : '',
                'url' => $url,
                'askAdministrator' => !$isSuperUser,
            ];
        }

        return $recommendations;
    }

    /**
     * @param array{mcp: string, canPerformActions: bool} $state
     * @return list<string>
     */
    private static function getApplicableIds(array $state): array
    {
        switch ($state['mcp']) {
            case McpAgent::STATUS_NOT_INSTALLED:
                return [self::INSTALL_MCP_SERVER];
            case McpAgent::STATUS_NOT_ACTIVATED:
                return [self::ACTIVATE_MCP_SERVER];
            case McpAgent::STATUS_DISABLED:
                return [self::ENABLE_MCP];
            case McpAgent::STATUS_UNAVAILABLE:
                return [self::MCP_UNAVAILABLE];
            case McpAgent::STATUS_READY:
                return empty($state['canPerformActions']) ? [self::ENABLE_WRITE_MODE] : [];
            default:
                return [];
        }
    }

    /**
     * @param array<string, string|int> $urlParams
     */
    private static function getUrl(string $id, array $urlParams): string
    {
        switch ($id) {
            case self::ACTIVATE_MCP_SERVER:
                return self::buildUrl(['module' => 'CorePluginsAdmin', 'action' => 'plugins'], $urlParams);
            case self::INSTALL_MCP_SERVER:
                return self::buildUrl(['module' => 'Marketplace', 'action' => 'overview'], $urlParams)
                    . '#?' . http_build_query(['showPlugin' => PluginDependencies::MCP_SERVER]);
            case self::ENABLE_MCP:
            case self::ENABLE_WRITE_MODE:
                return self::buildUrl(['module' => 'CoreAdminHome', 'action' => 'generalSettings'], $urlParams)
                    . '#/' . PluginDependencies::MCP_SERVER;
            default:
                return '';
        }
    }

    /**
     * @param array<string, string> $route
     * @param array<string, string|int> $urlParams
     */
    private static function buildUrl(array $route, array $urlParams): string
    {
        $params = $route;
        foreach (['idSite', 'period', 'date'] as $name) {
            if (isset($urlParams[$name]) && (string) $urlParams[$name] !== '') {
                $params[$name] = (string) $urlParams[$name];
            }
        }

        return 'index.php?' . http_build_query($params);
    }
}
