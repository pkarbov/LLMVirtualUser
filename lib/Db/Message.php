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
class Message extends Entity implements JsonSerializable {

    /** @var int */
    protected $idParent;
    /** @var int */ 
    protected $idRoomUser;
    /** @var string */
    protected $content;
    /** @var string */
    protected $contentBin;
    /** @var datetime */
    protected $timestampStart;
    /** @var datetime */
    protected $timestampEnd;
    /** @var datetime */
    protected $seen;
    /** @var datetime */
    protected $deleted;

    public function __construct() {
        ////////////////////////////////
        $this->addType('idParent',   'int');
        $this->addType('idRoomUser', 'int');
        $this->addType('content',    'string');
        $this->addType('contentBin', 'string');
        $this->addType('seen',       'datetime');
        $this->addType('deleted',    'datetime');
        $this->addType('timestampStart', 'datetime');
        $this->addType('timestampEnd',   'datetime');
    }

    public function jsonSerialize(): array {
        $test = (object) [];
        $test->seconds      = $this->timestamp->timestampEnd();
        $test->nanoseconds  = 0;
        $res = [
            'id'        => $this->id,
            'sender_id' => $this->idUser,
            'content'   => $this->content,
            'timestamp' => $test,
        ];
        
        return $res;
    }

}
