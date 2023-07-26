<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\LLaMaVirtualUser\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IURLGenerator;
use OCP\IRequest;
use OCP\Util;

use OCA\LLaMaVirtualUser\AppInfo\Application;
use OCA\LLaMaVirtualUser\Logger\Logger;

use OCA\LLaMaVirtualUser\Service\UserService;

use OCA\LLaMaVirtualUser\Db\MessageMapper;
use OCA\LLaMaVirtualUser\Db\Message;

class MessageController extends Controller {

    /**
     * @var IURLGenerator
     */
    private $url;

   /**
    * @var LLaMaLogger
    */
    protected $logger;
    protected $msg_db;

    public function __construct(Logger $logger,
                                MessageMapper $msg_db,
                                IRequest $request,
                                IURLGenerator $url) {
        $this->logger = $logger;
        $this->msg_db = $msg_db;
        $this->url = $url;

        // $this->logger->info('UserController::__construct');
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * Get All rooms
     *
     * @param array $values
     * @return DataResponse
     */
    public function last(): DataResponse {
        /////////////////////////////////////////////////////////////////////////////////////
        $this->logger->info(sprintf('MessageController::last'));
        // $user    = $this->user_db->find_by_id($userId);
        // $this->logger->info(sprintf('UserController::find %s', json_encode($user, JSON_PRETTY_PRINT)));
        return new DataResponse([]);
    }

}
