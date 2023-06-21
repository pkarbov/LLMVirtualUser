<?php
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

$appId = OCA\LLaMaVirtualUser\AppInfo\Application::APP_ID;
\OCP\Util::addScript($appId, $appId . '-adminSettings');
?>

<div id="llama_admin">
    <div class="section" id="llama_server_status">
        <h2>
            <?php p($l->t('LLaMa Integration')) ?>
        </h2>
    </div>
    <div class="section" id="llama_server_address">
        <h2>
            <?php p($l->t('Server Config')) ?>
        </h2>
    </div>
    <div class="models section" id="llama_server_models">
        <h2>
            <?php p($l->t('Models')) ?>
        </h2>
    </div>
    <div class="model-settings section" id="llama_model_settings">
        <h2>
            <?php p($l->t('Model Settings')) ?>
        </h2>
    </div>
</div>