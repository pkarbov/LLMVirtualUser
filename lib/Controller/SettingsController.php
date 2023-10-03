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

namespace OCA\LLaMaVirtualUser\Controller;

use OCP\IL10N;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IServerContainer;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\AppFramework\Http\RedirectResponse;

use OCA\LLaMaVirtualUser\Service\SettingsService;
use OCA\LLaMaVirtualUser\AppInfo\Application;
use OCA\LLaMaVirtualUser\Logger\Logger;

// TODO: Rewrite configuration
class SettingsController extends Controller {

    /**
     * @var IConfig
     */
    private $config;
    /**
     * @var IURLGenerator
     */
    private $urlGenerator;
    /**
     * @var IL10N
     */
    private $l;
    /**
     * @var NotionAPIService
     */
    private $apiService;
    /**
     * @var string|null
     */
    private $userId;
    /**
     * @var IInitialState
     */
    private $initialStateService;
    /**
    * @var LLaMaLogger
    */
    protected $logger;


    public function __construct(string $appName,
                                IL10N $l,
                                IRequest $request,
                                IConfig $config,
                                IURLGenerator $urlGenerator,
                                IInitialState $initialStateService,
                                Logger $logger,
                                SettingsService $apiService,
                                ?string $userId) {
        parent::__construct($appName, $request);

        $this->l = $l;
        $this->config = $config;
        $this->userId = $userId;
        $this->logger = $logger;
        $this->apiService = $apiService;
        $this->urlGenerator = $urlGenerator;
        $this->initialStateService = $initialStateService;

        $this->logger->info('SettingsController::__construct');
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        $this->logger->info('SettingsController::index()');
        return new TemplateResponse(Application::APP_ID, 'main');
    }

    /**
     * set config values
     * @NoAdminRequired
     *
     * @param array $values
     * @return DataResponse
     */
    public function setConfig(array $values): DataResponse {
        // $this->logger->info('SettingsController::setConfig');
        $result = [];
        return new DataResponse($result);
    }

    /**
     * set server values
     *
     * @param array $values
     * @return DataResponse
     */
    public function setServerAddress(array $values): DataResponse {
        // $this->logger->info('SettingsController::setServerAddress');
        foreach ($values as $key => $value) {
            $this->config->setAppValue(Application::APP_ID, $key, $value);
            // $this->logger->info(sprintf('setServerAddress(): [%s => %s] ',$key, $value));
        }
        return new DataResponse();// $this->apiService->checkConnected($this->config);
    }

    /**
     * set config level values
     *
     * @param array $values
     * @return DataResponse
     */
    public function setParameterLevel(array $values): DataResponse {
        // $this->logger->info('SettingsController::setParameterLevel');
        foreach ($values as $key => $value) {
            $this->config->setAppValue(Application::APP_ID, $key, $value);
            // $this->logger->info(sprintf('setParameterLevel(): [%s => %s] ',$key, $value));
        }
        return new DataResponse();
    }

    /**
     * activate model
     *
     * @param array $values
     * @return DataResponse
     */
    public function setActiveModel(array $values): DataResponse {
        // $this->logger->info(sprintf('SettingsController::setActiveModel: %s', json_encode($values, JSON_PRETTY_PRINT)));

        $model = $values['model'];
        $param = $values['param'];

        return $this->apiService->activateModel($this->config, $model, $param);
    }

    /**
     * get server connection stutas
     *
     * @param array $values
     * @return DataResponse
     */
    public function getServerStatus(): DataResponse {
        // $this->logger->info('SettingsController::getServerStatus');
        $status = $this->apiService->checkServerConnection($this->config);
        return new DataResponse(['connected' => $status]);
    }

    /**
     * get server connection stutas
     *
     * @param array $values
     * @return DataResponse
     */
    public function getEngineStatus(): DataResponse {
        // $this->logger->info('SettingsController::getEngineStatus');
        $status = $this->apiService->checkEngineConnection($this->config);
        return new DataResponse(['connected' => $status]);
    }

    /**
     * get server models
     *
     * @param array $values
     * @return DataResponse
     */
    public function getServerModels(): DataResponse {
        // $this->logger->info('SettingsController::getServerModels');
        $serverModels = $this->apiService->getServerModels($this->config);
        return new DataResponse($serverModels);
    }

    /**
     * get model settings
     *
     * @param array $values
     * @return DataResponse
     */
    public function getModelSettings(): DataResponse {
        // $this->logger->info('SettingsController::getModelSettings');
        $modelSettings = $this->apiService->getModelSettings($this->config);
        return new DataResponse($modelSettings);
    }

}
