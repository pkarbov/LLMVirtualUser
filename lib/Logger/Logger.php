<?php

declare(strict_types=1);

/**
 *
 * Nextcloud - LLaMa
 *
 * @copyright Copyright (c) 2023 Pavlo Karbovnyk <pkarbovn@gmail.com>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\LLaMaVirtualUser\Logger;

use OCA\LLaMaVirtualUser\AppInfo\Application;

use OCP\IConfig;
use OCP\ILogger;
use OCP\Log\IDataLogger;
use OCP\Log\ILogFactory;
use Psr\Log\LoggerInterface;

class Logger {
    /** @var LoggerInterface */
    protected $flowLogger;
    /** @var IConfig */
    private $config;
    /** @var ILogFactory */
    private $logFactory;

    public function __construct(IConfig $config, ILogFactory $logFactory) {
        $this->logFactory = $logFactory;
        $this->config = $config;

        $this->initLogger();
    }

    protected function initLogger(): void {
        $default = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/llama.log';
        $logFile = trim((string)$this->config->getAppValue(Application::APP_ID, 'logfile', $default));
        if ($logFile !== '') {
            $this->flowLogger = $this->logFactory->getCustomPsrLogger($logFile);
        }
    }

    public function info(string $message, array $context = []): void {
        $this->log($message, $context);
    }

    protected function log(
        string $message,
        array $context
    ): void {
        if (!isset($context['app'])) {
            $context['app'] = Application::APP_ID;
        }
        if (!isset($context['level'])) {
            $context['level'] = ILogger::INFO;
        }
        openlog(Application::APP_ID, LOG_PID | LOG_ODELAY,LOG_LOCAL4);
        syslog(LOG_INFO, $message);
        closelog();

        if (!$this->flowLogger instanceof IDataLogger) {
            return;
        }

        $this->flowLogger->logData(
            $message,
            [],
            ['app' => Application::APP_ID],
        );
    }
}
