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
class Reaction extends Entity implements JsonSerializable {

    /** @var int */
    protected $idMsg;
    /** @var int */
    protected $idUser;
    /** @var datetime */
    protected $seen;
    /** @var text */
    protected $reaction;


    public function __construct() {
        ////////////////////////////////
        $this->addType('idMsg',    'int');
        $this->addType('idUser',   'int');
        $this->addType('seen',     'datetime');
        $this->addType('reaction', 'string');
    }

    public function jsonSerialize(): array {
        $test1 = (object) [];
        $test2 = (object) [];

        $res = [];

        if (!is_null($this->id)) {
            $res['id']   = $this->id;
        };
        if (!is_null($this->idUser)) {
            $res['idUser']   = $this->idUser;
        };
        if (!is_null($this->idMsg)) {
            $res['idMsg']   = $this->idMsg;
        };
        if (!is_null($this->reaction)) {
            $res['reaction']   = $this->reaction;
        };
        if (!is_null($this->seen)) {
            $test1->seconds     = $this->seen->getTimestamp();
            $test1->nanoseconds = 0;
            $res['seen']   = $test1;
        };

        return $res;
    }

}
