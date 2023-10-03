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
    protected $idUser;
    /** @var int */
    protected $idRoom;
    /** @var string */
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
    protected $deleted;
    /** @var array */
    protected $reactions;
    /** @var array */
    protected $seens;
    /** @var array */
    public $files;

    public function __construct() {
        ////////////////////////////////
        $this->addType('idParent',   'int');
        $this->addType('idRoom',     'int');
        $this->addType('idUser',     'int');
        $this->addType('idRoomUser', 'int');
        $this->addType('content',    'string');
        $this->addType('contentBin', 'string');
        $this->addType('deleted',    'datetime');
        $this->addType('timestampStart', 'datetime');
        $this->addType('timestampEnd',   'datetime');
    }

    public function jsonSerialize(): array {
        $test1 = (object) [];
        $react = [];
        $seens = [];
        $files = [];
        $res = [];

        if (!is_null($this->id)) {
            $res['id']   = $this->id;
        };
        if (!is_null($this->idUser)) {
            $res['idUser']   = $this->idUser;
        };
        if (!is_null($this->idRoom)) {
            $res['idRoom']   = $this->idRoom;
        };
        if (!is_null($this->idParent)) {
            $res['idParent']   = $this->idParent;
        };
        if (!is_null($this->idRoomUser)) {
            $res['idSender']   = $this->idRoomUser;
        };
        if (!is_null($this->content)) {
            $res['content']   = $this->content;
        };

        if (!is_null($this->timestampEnd)) {
            $test1 = new \stdClass();
            $test1->seconds     = $this->timestampEnd->getTimestamp();
            $test1->nanoseconds = 0;
            $res['timestamp']   = $test1;
        };

        if (!is_null($this->seens)) {
            foreach($this->seens as $rct) {
                $id = $rct->getIdUser();

                $test1 = new \stdClass();
                $test1->seconds     = $rct->getSeen()->getTimestamp();
                $test1->nanoseconds = 0;
                $seens[$id] = $test1;

            }
            $res['seen']   = $seens;
        };

        if (!is_null($this->reactions)) {
            foreach($this->reactions as $rct) {
                $id = (string)$rct->getIdUser();
                if(array_key_exists($rct->getReaction(), $react)){
                    $react[$rct->getReaction()][] = $id;
                }else{
                    $react[$rct->getReaction()] = [$id];
                }
            }
            $res['reactions']   = $react;
        };

        if (!is_null($this->deleted)) {
            $test1 = new \stdClass();
            $test1->seconds     = $this->deleted->getTimestamp();
            $test1->nanoseconds = 0;
            $res['deleted'] = true;
        };

        if (!is_null($this->files)) {
            $res['files']   = $this->files;
        };

        return $res;
    }

}
