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
class File extends Entity implements JsonSerializable {

    /** @var int */
    protected $idMsg;
    /** @var string */
    protected $name;
    /** @var int */
    protected $size;
    /** @var string */
    protected $type;
    /** @var string */
    protected $extension;
    /** @var boolean */
    protected $audio;
    /** @var real */
    protected $duration;
    /** @var text */
    protected $url;
    /** @var text */
    protected $localUrl;

    public function __construct() {
        ////////////////////////////////
        $this->addType('idMsg', 'int');
        $this->addType('size',  'int');
        $this->addType('name',  'string');
        $this->addType('type',  'string');
        $this->addType('extension', 'string');
        $this->addType('url',      'string');
        $this->addType('localUrl', 'string');
        $this->addType('audio',    'bool');
        $this->addType('duration', 'real');
    }

    public function jsonSerialize(): array {

        if (!is_null($this->id)) {
            $res['id']   = $this->id;
        };
        if (!is_null($this->idMsg)) {
            $res['idMsg']   = $this->idMsg;
        };
        if (!is_null($this->name)) {
            $res['name']   = $this->name;
        };
        if (!is_null($this->type)) {
            $res['type']   = $this->type;
        };
        if (!is_null($this->extension)) {
            $res['extension']   = $this->extension;
        };
        if (!is_null($this->size)) {
            $res['size']   = $this->size;
        };
        if (!is_null($this->url)) {
            $res['url']   = $this->url;
        };
        if (!is_null($this->localUrl)) {
            $res['localUrl']   = $this->localUrl;
        };
        if (!is_null($this->audio)) {
            $res['audio']   = $this->audio;
        };
        if (!is_null($this->duration)) {
            $res['duration']   = $this->duration;
        };
        // $res['progress']   = 100;

        return $res;
    }

}
