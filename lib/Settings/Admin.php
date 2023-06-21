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

use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;

use OCA\LLaMaVirtualUser\Service\APIService;
use OCA\LLaMaVirtualUser\Service\Logger;

use OCA\LLaMaVirtualUser\AppInfo\Application;

class Admin implements ISettings {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IInitialState
	 */
	private $initialStateService;
	/**
	 * @var NotionAPIService
	 */
	private $apiService;
	/**
	* @var LLaMaLogger
	*/
	protected $logger;


	public function __construct(IConfig $config,
								Logger $logger,
								APIService $apiService,
								IInitialState $initialStateService) {
		$this->config = $config;
		$this->logger = $logger;
		$this->apiService     = $apiService;
		$this->initialStateService = $initialStateService;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
	    $this->initServerSettings();
	    $this->initParameterLevel();
	    $this->initModelSettings();
	    $this->initServerModels();
	
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	/**
	 * initServerSettings
	 *
	 * @return void
	 */
	protected function initServerSettings(): void {
	
        $serverAddress   = $this->config->getAppValue(Application::APP_ID, 'server_address');
        $serverSecret    = $this->config->getAppValue(Application::APP_ID, 'server_secret');
        $serverConnected = $this->apiService->getConnectedStatus($this->config);

        $adminConfig = [
            'server_address'   => $serverAddress,
            'server_secret'    => $serverSecret,
            'server_connected' => $serverConnected,
        ];
        $this->initialStateService->provideInitialState('server-config', $adminConfig);
    }
	/**
	 * initServerModels
	 *
	 * @return void
	 */
	protected function initServerModels(): void {

        $modelConfig = $this->apiService->getServerModels($this->config);
        $this->initialStateService->provideInitialState('server-models', $modelConfig);
    }
	/**
	 * initModelSettings
	 *
	 * @return void
	 */
	protected function initModelSettings(): void {

        $modelSettings = $this->apiService->getModelSettings($this->config);
        $this->initialStateService->provideInitialState('model-settings', $modelSettings);
    }
	/**
	 * initParameterLevel
	 *
	 * @return void
	 */
	protected function initParameterLevel(): void {
	
        $parameterLevel   = $this->config->getAppValue(Application::APP_ID, 'level');
        $parameterConfig = [
            'level'   => ($parameterLevel == '' ? '0' : $parameterLevel),
        ];
        $this->initialStateService->provideInitialState('parameter-level', $parameterConfig);
    }

    public function getSection(): string {
        return 'llama-config';
    }

    public function getPriority(): int {
        return 10;
    }
}
