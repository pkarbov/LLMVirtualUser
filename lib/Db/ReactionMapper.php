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
use OCA\LLaMaVirtualUser\Db\Reaction;

/**
 * @template-extends QBMapper<Note>
 */
class ReactionMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lvr_msg_reaction', Reaction::class);
    }

    /**
     * @param string $userId
     * @return array
     */
    public function deleteReaction($reaction): Int {

        $qb = $this->db->getQueryBuilder();

        $qb->select('id')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id_msg', $qb->createNamedParameter($reaction->getIdMsg())))
            ->andWhere($qb->expr()->eq('id_user', $qb->createNamedParameter($reaction->getIdUser())))
            ->andWhere($qb->expr()->eq('reaction', $qb->createNamedParameter($reaction->getReaction())))
            ->orderBy('seen', 'desc')
            ->setMaxResults(1);
        try {
            $res = $this->findEntity($qb);
            $this->delete($res);
            return $res->getId();
        } catch(\Throwable $ex){
            return -1;
        }
    }

    /**
     * @param string $userId
     * @return array
     */
     public function findReactions($msg_id): array {

        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('r.id',       'id')
            ->selectAlias('r.id_msg',  'id_msg')
            ->selectAlias('r.id_user', 'id_user')
            ->selectAlias('r.seen',    'seen')
            ->selectAlias('r.reaction','reaction')

            ->from($this->getTableName(), 'r')
            ->where($qb->expr()->eq('r.id_msg', $qb->createNamedParameter($msg_id)))
            ->andWhere($qb->expr()->isNotNull('reaction'));

        return $this->findEntities($qb);
    }

    /**
     * @param string $userId
     * @return array
     */
     public function findSeens($msg_id): array {

        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('r.id',       'id')
            ->selectAlias('r.id_msg',  'id_msg')
            ->selectAlias('r.id_user', 'id_user')
            ->selectAlias('r.seen',    'seen')

            ->from($this->getTableName(), 'r')
            ->where($qb->expr()->eq('r.id_msg', $qb->createNamedParameter($msg_id)))
            ->andWhere($qb->expr()->isNull('reaction'));

        return $this->findEntities($qb);
    }

    /**
     * @param string $userId
     * @return array
     */
    public function findSeen($msg_id, $usr_id): Object {

        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('r.id',       'id')
            ->selectAlias('r.id_msg',  'id_msg')
            ->selectAlias('r.id_user', 'id_user')
            ->selectAlias('r.seen',    'seen')

            ->from($this->getTableName(), 'r')
            ->where($qb->expr()->eq('r.id_msg', $qb->createNamedParameter($msg_id)))
            ->andWhere($qb->expr()->eq('r.id_user', $qb->createNamedParameter($usr_id)))
            ->andWhere($qb->expr()->isNull('reaction'));

        return $this->findEntity($qb);
    }

    /**
     * @param string $userId
     * @return array
     */
    public function deleteByRoomId($roomId): int {
        $qb = $this->db->getQueryBuilder();
        $ms = $this->db->getQueryBuilder();

        $ms->selectAlias('m.id', 'id')
            ->from('lvr_chat_room_msg', 'm')
            ->from('lvr_chat_room_user','r')
            ->where('m.id_room_user = r.id')
            ->andWhere($qb->expr()->eq('id_room', $qb->createNamedParameter($roomId)));


        $qb->delete($this->getTableName())
            ->where($qb->expr()->in('id_msg', $qb->createFunction($ms->getSQL()), IQueryBuilder::PARAM_INT_ARRAY));

        // $logger->info(sprintf('MessageMapper::findMessages::[%s]', $qb->getSQL()));
        return $qb->executeStatement();
    }

    /**
     * @param string $userId
     * @return array
     */
    public function deleteByMsgsId($msgsId): int {
        $qb = $this->db->getQueryBuilder();

        $qb->delete($this->getTableName())
            ->where($qb->expr()->in('id_msg', $qb->createNamedParameter($msgsId, IQueryBuilder::PARAM_INT_ARRAY)));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));
        return $qb->executeStatement();
    }

    /**
     * @param string $userId
     * @return array
     */
    public function deleteByMessageId($msgId): int {
        $qb = $this->db->getQueryBuilder();

        $qb->delete($this->getTableName(), 'f')
            ->where($qb->expr()->eq('f.id_msg', $qb->createNamedParameter($msgId)));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));
        return $qb->executeStatement();
    }

}
