<?php

/**
 * Nextcloud - maps
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2019
 */

namespace OCA\LLaMaVirtualUser\BackgroundJob;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Exception\RequestException;

use OCP\ICache;
use OCP\ICacheFactory;

use \OCP\BackgroundJob\QueuedJob;
use \OCP\BackgroundJob\IJobList;
use \OCP\AppFramework\Utility\ITimeFactory;

use OCA\LLaMaVirtualUser\Controller\MessageController;
use OCA\LLaMaVirtualUser\AppInfo\Application;
use OCA\LLaMaVirtualUser\Logger\Logger;
use OCA\LLaMaVirtualUser\Db\Message;

class StreamMessageJob extends QueuedJob {

	/** @var MessageService */
	private MessageController $messageService;

	/** @var ICacheFactory */
	private ICacheFactory $cacheFactory;

	/** @var ICache */
	private ICache $backgroundJobCache;
    /**
     * @var LLaMaLogger
     */
    private Logger $logger;

    /**
     * UserInstallScanJob constructor.
     *
     * A QueuedJob to scan user storage for photos and tracks
     *
	 * @param ITimeFactory $timeFactory
	 * @param PhotofilesService $photofilesService
     */
    public function __construct(ITimeFactory $timeFactory,
                                MessageController $messageService,
		                        ICacheFactory $cacheFactory,
                                Logger $logger) {

        parent::__construct($timeFactory);

    	$this->logger = $logger;
    	$this->cacheFactory = $cacheFactory;
        $this->messageService = $messageService;
	    $this->backgroundJobCache = $this->cacheFactory->createDistributed('llama:background-jobs');
        $this->logger->info('StreamMessageJob::__construct()');
    }

    /**
     * Run
     *
     * @param array $arguments
     * @return void
     */
    public function run($arguments) {
        $msg = $arguments['msg'];
        $this->logger->info(sprintf('StreamMessageJob::run(%s)', json_encode($msg, JSON_PRETTY_PRINT)));

        $url = 'https://llama.geoid.ca/api/v1/completion/';

        $client = new \GuzzleHttp\Client();
        $json = json_encode([   
            'prompt'     => 'Question: What are the names of the planets in the solar system? Answer:',
            'max_tokens' => 1024,
            'stream'     => True,
        ]);
        $headers = ['Content-type' => 'application/json', 'Authorization' => 'Bearer 72290464-fdbd-4ce6-aa6c-9ac643740df1'];
        try {
            $response = $client->request('POST', $url, ['body' => $json, 'headers' => $headers, 'stream'=>True]);
            // $body is now a Guzzle stream object.
            $body = $response->getBody();
            // create a generator
            $generator = function ($body) {
                while (!$body->eof()) {
                    // read big chunks (over network)
                    $chunk = $body->read(100);
                    yield $chunk;
                }
            };
            // build memory stream
            $iter = $generator($body);
            $stream = Utils::streamFor($iter);

            $this->logger->info('----------Start---------------');
            $time_start = microtime(true);
            while (!$stream->eof()) {
                // read line. Will read Byte by Byte.
                $chunk01 = Utils::readline($stream);
                $chunk02 = substr(trim($chunk01),-$str_len+8);
                $str_len = strlen($chunk01);
                if ($str_len > 10) {
                    // $this->logger->info(json_encode(json_decode($chunk02), JSON_PRETTY_PRINT));
                    $this->logger->info($chunk02);
                    // $this->logger->info(preg_replace('/[\x0d]/','',$chunk));
                }
            }
            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start);
            $this->logger->info('Total time: '.$execution_time.' sec.');
            $this->logger->info('-----------End----------------');
        } catch (RequestException $e){
            $this->logger->error($e);
        }

        // $this->messageService->streamMessage($msg);
        $this->logger->info('Done!!!!');
    }
}
