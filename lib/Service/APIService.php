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

namespace OCA\LLaMaVirtualUser\Service;

use Exception;
use OCP\IConfig;
use GuzzleHttp\Client;
use OCP\Http\Client\IResponse;
use OCP\Http\Client\IClientService;
use OCP\AppFramework\Http\DataResponse;

use OCA\LLaMaVirtualUser\AppInfo\Application;

class APIService {
    /**
    * @var LLaMaLogger
    */
    protected $logger;
    /**
    * @var IClientService
    */
    private IClientService $clientService;
    private array $settingMap;

    public function __construct(Logger $logger,
                                IClientService $clientService
                                ) {
        $this->clientService = $clientService;
        $this->logger = $logger;
        $this->settingMap = $this->initSettingMap();
    }

    /**
     * @checkConnected
     *
     * @return DataResponse
     */
    public function checkConnected(IConfig $config): DataResponse {
        // $this->logger->info('APIService::checkConnected');
        $connected = $this->getConnectedStatus($config);
        return new DataResponse([
            'connected' => $connected,
        ]);
    }

    /**
     * @getConnectedStatus
     *
     * @return integer
     */
    public function getConnectedStatus(IConfig $config): int {
        // $this->logger->info('APIService::checkConnectedStatus');

        $serverAddress = $config->getAppValue(Application::APP_ID, 'server_address');
        $serverSecret  = $config->getAppValue(Application::APP_ID, 'server_secret');
        $connected     = 0;

        $options = [
            'headers' => [
                'Content-Type'  => 'application/json', 
                'Authorization' => 'Bearer ' . $serverSecret,
            ],
        ];

        if ($serverAddress === '') {
            $connected = 3; // not set
            return $connected;
        }
        // get models list
        try {
            $url = $this->getUrlHealth($serverAddress);
            $client = $this->clientService->newClient();
            $response = $client->get($url, $options);
            $connected = 2; // Ok
        } catch (\Exception $ex) {
            $this->logger->info($ex->getMessage());
            $connected = 0; // error
        }
        // set connection result
        //$config->setAppValue(Application::APP_ID, 'server_connected', $connected);
        return $connected;
    }

    /**
     * @getServerModels
     *
     * @return integer
     */
    public function getServerModels(IConfig $config): array {
        // $this->logger->info('APIService::getServerModels');

        $serverAddress = $config->getAppValue(Application::APP_ID, 'server_address');
        $serverSecret  = $config->getAppValue(Application::APP_ID, 'server_secret');

        $url = $this->getUrlModels($serverAddress);

        $serverModels   = $this->APIServerGet($url, $serverSecret);
        //$this->logger->info(json_encode($serverModels, JSON_PRETTY_PRINT));

        return $serverModels == (object)[] ? [] : $serverModels->data;
    }

    /**
     * @getModelSettings
     *
     * @return integer
     */
    public function getModelSettings(IConfig $config): array {
        // $this->logger->info('APIService::getModelSettings');

        $serverAddress = $config->getAppValue(Application::APP_ID, 'server_address');
        $serverSecret  = $config->getAppValue(Application::APP_ID, 'server_secret');

        $url = $this->getUrlModelSettings($serverAddress);

        $modelSettings   = $this->APIServerGet($url, $serverSecret);
        $modelSettings   = $this->updateSettingsInside($modelSettings);
        //$this->logger->info(json_encode($modelSettings, JSON_PRETTY_PRINT));

        return $modelSettings;
    }
    /**
     * @activateModel
     *
     * @return integer
     */
    public function activateModel(IConfig $config, array $model, array $param): DataResponse {
        // $this->logger->info(sprintf('APIService::activateModel: %s', json_encode($model, JSON_PRETTY_PRINT)));
        // $this->logger->info(sprintf('APIService::activateModel: %s', json_encode($param, JSON_PRETTY_PRINT)));

        $serverAddress = $config->getAppValue(Application::APP_ID, 'server_address');
        $serverSecret  = $config->getAppValue(Application::APP_ID, 'server_secret');

        $url = $this->getUrlActivateModel($serverAddress);
        
        sleep(5);
        $modelSettings   = $this->updateSettingsOutside($param);
        $response        = $this->APIServerPost($url, ['model' => $model, 'setting' => $modelSettings], $serverSecret);

        // $this->logger->info(sprintf('APIService::activateModel: %s', json_encode($response->getBody(), JSON_PRETTY_PRINT)));
        // $this->logger->info(sprintf('APIService::activateModel: %s', $response->getBody()));
        if ($response->getStatusCode() != 200 ) {
            return new DataResponse([$response->getReasonPhrase()], $statusCode = $response->getStatusCode());
        };
        return new DataResponse($response->getBody(), $statusCode = $response->getStatusCode());
        
    }

