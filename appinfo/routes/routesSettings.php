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

return [
    'routes' => [

        ['name' => 'settings#setConfig',          'url' => '/config',                 'verb' => 'PUT'],
        ['name' => 'settings#setServerAddress',   'url' => '/server-address-config',  'verb' => 'PUT'],
        ['name' => 'settings#setParameterLevel',  'url' => '/parameter-level-config', 'verb' => 'PUT'],
        ['name' => 'settings#setActiveModel',     'url' => '/activate-model',         'verb' => 'PUT'],

        ['name' => 'settings#getServerStatus',    'url' => '/server-status',          'verb' => 'GET'],
        ['name' => 'settings#getEngineStatus',    'url' => '/engine-status',          'verb' => 'GET'],
        ['name' => 'settings#getServerModels',    'url' => '/server-models',          'verb' => 'GET'],
        ['name' => 'settings#getModelSettings',   'url' => '/model-settings',         'verb' => 'GET'],

    ],
];
