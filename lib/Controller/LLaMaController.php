<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\LLaMaVirtualUser\Controller;

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\Util;

use OCA\LLaMaVirtualUser\AppInfo\Application;
use OCA\LLaMaVirtualUser\Logger\Logger;

use OCA\LLaMaVirtualUser\Service\UserService;
use OCA\LLaMaVirtualUser\Service\FileService;
use OCA\LLaMaVirtualUser\Db\User;

class LLaMaController extends Controller {

   /**
    * @var LLaMaLogger
    */
    protected $logger;
    protected $user;

    public function __construct(Logger $logger,
                                UserService $user,
                                FileService $file,
                                IInitialState $initialStateService,
                                IRequest $request) {
        $this->logger = $logger;
        $this->user   = $user;
        $this->file   = $file;
        $this->initialStateService = $initialStateService;
        $this->logger->info('LLaMaController::__construct');
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function main(): TemplateResponse {
        $this->logger->info('LLaMaController::index()');
        //////////////////////////////////////////////////////////////////////////////////
        // Check/Create folder
        try{
            $userId = $this->user->getUserId();
            $this->file->createLLaMaDirectory($userId);
        } catch(\Throwable $ex) {}

        $response = new TemplateResponse(Application::APP_ID, 'main');
        $policy   = new ContentSecurityPolicy();

        //////////////////////////////////////////////////////////////////////////////////
        // $policy->addAllowedFrameDomain('https://s-usc1a-nss-2039.firebaseio.com/');
        // $policy->addAllowedFrameDomain('https://s-usc1a-nss-2038.firebaseio.com/');
        // $policy->addAllowedImageDomain('*');
        //////////////////////////////////////////////////////////////////////////////////
        // $policy->addAllowedScriptDomain('self');
        // $policy->addAllowedScriptDomain('https://test-88585-default-rtdb.firebaseio.com/');
        // $policy->addAllowedScriptDomain('https://s-usc1a-nss-2039.firebaseio.com/');
        // $policy->addAllowedScriptDomain('https://s-usc1a-nss-2038.firebaseio.com/');
        //////////////////////////////////////////////////////////////////////////////////
        $policy->addAllowedConnectDomain('https://llama.geoid.ca/api/');
        // $policy->addAllowedConnectDomain('https://firestore.googleapis.com/google.firestore.v1.Firestore/');
        // $policy->addAllowedConnectDomain('https://firebasestorage.googleapis.com/v0/b/test-88585.appspot.com/');
        // $policy->addAllowedConnectDomain('wss://s-usc1a-nss-2039.firebaseio.com/');
        // $policy->addAllowedConnectDomain('wss://s-usc1a-nss-2038.firebaseio.com/');
        $response->setContentSecurityPolicy($policy);

        $this->logger->info(sprintf('LLaMaController::index()::CSP %s',$policy->buildPolicy()));
        $this->initChatSettings();

        return $response;
    }
    /**
     * initChatSettings
     *
     * @return void
     */
    protected function initChatSettings(): void {

        $chatConfig = [
            'current_user' => $this->user->findCreateCurrentUser(),
            'llama_user'   => $this->user->findCreateLLaMaUser(),
            'test_users'   => $this->user->findCreateTestUsers(),
        ];
        $this->initialStateService->provideInitialState('chat-config', $chatConfig);
    }
}
