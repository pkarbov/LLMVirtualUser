<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\LLaMaVirtualUser\Listener;

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

use OCA\LLaMaVirtualUser\Logger\Logger;

class CSPListener implements IEventListener {

    protected $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function handle(Event $event): void {
        if (!($event instanceof AddContentSecurityPolicyEvent)) {
            return;
        }

        $this->logger->info(sprintf('CSPListener::event %s', json_encode($event, JSON_PRETTY_PRINT)));

        $csp = new ContentSecurityPolicy();
        //$csp->addAllowedMediaDomain('blob:');
        //$csp->addAllowedWorkerSrcDomain('blob:');
        //$csp->addAllowedWorkerSrcDomain("'self'");
        //$csp->addAllowedChildSrcDomain('blob:');
        //$csp->addAllowedChildSrcDomain("'self'");
        //$csp->addAllowedScriptDomain('blob:');
        //$csp->addAllowedScriptDomain("'self'");
        //$csp->addAllowedConnectDomain('blob:');
        //$csp->addAllowedConnectDomain("'self'");
        //$csp->addAllowedImageDomain('https://www.google.com/images/*');
        //$csp->addAllowedScriptDomain('https://test-88585-default-rtdb.firebaseio.com/*');
        //$csp->addAllowedConnectDomain('https://firestore.googleapis.com/*');

        $event->addPolicy($csp);
    }
}
