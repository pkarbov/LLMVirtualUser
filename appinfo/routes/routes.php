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

        ['name' => 'LLaMa#main',                'url' => '/',                       'verb' => 'GET'],

        ['name' => 'config#setConfig',          'url' => '/config',                 'verb' => 'PUT'],
        ['name' => 'config#setServerAddress',   'url' => '/server-address-config',  'verb' => 'PUT'],
        ['name' => 'config#setParameterLevel',  'url' => '/parameter-level-config', 'verb' => 'PUT'],
        ['name' => 'config#setActiveModel',     'url' => '/activate-model',         'verb' => 'PUT'],

        ['name' => 'config#getServerStatus',    'url' => '/server-status',          'verb' => 'GET'],
        ['name' => 'config#getEngineStatus',    'url' => '/engine-status',          'verb' => 'GET'],
        ['name' => 'config#getServerModels',    'url' => '/server-models',          'verb' => 'GET'],
        ['name' => 'config#getModelSettings',   'url' => '/model-settings',         'verb' => 'GET'],

    ]
];
