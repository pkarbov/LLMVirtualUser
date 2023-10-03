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


use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IURLGenerator;

use OCP\AppFramework\Db\DoesNotExistException;

use OCA\LLaMaVirtualUser\AppInfo\Application;
use OCA\LLaMaVirtualUser\Logger\Logger;
use OCA\LLaMaVirtualUser\Db\UserMapper;
use OCA\LLaMaVirtualUser\Db\User;

class UserService {
    /**
    * @var LLaMaLogger
    */
    protected $logger;

    /** @var IUserSession */
    private $session;

    /** @var string */
    private $userId;

    /** @var IUserManager */
    private $userManager;

    /** @var UserMapper */
    private $user_db;

    /**
     * @var IURLGenerator
     */
    private $url;

    public function __construct(Logger $logger,
                                UserMapper $user_db,
                                IUserSession $session,
                                IUserManager $userManager,
                                IURLGenerator $url) {
        $this->userManager = $userManager;
        $this->session = $session;
        $this->user_db = $user_db;
        $this->logger = $logger;
        $this->url = $url;

        $this->userId = null;
    }

    public function getSession(): IUserSession {
        return $this->session;
    }

    public function getUserId(): ?string {
        if ($this->userId !== null) {
            return $this->userId;
        }
        if ($this->session && $this->session->getUser() !== null) {
            $this->userId = $this->session->getUser()->getUID();
            return $this->userId;
        }

        return null;
    }

    public function getUser(): ?IUser {
        $userId = $this->getUserId();
        if ($userId !== null) {
            return $this->userManager->get($userId);
        }
        return null;
    }

    public function findCreateCurrentUser(): ?User {
        return $this->findCreateUser(ucfirst($this->getUserId()));
        return $this->findCreateUser($this->getUserId());
    }

    public function findCreateLLaMaUser(): ?User {
        return $this->findCreateUser('LLaMa', 'llama.svg');
    }

    public function findCreateTestUsers(): array {
        $user01 = $this->findCreateUser('Luke', 'Luke.jpg');
        $user02 = $this->findCreateUser('Leia', 'Leia.jpg');
        $user03 = $this->findCreateUser('Yoda', 'Yoda.webp');
        return [$user01, $user02, $user03];
    }

    public function findCreate(User $user): ?User {
        try{
            $userDB = $this->user_db->find_by_name($user->getUsername());
        }catch(DoesNotExistException $ex){
            $userDB = $this->user_db->insert($userDB);
        }
        $this->logger->info(sprintf('UserService::findCreate %s', json_encode($userDB, JSON_PRETTY_PRINT)));
        return $userDB;
    }

    protected function findCreateUser(string $name, string $avatar = null): ?User {
        try{
            $userDB = $this->user_db->find_by_name($name);
        }catch(DoesNotExistException $ex){
            if (!empty($avatar)) {
                $avatar = $this->url->getAbsoluteURL($this->url->imagePath(Application::APP_ID, $avatar));
                $userDB = User::fromParams([ 'username' => $name, 'avatar' => $avatar]);
            }else{
                $userDB = User::fromParams([ 'username' => $name]);
            }

            $userDB = $this->user_db->insert($userDB);
        }
        // $this->logger->info(sprintf('UserService::findCreateUser %s', json_encode($userDB, JSON_PRETTY_PRINT)));
        return $userDB;
    }

}
