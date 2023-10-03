<?php
/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\LLaMaVirtualUser\Service;


use OCP\Files\Storage\IStorage;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use OC\OCS\Exception;

use OCA\LLaMaVirtualUser\AppInfo\Application;
use OCA\LLaMaVirtualUser\Logger\Logger;

class FileService {

    /**
    * @var LLaMaLogger
    */
    protected $logger;
    /**
    * @var IRoot
    */
    protected $root;


    public function __construct(Logger $logger,
                                IRootFolder $root
    ) {
        $this->logger = $logger;
        $this->root = $root;
    }

    /**
     * @param string $userId
     * @return array
     */
    public function createFindRoomFile($currentUserId, $roomId, $userId, $fileName) {
        // $this->logger->info(sprintf('FileService::createFindRoomFile { %s, %d, %s }', $userId, $roomId, $name));
        /////////////////////////////////////////////
        $llamaName  = 'LLaMa';
        $roomName   = sprintf('room-%d', $roomId);
        $userName   = sprintf('user-%d', $userId);
        $userFolder = $this->root->getUserFolder($currentUserId);
        /////////////////////////////////////////////
        $llamaFolder = $userFolder->nodeExists($llamaName) ? $userFolder->get($llamaName) : $userFolder->newFolder($llamaName);
        $roomFolder  = $llamaFolder->nodeExists($roomName) ? $llamaFolder->get($roomName) : $llamaFolder->newFolder($roomName);
        $userFolder  = $roomFolder->nodeExists($userName)  ? $roomFolder->get($userName)  : $roomFolder->newFolder($userName);
        $fileFolder  = $userFolder->nodeExists($fileName)  ? $userFolder->get($fileName)  : $userFolder->newFolder($fileName);
        /////////////////////////////////////////////
        // $this->logger->info(sprintf('FileService::createFindRoomFile::path { %s }', $userFolder->getPath()));
        // $this->logger->info(sprintf('FileService::createFindRoomFile::path { %s }', $llamaFolder->getPath()));
        // $this->logger->info(sprintf('FileService::createFindRoomFile::path { %s }', $roomFolder->getPath()));
        // $this->logger->info(sprintf('FileService::createFindRoomFile::path { %s }', $fileFolder->getPath()));
        // $this->logger->info(sprintf('FileService::createFindRoomFile::type { %s }', $fileFolder->getType()));
        return $fileFolder;
        /////////////////////////////////////////////
        if ($fileFolder->getType() == \OCP\Files\FileInfo::TYPE_FOLDER) {
            return $fileFolder;
        }
        else if (!$fileFolder->isCreatable()) {
            $response = $folderName + ' is not writeable';
            throw new Exception($response);
        }
    }

    /**
     * @param string $userId
     * @return array
     */
    public function createLLaMaDirectory($userId) {
        $this->logger->info(sprintf('FileService::createLLaMaDirectory %s', $userId));

        $userFolder = $this->root->getUserFolder($userId);
        if (!$userFolder->nodeExists('/LLaMa')) {
            $userFolder->newFolder('LLaMa');
        }
        if ($userFolder->nodeExists('/LLaMa')) {
            $mapsFolder = $userFolder->get('/LLaMa');
            if ($mapsFolder->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                return $mapsFolder;
            }
            else if (!$mapsFolder->isCreatable()) {
                $response = '/LLaMa is not writeable';
                throw new Exception($response);
            }
        }
        else {
            $response = 'Impossible to create /LLaMa';
            throw new Exception($response);
        }

    }

}
