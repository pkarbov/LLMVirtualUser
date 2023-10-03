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
use OCA\LLaMaVirtualUser\Service\UserService;
use OCA\LLaMaVirtualUser\Logger\Logger;

use OCA\LLaMaVirtualUser\Db\RoomMapper;
use OCA\LLaMaVirtualUser\Db\FileMapper;
use OCA\LLaMaVirtualUser\Db\MessageMapper;
use OCA\LLaMaVirtualUser\Db\RoomUserMapper;
use OCA\LLaMaVirtualUser\Db\ReactionMapper;

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
    protected $msg_db;
    protected $rm_db;
    protected $ru_db;
    protected $rc_db;
    protected $fl_db;
    protected $user;

    public function __construct(Logger $logger,
                                ReactionMapper $rc_db,
                                RoomUserMapper $ru_db,
                                MessageMapper $msg_db,
                                UserService $user,
                                RoomMapper $rm_db,
                                FileMapper $fl_db,                                
                                IRequest $request) {

        $this->logger = $logger;
        $this->msg_db = $msg_db;
        $this->rm_db  = $rm_db;
        $this->ru_db  = $ru_db;
        $this->rc_db  = $rc_db;
        $this->fl_db  = $fl_db;
        $this->user   = $user;

        // $this->logger->info('RoomController::__construct');
        parent::__construct(Application::APP_ID, $request);
    }

    /*************************************************************************************************************************************************
     * Delete room
     *
     * @param array $values
     * @return DataResponse
     */
    public function delete($request): DataResponse {
        $roomId = $request['roomId'];
        $roomDB = Room::fromParams([ 'id' => $roomId]);
        // $this->logger->info(sprintf('RoomController::delete %s', json_encode($request, JSON_PRETTY_PRINT)));
        // $this->logger->info(sprintf('RoomController::delete %s', json_encode($roomDB, JSON_PRETTY_PRINT)));
        $usersId = $this->ru_db->findRoomUsersId($roomId);
        $mesgsId = $this->msg_db->findRoomMessagesId($roomId);
        ///////////////////////////////////////////////////////////////////////////////////////
        $this->logger->info(sprintf('RoomController::delete::users %s', json_encode($usersId, JSON_PRETTY_PRINT)));
        // $this->logger->info(sprintf('RoomController::delete::mesgs %s', json_encode($mesgsId, JSON_PRETTY_PRINT)));
        ///////////////////////////////////////////////////////////////////////////////////////
        // $count = $this->fl_db->deleteByRoomId($roomId);
        $count = $this->fl_db->deleteByMsgsId($mesgsId);
        $this->logger->info(sprintf('RoomController::delete::files %d', $count));

        // $count = $this->rc_db->deleteByRoomId($roomId);
        $count = $this->rc_db->deleteByMsgsId($mesgsId);
        $this->logger->info(sprintf('RoomController::delete::reactions %d', $count));

        // $count = $this->msg_db->deleteByRoomId($roomId);
        $count = $this->msg_db->deleteByMsgsId($mesgsId);
        $this->logger->info(sprintf('RoomController::delete::messages %d', $count));

        // $count = $this->ru_db->deleteByRoomId($roomId);
        $count = $this->ru_db->deleteByUsersId($usersId);
        $this->logger->info(sprintf('RoomController::delete::users %d', $count));
        ///////////////////////////////////////////////////////////////////////////////////////
        // $this->msg_db->deleteMessagesByRoom($roomId);
        // $this->ru_db->deleteUsersByRoom($roomId);
        $this->rm_db->delete($roomDB);
        return new DataResponse([$roomId]);
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
            $room    = $this->rm_db->find($room_user->getIdRoom());
            $users   = $this->ru_db->findRoomUsers($room_user->getIdRoom());
            $users   = array_map(function($room_user) { return $room_user->getIdUser(); }, $users);
            ///////////////////////////////////////////////////////////////////////////////////////
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
    public function create($request): DataResponse {
        $this->logger->info(sprintf('RoomController::create %s', json_encode($request, JSON_PRETTY_PRINT)));
        $user_ids = array();
        /////////////////////////////////////////////////////////////////////////
        // read and update users
        foreach($request['users'] as $user) {
            unset($user['_id']);
            $userDB = User::fromParams($user);
            if(is_null($userDB->id)){
                $userDB = $this->user->findCreate($userDB);
            }
            array_push($user_ids, $userDB->getId());
        };
        /////////////////////////////////////////////////////////////////////////
        // prepare for room creation
        unset($request['users']);
        $roomDB = Room::fromParams($request);
        try{
            // create room
            $roomDB = $this->rm_db->insertOrUpdate($roomDB);
            $roomDB = $this->rm_db->find($roomDB->getId());
            // attach users to room
            $resDB = $this->ru_db->addRoomUsersId($roomDB->getId(), $user_ids);
        }catch(DbalException $ex){
            $this->logger->info(sprintf('RoomController::create::error %s', $ex));
        }
        return new DataResponse([$roomDB]);
    }

    /*************************************************************************************************************************************************
     * Update room timestamp
     *
     * @param request
     * @return DataResponse
     */
    public function update($request): DataResponse {
        $this->logger->info(sprintf('RoomController::update %s', json_encode($request, JSON_PRETTY_PRINT)));

        $roomDB = Room::fromParams([ 'id' => $request['roomId'], 'lastUpdated' => $request['lastUpdated']]);
        // $this->logger->info(sprintf('RoomController::update %s', json_encode($roomDB, JSON_PRETTY_PRINT)));
        $roomDB = $this->rm_db->update($roomDB);
        return new DataResponse([$roomDB]);
    }

}
