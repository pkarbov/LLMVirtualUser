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
use OCA\LLaMaVirtualUser\Db\User;

/**
 * @template-extends QBMapper<Note>
 */
class UserMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lvr_chat_user', User::class);
    }

    /**
     * Find or create New User
     *
     * @param array $values
     * @return DataResponse
     */
    public function addFindUser(User $user): ?User {
        // $logger->info(sprintf('UserMapper::addFindUser %s', json_encode($user, JSON_PRETTY_PRINT)));
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('llamavirtualuser')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        return $this->findEntity($qb);
    }

    public function find_by_name(string $userName): ?User {
        /* @var $qb IQueryBuilder */
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('username', $qb->createNamedParameter($userName)));
        return $this->findEntity($qb);
    }

    public function find_by_id(int $id): ?User {
        /* @var $qb IQueryBuilder */
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
        return $this->findEntity($qb);
    }
}
