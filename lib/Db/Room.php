<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\LLaMaVirtualUser\Db;

use Datetime;
use JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method void     setName(string $name)
 * @method string   getName()
 * @method void     setCreated(datetime $created)
 * @method datetime getCreated()
 */
class Room extends Entity implements JsonSerializable {

    /** @var datetime */
    protected $lastUpdated;

    public function __construct() {
        ////////////////////////////////
        $this->addType('lastUpdated', 'datetime');
    }

    public function jsonSerialize(): array {
        $test = (object) [];
        $test->seconds      = $this->lastUpdated->getTimestamp();
        $test->nanoseconds  = 0;

        $res = [
            'id'          => $this->id,
            'lastUpdated' => $test,
        ];

        if (property_exists($this, 'users')) {
            $res['typingUsers'] = $this->typingUsers;
            $res['users']       = $this->users;
        }

        return $res; 
    }

}
