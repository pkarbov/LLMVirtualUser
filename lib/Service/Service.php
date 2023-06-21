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
use OCP\Http\Client\IClientService;
use GuzzleHttp\Client;

class Service {
    private IClientService $clientService;
    private bool $isCLI;

    public function __construct(IClientService $clientService, bool $isCLI) {
        $this->clientService = $clientService;
        $this->isCLI = $isCLI;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function models() : void {
        // Log the message to the system log
        try {
            $client = $this->clientService->newClient();
            $response = $client->get($this->getUrlModels());
            $resp_str = $response->getBody();
            error_log($resp_str, 0);
        } catch (\Exception $ex) {
            error_log($ex->getMessage(), 0);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function completions(bool $isStream = false) : void {
        // Log the message to the system log
        try {
            if ($isStream) {
                $client = new Client();
                $response = $client->request('POST', $this->getUrlCompletions(), ['json' => $this->getRequest($isStream), 'headers' => $this->getHeaders(), 'stream' => $isStream,]);
                // Read bytes off of the stream until the end of the stream is reached
                $stream = $response->getBody();
                while (!$stream->eof()) {
                    $resp_str = $stream->read(1024);
                    error_log($resp_str, 0);
                }
            } else{
                $client = $this->clientService->newClient();
                $response = $client->post($this->getUrlCompletions(), ['json' => $this->getRequest($isStream), 'headers' => $this->getHeaders(),]);
                $resp_str = $response->getBody();
                error_log($resp_str, 0);
            }
            //$resp_str = json_decode($response->getBody(), true);
        } catch (\Exception $ex) {
            error_log($ex->getMessage(), 0);
        }
    }


    protected function getUrlModels(): string {
        return "https://llama.geoid.ca/v1/models";
    }

    protected function getUrlCompletions(): string {
        return "https://llama.geoid.ca/v1/completions";
    }

    protected function getHeaders(): array {
        //json    = {"prompt": "Question: What are the names of the planets in the solar system? Answer:", "stream": "True",},
        return ['Content-Type'  => 'application/json',
                'Authorization' => 'Bearer 72290464-fdbd-4ce6-aa6c-9ac643740df1'];
    }

    protected function getRequest(bool $isStream = false): array {
        return ['prompt'      => 'Question: What are the names of the planets in the solar system? Answer:',
                'max_tokens'  => 2024,
                'stream'      => $isStream];
    }

}
