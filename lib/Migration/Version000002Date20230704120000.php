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

        if ($schema->hasTable('lvr_msg_files')) {
            print("Droping table: lvr_msg_files\n");
            $schema->dropTable('lvr_msg_files');
        }

        if ($schema->hasTable('lvr_msg_reaction')) {
            print("Droping table: lvr_msg_reaction\n");
            $schema->dropTable('lvr_msg_reaction');
        }

        if ($schema->hasTable('lvr_chat_room_msg')) {
            print("Droping table: lvr_chat_room_msg\n");
            $schema->dropTable('lvr_chat_room_msg');
        }

        if ($schema->hasTable('lvr_chat_room_user')) {
            print("Droping table: lvr_chat_room_user\n");
            $schema->dropTable('lvr_chat_room_user');
        }

        if ($schema->hasTable('lvr_chat_room')) {
            print("Droping table: lvr_chat_room\n");
            $schema->dropTable('lvr_chat_room');
        }

        if ($schema->hasTable('lvr_chat_user')) {
            print("Droping table: lvr_chat_user\n");
            $schema->dropTable('lvr_chat_user');
        }
        return $schema;
    }
}
