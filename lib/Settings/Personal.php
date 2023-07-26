<?php
declare(strict_types=1);

/**
 *
 * Nextcloud - LLaMa
 *
 * @copyright Copyright (c) 2023 Pavlo Karbovnyk <pkarbovn@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\LLaMaVirtualUser\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

use OCA\LLaMaVirtualUser\AppInfo\Application;
use OCA\LLaMaVirtualUser\Service\SettingsService;

class Personal implements ISettings {

    /**
     * @var IConfig
     */
    private $config;
    /**
     * @var IInitialState
     */
    private $initialStateService;
    /**
     * @var string|null
     */
    private $userId;
    /**
     * @var llamaAPIService
     */
    private $apiService;

    public function __construct(IConfig $config,
                                IInitialState $initialStateService,
                                SettingsService $apiService,
                                ?string $userId) {
        $this->config = $config;
        $this->initialStateService = $initialStateService;
        $this->userId = $userId;
        $this->apiService = $apiService;
    }

    /**
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse {
        $this->initServerSettings();
        $this->initRequestSettings();

        return new TemplateResponse(Application::APP_ID, 'personalSettings');
    }

    /**
     * initServerSettings
     *
     * @return void
     */
    protected function initServerSettings(): void {

        $serverAddress   = $this->config->getAppValue(Application::APP_ID, 'server_address');
        $serverSecret    = $this->config->getAppValue(Application::APP_ID, 'server_secret');
        $serverConnected = $this->apiService->checkServerConnection($this->config);
        $engineConnected = $this->apiService->checkEngineConnection($this->config);

        $adminConfig = [
            'server_address'   => $serverAddress,
            'server_secret'    => $serverSecret,
            'server_connected' => $serverConnected,
            'engine_connected' => $engineConnected,
        ];
        $this->initialStateService->provideInitialState('server-config', $adminConfig);
    }

    /**
     * initRequestSettings
     *
     * @return void
     */
    protected function initRequestSettings(): void {

        $requestSettings = $this->apiService->getRequestSettings($this->config);
        $this->initialStateService->provideInitialState('request-settings', $requestSettings);
    }

    public function getSection(): string {
        return 'llama-config';
    }

    public function getPriority(): int {
        return 10;
    }

}
