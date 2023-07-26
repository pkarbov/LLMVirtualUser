<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\LLaMaVirtualUser\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\DB\ISchemaWrapper;
use Doctrine\DBAL\Types\Types;
use OCP\Migration\SimpleMigrationStep;

class Version000002Date20230704120000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('lvr_chat_room_msg')) {
            $schema->dropTable('lvr_chat_room_msg');
        }

        if ($schema->hasTable('lvr_chat_room_user')) {
            $schema->dropTable('lvr_chat_room_user');
        }

        if ($schema->hasTable('lvr_chat_room')) {
            $schema->dropTable('lvr_chat_room');
        }

        if ($schema->hasTable('lvr_chat_user')) {
            $schema->dropTable('lvr_chat_user');
        }
        return $schema;
    }
}
