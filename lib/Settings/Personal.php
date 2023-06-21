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
use OCA\LLaMaVirtualUser\Service\APIService;

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
								APIService $apiService,
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
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$llamaUserId = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_id');
		$llamaUserName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_name');
		$searchPagesEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_pages_enabled', '0');
		$searchDatabasesEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_databases_enabled', '0');
		$linkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '0');

		// for OAuth
		$clientID = $this->config->getAppValue(Application::APP_ID, 'client_id');
		// don't expose the client secret to users
		$clientSecret = ($this->config->getAppValue(Application::APP_ID, 'client_secret') !== '');
		$usePopup = $this->config->getAppValue(Application::APP_ID, 'use_popup', '0') === '1';

		$userConfig = [
			'token' => $token ? 'dummyTokenContent' : '',
			'client_id' => $clientID,
			'client_secret' => $clientSecret,
			'use_popup' => $usePopup,
			'user_id' => $llamaUserId,
			'user_name' => $llamaUserName,
			'search_pages_enabled' => $searchPagesEnabled === '1',
			'search_databases_enabled' => $searchDatabasesEnabled === '1',
			'link_preview_enabled' => $linkPreviewEnabled === '1',
		];
		$this->initialStateService->provideInitialState('user-config', $userConfig);
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'llama-config';
	}

	public function getPriority(): int {
		return 10;
	}
}
