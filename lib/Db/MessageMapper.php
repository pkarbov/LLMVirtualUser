<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\LLaMaVirtualUser\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\Entity;
use OCP\IDBConnection;

use OCA\LLaMaVirtualUser\Logger\Logger;
use OCA\LLaMaVirtualUser\Db\Message;
use OCA\LLaMaVirtualUser\Db\RoomUser;

use PDO;
/**
 * @template-extends QBMapper<Note>
 */
class MessageMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lvr_chat_room_msg', Message::class);
    }

    /**
     * @param string $userId
     * @return array
     */
     public function findRoomMessagesQ($request): array {
        $id_room = $request['roomId'];
        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('m.id',              'id')
            ->selectAlias('m.id_parent',       'id_parent')
            ->selectAlias('r.id_user',         'id_user')
            ->selectAlias('r.id_room',         'id_room')
            ->selectAlias('m.id_room_user',    'id_room_user')
            ->selectAlias('m.content',         'content')
            ->selectAlias('m.content_bin',     'content_bin')
            ->selectAlias('m.timestamp_start', 'timestamp_start')
            ->selectAlias('m.timestamp_end',   'timestamp_end')
            ->selectAlias('m.deleted',         'deleted')

            ->from($this->getTableName(), 'm')
            ->from('lvr_chat_room_user',  'r')
            ->where('m.id_room_user = r.id')
            ->andWhere($qb->expr()->eq('id_room', $qb->createNamedParameter($id_room)))

            ->groupBy(['id_room','m.id','id_user'])
            ->orderBy('m.timestamp_end', 'desc')
            ->setFirstResult(is_null($request['startMessage'])   ? 0    : $request['startMessage'])
            ->setMaxResults(is_null($request['messagesPerPage']) ? null : $request['messagesPerPage']);

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));

        return $this->findEntities($qb);
    }

    /**
     * @param string $userId
     * @return array
     */
     public function findRoomMessages($idRoom): array {
        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('m.id',              'id')

            ->from($this->getTableName(), 'm')
            ->from('lvr_chat_room_user',  'r')
            ->where('m.id_room_user = r.id')
            ->andWhere($qb->expr()->eq('id_room', $qb->createNamedParameter($idRoom)));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));

        return $this->findEntities($qb);
    }

    /**
     * @param string $userId
     * @return array
     */
     public function findRoomMessagesId($idRoom): array {
        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('m.id',              'id')

            ->from($this->getTableName(), 'm')
            ->from('lvr_chat_room_user',  'r')
            ->where('m.id_room_user = r.id')
            ->andWhere($qb->expr()->eq('id_room', $qb->createNamedParameter($idRoom)));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));

        // return $this->findEntities($qb);
        // create array of ids
        return array_merge(...$qb->execute()->fetchAll(PDO::FETCH_NUM));
    }

    /**
     * @param string $idRoom
     * @return array
     */
     public function deleteByRoomId($idRoom): int {

        $qb = $this->db->getQueryBuilder();
        $ms = $this->db->getQueryBuilder();

        $ms->selectAlias('m.id', 'id')
            ->from($this->getTableName(), 'm')
            ->from('lvr_chat_room_user',  'r')
            ->where('m.id_room_user = r.id')
            ->andWhere($qb->expr()->eq('id_room', $qb->createNamedParameter($idRoom)));

        $qb->delete($this->getTableName())
            ->where($qb->expr()->in('id', $qb->createFunction($ms->getSQL()), IQueryBuilder::PARAM_INT_ARRAY));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));
        return $qb->executeStatement();
    }

    /**
     * @param string $userId
     * @return array
     */
     public function deleteByMsgsId($msgsId): int {
        $qb = $this->db->getQueryBuilder();

        $qb->delete($this->getTableName())
            ->where($qb->expr()->in('id', $qb->createNamedParameter($msgsId, IQueryBuilder::PARAM_INT_ARRAY)));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));
        return $qb->executeStatement();
    }

    /**
     * @param string $userId
     * @return array
     */
     public function findMessage($msg_id): Message {

        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('m.id',              'id')
           ->selectAlias('m.id_parent',       'id_parent')
           ->selectAlias('r.id_user',         'id_user')
           ->selectAlias('r.id_room',         'id_room')
           ->selectAlias('m.id_room_user',    'id_room_user')
           ->selectAlias('m.content',         'content')
           ->selectAlias('m.content_bin',     'content_bin')
           ->selectAlias('m.timestamp_start', 'timestamp_start')
           ->selectAlias('m.timestamp_end',   'timestamp_end')
           ->selectAlias('m.deleted',         'deleted')

            ->from($this->getTableName(), 'm')
            ->from('lvr_chat_room_user',  'r')
            ->where('m.id_room_user = r.id')
            ->andWhere($qb->expr()->eq('m.id', $qb->createNamedParameter($msg_id)));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));

        return $this->findEntity($qb);
    }

    /**
     * @param string $userId
     * @return array
     */
     public function findNewestMessages($room_id): Message {

        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('m.id',              'id')
           ->selectAlias('m.id_parent',       'id_parent')
           ->selectAlias('r.id_user',         'id_user')
           ->selectAlias('r.id_room',         'id_room')
           ->selectAlias('m.id_room_user',    'id_room_user')
           ->selectAlias('m.content',         'content')
           ->selectAlias('m.content_bin',     'content_bin')
           ->selectAlias('m.timestamp_start', 'timestamp_start')
           ->selectAlias('m.timestamp_end',   'timestamp_end')

            ->from($this->getTableName(), 'm')
            ->from('lvr_chat_room_user',  'r')

            ->where('m.id_room_user = r.id')
            ->andWhere($qb->expr()->eq('r.id_room', $qb->createNamedParameter($room_id)))
            ->andWhere($qb->expr()->isNull('m.deleted'))

            ->orderBy('m.timestamp_end', 'desc')
            ->setMaxResults(1);

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));

        return $this->findEntity($qb);
    }

}
