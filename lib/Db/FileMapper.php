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
use OCA\LLaMaVirtualUser\Db\File;

/**
 * @template-extends QBMapper<Note>
 */
class FileMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lvr_msg_files', File::class);
    }

    /**
     * @param string $userId
     * @return array
     */
    public function findFiles($msgId): array {
        $qb = $this->db->getQueryBuilder();

        $qb->selectAlias('f.id',       'id')
           ->selectAlias('f.id_msg',   'id_msg')
           ->selectAlias('f.name',     'name')
           ->selectAlias('f.size',     'size')
           ->selectAlias('f.type',     'type')
           ->selectAlias('f.extension','extension')
           ->selectAlias('f.url',      'url')
           ->selectAlias('f.local_url','local_url')
           ->selectAlias('f.audio',    'audio')
           ->selectAlias('f.duration', 'duration')

            ->from($this->getTableName(), 'f')
            ->where($qb->expr()->eq('f.id_msg', $qb->createNamedParameter($msgId)));

        // $logger->info(sprintf('MessageMapper::findMessages %s', $qb->getSQL()));

        return $this->findEntities($qb);
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
    public function deleteExcludingIds($msgsId, $idKeep): int {
        $qb = $this->db->getQueryBuilder();

        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('id_msg', $qb->createNamedParameter($msgsId)))
            ->andWhere($qb->expr()->notIn('id', $qb->createNamedParameter($idKeep, IQueryBuilder::PARAM_INT_ARRAY)));

        // $logger->info(sprintf('MessageMapper::deleteExcludingIds %s', $qb->getSQL()));
        return $qb->executeStatement();
    }

    /**
     * @param string $userId
     * @return array
     */
    public function deleteIncludingIds($msgsId, $idKeep): int {
        $qb = $this->db->getQueryBuilder();

        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('id_msg', $qb->createNamedParameter($msgsId)))
            ->andWhere($qb->expr()->in('id', $qb->createNamedParameter($idKeep, IQueryBuilder::PARAM_INT_ARRAY)));

        // $logger->info(sprintf('MessageMapper::deleteIncludingIds %s', $qb->getSQL()));
        return $qb->executeStatement();
    }

}