    /**
     * @humanFileSize
     *
     * @return string
     */
    protected function humanFileSize(int $bytes, bool $si = false, int $decimals = 2): string {

        if (!+$bytes) return '0 Bytes';

        $k = $si ? 1000 : 1024;
        $dm = $decimals < 0 ? 0 : $decimals;
        $sizes = $si
            ? array('Bytes', 'KB',  'MB',  'GB',  'TB',  'PB',  'EB',  'ZB',  'YB')
            : array('Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');

        $i   = floor(log($bytes) / log($k));
        $res = sprintf("%.{$dm}f %s", ($bytes / pow($k, $i)), $sizes[$i]);
        $this->logger->info(sprintf("APIService::humanFileSize (%s)", $res));
        return $res;
    }

    /**
     * @updateSettingsOutside
     *
     * @return array
     */
    protected function updateSettingsOutside(array $modelSettings): array {
        #$this->logger->info(sprintf('APIService::updateSettingsOutside: %s', json_encode($modelSettings->data[0], JSON_PRETTY_PRINT)));

        // combine values from LLaMa server with extended information
        $settings = [];
        foreach ($modelSettings as $value) {
            // $this->logger->info(sprintf('APIService::updateSettingsOutside: %s', json_encode($value, JSON_PRETTY_PRINT)));
            $key = $value['id'];
            switch(gettype($value['val'])) {
                case 'integer' ;
                    $val =  intval($value['val_str']);
                    break;
                case 'boolean' ;
                    $val = $value['val'];
                    break;
                case 'string' ;
                    $val = $value['val'];
                    break;
                }
            $settings = array_merge($settings, array($key => $val));
        };
        // $this->logger->info(sprintf('APIService::updateSettingsOutside: %s', json_encode($settings, JSON_PRETTY_PRINT)));
        return $settings;
    }

    /**
     * @updateSettingsInside
     *
     * @return array
     */
    protected function updateSettingsInside(object $modelSettings): array {
        #$this->logger->info(sprintf('APIService::updateSettingsInside: %s', json_encode($modelSettings->data[0], JSON_PRETTY_PRINT)));

        if ($modelSettings == (object)[]) return [];
        // combine values from LLaMa server with extended information
        $settings = [];
        foreach ($modelSettings->data[0] as $key => $value) {
            // check if we have extended information for the setting name
            $localArray = [];
            if (array_key_exists($key, $this->settingMap)) {
                // build setting record for web UI
                switch(gettype($value)) {
                    case 'integer' ;
                        $df  = ($key == 'cache_size') ? $this->humanFileSize($value)  : strval($value);
                        $val = $value;
                        $localArray = array('id'=>$key, 'df' => $df, 'val' => $val, 'val_str' => strval($value));
                        break;
                    case 'boolean' ;
                        $df  = $value ? 'True' : 'False';
                        $val = $value;
                        $localArray = array('id'=>$key, 'df' => $df, 'val' => $val);
                        break;
                    case 'string' ;
                        $df  = $value;
                        $val = $value;
                        $localArray = array('id'=>$key, 'df' => $df, 'val' => $val);
                        break;
                }
                // check if we set data for the setting
                if (count($localArray) > 0) {
                    $addtionalSettings = $this->settingMap[$key];
                    $mergeArrays       = array_merge($localArray, $addtionalSettings);
                    array_push($settings, $mergeArrays);
                };
            };
        };
        // sort settings array
        $cmp = function ($a, $b) {
            $this->logger->info(sprintf('APIService::updateSettings: %s <> %s', 
                                    json_encode($a, JSON_PRETTY_PRINT),
                                    json_encode($b, JSON_PRETTY_PRINT),
                                ));
            if ($a['sort'] == $b['sort']) {
                return 0;
            }
            return ($a['sort'] < $b['sort']) ? -1 : 1;
        };
        uasort($settings, $cmp);
        // get array values
        $settings = array_values($settings);
        // $this->logger->info(sprintf('APIService::updateSettingsInside: %s', json_encode($settings, JSON_PRETTY_PRINT)));
        return $settings;
    }

