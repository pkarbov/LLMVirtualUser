<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\LLaMaVirtualUser\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'llamavirtualuser';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}
}
