<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\LLaMaVirtualUser\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\Util;

use OCA\LLaMaVirtualUser\AppInfo\Application;
use OCA\LLaMaVirtualUser\Logger\Logger;

use OCA\LLaMaVirtualUser\Service\UserService;

use OCA\LLaMaVirtualUser\Db\UserMapper;
use OCA\LLaMaVirtualUser\Db\User;

class UserController extends Controller {

   /**
    * @var LLaMaLogger
    */
    protected $logger;
    protected $user_db;

    public function __construct(Logger $logger,
                                UserMapper $user_db,
                                IRequest $request) {
        $this->logger = $logger;
        $this->user_db = $user_db;

        // $this->logger->info('UserController::__construct');
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * Get All rooms
     *
     * @param array $values
     * @return DataResponse
     */
    public function find(int $userId): DataResponse {
        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('UserController::userFind %s', $userId));
        $user    = $this->user_db->find_by_id($userId);
        /////////////////////////////////////////////////////////////////////////////////////
        // if ($userId === $this->user->getUserHashId()) {
        //     // $user->setUsername($this->user->getUser()->getDisplayName());
        //     $user->setUsername(ucfirst($this->user->getUserId()));
        //     $avatar = $this->user->getUser()->getAvatarImage(-1);
        //     $user->setImage($avatar);
        // } else if ($userId === $this->user->getLLaMaHashId()) {
        //     $avatar = $this->url->getAbsoluteURL($this->url->imagePath(Application::APP_ID, 'llama.svg'));
        //     $user->setUsername('LLaMa');
        //     $user->setAvatar($avatar);
        // }
        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('UserController::userFind %s', $avatar));
        // $this->logger->info(sprintf('UserController::find %s', json_encode($user, JSON_PRETTY_PRINT)));
        return new DataResponse($user);
    }

}