    /**
     * @APIServerPost
     *
     * @return array
     */
    protected function APIServerPost(string $url, $data, $serverSecret): object {
        // $this->logger->info('APIService::getAPIServer');

        $response  = (object)[];

        $options = [
            'json' => json_decode(json_encode($data), true),
            'headers' => [
                'Content-Type'  => 'application/json', 
                'Authorization' => 'Bearer ' . $serverSecret,
                'allow_redirects' => false,
            ],
        ];

        // get models list
        try {
            // $this->logger->info($url);
            //$this->logger->info(json_encode($options, JSON_PRETTY_PRINT));

            $client   = $this->clientService->newClient();
            $response = $client->post($url, $options);

            // $this->logger->info(json_encode($response, JSON_PRETTY_PRINT));
        } catch (\Exception $ex) {
            $this->logger->info($ex->getMessage());
            $response = $ex->getResponse();
        }
        // $this->logger->info(json_encode($response, JSON_PRETTY_PRINT));
        // $this->logger->info('+++++++++++++++++++++++');
        return $response;
    }

    /**
     * @APIServerGet
     *
     * @return array
     */
    protected function APIServerGet(string $url, $serverSecret): object {
        // $this->logger->info('APIService::getAPIServer');

        $serverReplay  = (object)[];

        $options = [
            'headers' => [
                'Content-Type'  => 'application/json', 
                'Authorization' => 'Bearer ' . $serverSecret,
            ],
        ];

        // get models list
        try {

            $client   = $this->clientService->newClient();
            $response = $client->get($url, $options);

            $resp_str     = $response->getBody();
            $resp_json    = json_decode($resp_str);
            $serverReplay = $resp_json;

            // $this->logger->info(gettype($serverReplay));
            // $this->logger->info(json_encode($serverReplay, JSON_PRETTY_PRINT));

        } catch (\Exception $ex) {
            $this->logger->info($ex->getMessage());
        }

        return $serverReplay;
    }

    /**
     * @getUrlModels
     *
     * @return string
     */
    protected function getUrlActivateModel(string $serverAddress): string {

        $subfolder = "api/v1/activate";
        $base      = $serverAddress;

        return $this->getUrlTemplate($serverAddress, $subfolder) . '/';
    }
    /**
     * @getUrlModels
     *
     * @return string
     */
    protected function getUrlHealth(string $serverAddress): string {

        $subfolder = "api/health";
        $base      = $serverAddress;
        
        return $this->getUrlTemplate($serverAddress, $subfolder);
    }

    /**
     * @getUrlModels
     *
     * @return string
     */
    protected function getUrlModels(string $serverAddress): string {

        $subfolder = "api/v1/models";
        $base      = $serverAddress;

        return $this->getUrlTemplate($serverAddress, $subfolder) . '/';
    }

    /**
     * @getUrlModelSettings
     *
     * @return string
     */
    protected function getUrlModelSettings(string $serverAddress): string {

        $subfolder = "api/v1/settings";
        $base      = $serverAddress;
        
        return $this->getUrlTemplate($serverAddress, $subfolder) . '/';
    }

    /**
     * @getUrlTemplate
     *
     * @return string
     */
    protected function getUrlTemplate(string $serverAddress, string $urlfolder): string {

        $base      = $serverAddress;
        $subfolder = $urlfolder;

        $callback = function (&$component) {
            $component = rtrim($component, '/');
        };

        $array = array($base, $subfolder);
        array_walk_recursive($array, $callback); 
        $url = implode('/', $array);

        return $url;
    }

