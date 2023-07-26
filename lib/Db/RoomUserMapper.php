<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\LLaMaVirtualUser\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\Entity;
use OCP\IDBConnection;

use OCA\LLaMaVirtualUser\Logger\Logger;
use OCA\LLaMaVirtualUser\Db\RoomUser;

use OC\DB\Exceptions\DbalException;
use OCP\DB\Exception;

/**
 * @template-extends QBMapper<Note>
 */
class RoomUserMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lvr_chat_room_user', RoomUser::class);
    }

    public static function fromRow($row) {

    }

    /**
     * @param string $userId
     * @return array
     */
    public function findRoomUsers(int $id): array {
        $qb = $this->db->getQueryBuilder();
                $qb->select('*')
                    ->from($this->getTableName())
                    ->where($qb->expr()->eq('id_room', $qb->createNamedParameter($id)));
        return $this->findEntities($qb);
    }

    /**
     * @param string $userId
     * @return array
     */
    public function findRoomUser(RoomUser $room_user): ?RoomUser {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('id_room', $qb->createNamedParameter($room_user->getIdRoom())))
           ->andWhere($qb->expr()->eq('id_user', $qb->createNamedParameter($room_user->getIdUser())));
        return $this->findEntity($qb);
    }

    /**
     * @param string $userId
     * @return array
     */
    public function addRoomUsersId(int $roomId, array $userIds): array {
        // attach users to room
        foreach($userIds as $id) {
            $room_user = RoomUser::fromParams([ 'idUser' => $id, 'idRoom' => $roomId]);
            try{
                $ruDB = $this->insert($room_user);
            }catch(DbalException $ex){
                $ruDB = $this->findRoomUser($room_user);
            }
            $result[] = $ruDB;
        };
        return $result;
    }

    /**
     * @param string $userId
     * @return array
     */
    public function findRoomsByUserId(array $request): array {
        $id_user = $request['userId'];
        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('u.id',           'id')
           ->selectAlias('u.id_user',      'id_user')
           ->selectAlias('u.id_room',      'id_room')

            ->from($this->getTableName(), 'u')
            ->from('lvr_chat_room',       'r')
            ->where('u.id_room = r.id')
            ->andWhere($qb->expr()->eq('id_user', $qb->createNamedParameter($id_user)))

            ->orderBy('r.last_updated', 'desc')
            ->setFirstResult(is_null($request['startRooms']) ? 0 : $request['startRooms'])
            ->setMaxResults($request['roomsPerPage']);

        return $this->findEntities($qb);
    }
}
