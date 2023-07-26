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

use OCA\LLaMaVirtualUser\Db\RoomMapper;
use OCA\LLaMaVirtualUser\Db\RoomUserMapper;
use OCA\LLaMaVirtualUser\Db\RoomUser;
use OCA\LLaMaVirtualUser\Db\Room;
use OCA\LLaMaVirtualUser\Db\User;

use OC\DB\Exceptions\DbalException;
use OCP\DB\Exception;

class RoomController extends Controller {

   /**
    * @var LLaMaLogger
    */
    protected $logger;
    protected $user;
    protected $room_db;
    protected $ru_db;

    public function __construct(Logger $logger,
                                UserService $user,
                                RoomMapper $room_db,
                                RoomUserMapper $ru_db,
                                IRequest $request) {

        $this->logger = $logger;
        $this->user = $user;
        $this->ru_db = $ru_db;
        $this->room_db = $room_db;

        // $this->logger->info('RoomController::__construct');
        parent::__construct(Application::APP_ID, $request);
    }

    /*************************************************************************************************************************************************
     * Get All rooms
     *
     * @param array $values
     * @return DataResponse
     */
    public function find(array $request): DataResponse {
        // $this->logger->info(sprintf('RoomController::roomFind %s', json_encode($request, JSON_PRETTY_PRINT)));
        $rooms = [];
        $room_users = $this->ru_db->findRoomsByUserId($request);
        foreach($room_users as $room_user) {
            $room    = $this->room_db->find($room_user->getIdRoom());
            $users   = $this->ru_db->findRoomUsers($room_user->getIdRoom());
            $users   = array_map(function($room_user) { return $room_user->getIdUser(); }, $users);
            //////////////////////////
            $room->typingUsers = [];
            $room->users = $users;
            $rooms[]     = $room;
        };
        return new DataResponse($rooms);
    }

    /*************************************************************************************************************************************************
     * Create new room
     *
     * @param array $values
     * @return DataResponse
     */
    public function create(array $room): DataResponse {
        // $this->logger->info(sprintf('RoomController::roomCreate %s', json_encode($room, JSON_PRETTY_PRINT)));
        $user_ids = array();
        /////////////////////////////////////////////////////////////////////////
        // read and update users
        foreach($room['users'] as $user) {
            unset($user['_id']);
            $userDB = User::fromParams($user);
            $this->logger->info(sprintf('RoomController::roomCreate::user %s', json_encode($userDB, JSON_PRETTY_PRINT)));
            if(is_null($userDB->id)){
                $userDB = $this->user->findCreate($userDB);
            }
            array_push($user_ids, $userDB->getId());
        };
        /////////////////////////////////////////////////////////////////////////
        // prepare for room creation
        unset($room['users']);
        $roomDB = Room::fromParams($room);
        // $this->room_db->beginTransaction();
        try{
            // create room
            $roomDB = $this->room_db->insertOrUpdate($roomDB);
            // attach users to room
            $resDB = $this->ru_db->addRoomUsersId($roomDB->getId(), $user_ids);
            $this->logger->info(sprintf('RoomController::roomCreate::room::user %s', json_encode($resDB, JSON_PRETTY_PRINT)));
        }catch(DbalException $ex){
            // $this->room_db->rollBack();
            $this->logger->info(sprintf('RoomController::roomCreate::room::error %s', $ex));
        }
        // $this->room_db->commit();
        return new DataResponse($roomDB->getId());
    }

}