    /**
     * @initSettingMap
     *
     * @return array
     */
    protected function initSettingMap(): array {

        $settingMap = array(
            'n_ctx'              => array( 'st' => 0, 'sort' => 0,  'lb' => 'Context size.'  , 'desc' => 'Maximum context size.', 'minmax' => array(1, 2048)),
            'n_batch'            => array( 'st' => 0, 'sort' => 1,  'lb' => 'Token batch.'   , 'desc' => 'Maximum number of prompt tokens to batch together when calling llama_eval.', 'minmax' => array(1, 2048)),
            'n_threads'          => array( 'st' => 0, 'sort' => 2,  'lb' => 'Threads number.', 'desc' => 'Number of threads.', 'minmax' => array(1, 512)),
            'cache'              => array( 'st' => 0, 'sort' => 3,  'lb' => 'Use a cache.'   , 'desc' => 'Use a cache to reduce processing times for evaluated prompts.'),
            'cache_size'         => array( 'st' => 0, 'sort' => 4,  'lb' => 'Cache size.'    , 'desc' => 'The size of the cache in bytes. Only used if cache is True.'),
            'cache_type'         => array( 'st' => 0, 'sort' => 5,  'lb' => 'Cache type.'    , 'desc' => 'The type of cache to use. Only used if cache is True.', 'select' => array('ram','disk')),

            'n_gpu_layers'       => array( 'st' => 1, 'sort' => 6,  'lb' => 'GPU layers.' , 'desc' => 'The number of layers to put on the GPU. The rest will be on the CPU.'),
            'use_mmap'           => array( 'st' => 1, 'sort' => 7,  'lb' => 'Use a mmap.' , 'desc' => 'Use mmap if possible.'),
            'use_mlock'          => array( 'st' => 1, 'sort' => 8,  'lb' => 'Use a mlock.', 'desc' => 'Force the system to keep the model in RAM.'),

            'last_n_tokens_size' => array( 'st' => 2, 'sort' => 9,  'lb' => 'Last n tokens.' ,  'desc' => 'Last n tokens to keep for repeat penalty calculation.', 'minmax' => array(0, 2048)),
            'low_vram'           => array( 'st' => 2, 'sort' => 10, 'lb' => 'Use less VRAM.' ,  'desc' => 'Whether to use less VRAM. This will reduce performance.' ),
            'seed'               => array( 'st' => 2, 'sort' => 11, 'lb' => 'Random seed.'   ,  'desc' => 'Random seed. 0 for random.'),
            'f16_kv'             => array( 'st' => 2, 'sort' => 12, 'lb' => 'Half precision.',  'desc' => 'Use half-precision for key/value cache.'),
            'logits_all'         => array( 'st' => 2, 'sort' => 13, 'lb' => 'Logits all.'    ,  'desc' => 'Return logits for all tokens, not just the last token.'),
            'vocab_only'         => array( 'st' => 2, 'sort' => 14, 'lb' => 'Vocab only.'    ,  'desc' => 'Only load the vocabulary no weights.'),
            'embedding'          => array( 'st' => 2, 'sort' => 15, 'lb' => 'Embedding only.',  'desc' => 'Embedding mode only.'),
            'lora_base'          => array( 'st' => 2, 'sort' => 16, 'lb' => 'Path to base LoRA model.',  'desc' => 'Path to base model, useful if using a quantized base model and you want to apply LoRA to an f16 model.'),
            'lora_path'          => array( 'st' => 2, 'sort' => 17, 'lb' => 'Path to LoRA file.'      ,  'desc' => 'Path to a LoRA file to apply to the model.'),
            'verbose'            => array( 'st' => 2, 'sort' => 18, 'lb' => 'Verbose.'       ,  'desc' => 'Whether to print debug information.'),
        );
        // $this->logger->info(json_encode($settingMap, JSON_PRETTY_PRINT));
        return $settingMap;
    }

}
