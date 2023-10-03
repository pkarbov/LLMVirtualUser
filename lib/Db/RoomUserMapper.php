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

use PDO;

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
     public function findRoomUsersId(int $idRoom): array {
        $qb = $this->db->getQueryBuilder();
                $qb->select('id')
                    ->from($this->getTableName())
                    ->where($qb->expr()->eq('id_room', $qb->createNamedParameter($idRoom)));
        // return $this->findEntities($qb);
        return array_merge(...$qb->execute()->fetchAll(PDO::FETCH_NUM));
    }

    /**
     * @param string $userId
     * @return array
     */
     public function findRoomUsers(int $idRoom): array {
        $qb = $this->db->getQueryBuilder();
                $qb->select('*')
                    ->from($this->getTableName())
                    ->where($qb->expr()->eq('id_room', $qb->createNamedParameter($idRoom)));
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

            ->orderBy('r.last_updated', 'desc');
        
        if (isset($request['startRoom'])) {
            $qb->setFirstResult($request['startRoom']);
        }

        if (isset($request['roomsPerPage'])) {
            $qb->setMaxResults($request['roomsPerPage']);
        }
        return $this->findEntities($qb);
    }

    /**
     * @param string $idRoom
     * @return array
     */
     public function deleteByRoomId($idRoom): int {

        $qb = $this->db->getQueryBuilder();


        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('id_room', $qb->createNamedParameter($idRoom)));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));
        return $qb->executeStatement();
    }

    /**
     * @param string $userId
     * @return array
     */
     public function deleteByUsersId($usersId): int {
        $qb = $this->db->getQueryBuilder();

        $qb->delete($this->getTableName())
            ->where($qb->expr()->in('id', $qb->createNamedParameter($usersId, IQueryBuilder::PARAM_INT_ARRAY)));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));
        return $qb->executeStatement();
    }

}
