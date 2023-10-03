<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\LLaMaVirtualUser\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Exception\RequestException;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use \OCP\BackgroundJob\IJob;
use \OCP\BackgroundJob\IJobList;
use OCP\IURLGenerator;
use OCP\IRequest;
use OCP\Util;

use OCP\ICacheFactory;
use OCP\ICache;

use OCA\LLaMaVirtualUser\AppInfo\Application;
use OCA\LLaMaVirtualUser\Logger\Logger;

use OCA\LLaMaVirtualUser\BackgroundJob\StreamMessageJob;
use OCA\LLaMaVirtualUser\Service\StreamMessageService;
use OCA\LLaMaVirtualUser\Service\FileService;
use OCA\LLaMaVirtualUser\Service\UserService;

use OCA\LLaMaVirtualUser\Db\FileMapper;
use OCA\LLaMaVirtualUser\Db\File;

use OCA\LLaMaVirtualUser\Db\MessageMapper;
use OCA\LLaMaVirtualUser\Db\Message;

use OCA\LLaMaVirtualUser\Db\ReactionMapper;
use OCA\LLaMaVirtualUser\Db\Reaction;

use OCA\LLaMaVirtualUser\Db\RoomUserMapper;
use OCA\LLaMaVirtualUser\Db\RoomUser;

use OC\DB\Exceptions\DbalException;
use OCP\DB\Exception;

class MessageController extends Controller {

    /**
     * @var IURLGenerator
     */
    private $url;

    /**
     * @var LLaMaLogger
     */
    protected $logger;
    /**
     * @var StreamMessageService
     */
    private $jobList;
	private ICacheFactory $cacheFactory;
	private \OCP\ICache $backgroundJobCache;

    protected $msg_db;
    protected $ru_db;
    protected $rc_db;
    protected $fl_db;
    protected $file;
    protected $user;

    public function __construct(Logger $logger,
                                ICacheFactory $cacheFactory,
                                IJobList $jobList,
                                RoomUserMapper $ru_db,
                                MessageMapper $msg_db,
                                ReactionMapper $rc_db,
                                FileMapper $fl_db,
                                FileService $file,
                                UserService $user,
                                IRequest $request,
                                IURLGenerator $url) {
        $this->logger = $logger;
        $this->msg_db = $msg_db;
        $this->ru_db  = $ru_db;
        $this->rc_db  = $rc_db;
        $this->fl_db  = $fl_db;
        $this->url    = $url;
        $this->file   = $file;
        $this->user   = $user;
        $this->jobList = $jobList;
		$this->cacheFactory = $cacheFactory;
		$this->backgroundJobCache = $cacheFactory->createDistributed('llama:background-jobs');

        $this->logger->info('MessageController::__construct()');
        parent::__construct(Application::APP_ID, $request);
    }

    /*************************************************************************************************************************************************
     * Delete message
     *
     * @param array $values
     * @return DataResponse
     */
    public function deleteFileData($request): DataResponse {
        $this->logger->info(sprintf('MessageController::deleteFileData::NotImplemented %s', json_encode($request, JSON_PRETTY_PRINT)));
        /////////////////////////////////////////////////////////////////////////////////////
        return new DataResponse();
    }

    /*************************************************************************************************************************************************
     * Delete message
     *
     * @param array $values
     * @return DataResponse
     */
    public function deleteMessage($request): DataResponse {
        $idMsg = $request['messageId'];
        $date  = $request['date'];
        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('MessageController::deleteMessage %s', json_encode($request, JSON_PRETTY_PRINT)));
        /////////////////////////////////////////////////////////////////////////////////////
        $msgDB = Message::fromParams([ 'id' => $idMsg, 'deleted' => $date]);

        // $this->fl_db->deleteByMessageId($idMsg);
        // $this->rc_db->deleteByMessageId($idMsg);
        // $this->logger->info(sprintf('MessageController::deleteMessage %s', json_encode($msgDB, JSON_PRETTY_PRINT)));
        $this->msg_db->update($msgDB);
        $msgDB = $this->msg_db->findMessage($idMsg);
        // $this->logger->info(sprintf('MessageController::deleteMessage %s', json_encode($msgDB, JSON_PRETTY_PRINT)));
        /////////////////////////////////////////////////////////////////////////////////////

        return new DataResponse([$msgDB]);
    }

    /*************************************************************************************************************************************************
     * Update File data: regenerate size
     *
     * @param array $values
     * @return DataResponse
     */
    public function updateFileInfo($request): DataResponse {

        $idRoom = $request['roomId'];
        $idUser = $request['userId'];
        $file   = $request['file'];

        $file_extn = $file['extension'];
        $file_name = $file['name'];
        $file_nm01 = $this->_getAttachmentFileName($file_name, $file_extn);
        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('MessageController::updateFileData %s', json_encode($request, JSON_PRETTY_PRINT)));
        $node = $this->file->createFindRoomFile($this->user->getUserId(), $idRoom, $idUser, $file_name);
        /////////////////////////////////////////////////////////////////////////////////////
        try{
            $fl = $node->get($file_nm01);
            $updator = $node->getStorage()->getUpdater();
            $updator->update($fl->getInternalPath());
        }catch(\Throwable $ex){ }
        /////////////////////////////////////////////////////////////////////////////////////
        return new DataResponse();
    }

    /*************************************************************************************************************************************************
     * Write/Append data to file
     *
     * @param array $values
     * @return DataResponse
     */
    public function addFileData($request): DataResponse {

        $data   = $request['bytes'];
        $file   = $request['file'];
        $idRoom = $request['roomId'];
        $idUser = $request['userId'];
        $offset = $request['offset'];

        $file_extn = $file['extension'];
        $file_name = $file['name'];
        $file_nm01 = $this->_getAttachmentFileName($file_name, $file_extn);

        unset($request['bytes']);

        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('MessageController::addFileData %s', json_encode($request, JSON_PRETTY_PRINT)));
        /////////////////////////////////////////////////////////////////////////////////////
        $node = $this->file->createFindRoomFile($this->user->getUserId(), $idRoom, $idUser, $file_name);
        $fl   = $node->nodeExists($file_nm01) ? $node->get($file_nm01)  : $node->newFile($file_nm01);
        $mode = ($offset === 0) ? 'wb+' : 'ab+';
        /////////////////////////////////////////////////////////////////////////////////////
        // write data to file
        if (!($fp = $fl->fopen($mode))) {
            $response = $file_nm01 . ' is not writeable';
            throw new Exception($response);
        }
        // Write $data to our opened file.
        if (($size = fwrite($fp, base64_decode(substr($data,37)))) === FALSE) {
            $response = $file_nm01 . ' is not writeable';
            throw new Exception($response);
        }
        fclose($fp);
        /////////////////////////////////////////////////////////////////////////////////////
        return new DataResponse();
    }

    /**
     * Store message in DB
     *
     * @param array $request
     * @return DataResponse
     */
    public function updateRoomMessage($message): DataResponse {
        // $this->logger->info(sprintf('MessageController::updateRoomMessage %s', json_encode($message, JSON_PRETTY_PRINT)));
        /////////////////////////////////////////////////////////////////////////////////////
        $idMsg    = $message['id'];
        $idUser   = $message['idUser'];
        $idRoom   = $message['idRoom'];
        $content  = $message['newContent'];
        $updated  = $message['lastUpdated'];
        $filesDel = $message['filesDel'];
        $filesNew = $message['filesNew'];
        ///////////////////////////////////////
        $rctDB   = new \stdClass();
        ///////////////////////////////////////////
        // update seen timestamp
        try{
            $rctDB = $this->rc_db->findSeen($idMsg, $idUser);
            $rctDB->setSeen($updated);
            $rctDB = $this->rc_db->update($rctDB);
        } catch (\Throwable $ex) {
            $rctDB = Reaction::fromParams([ 'idMsg' => $idMsg, 'idUser' => $idUser, 'seen' => $updated]);
            $rctDB = $this->rc_db->insert($rctDB);
        }
        ///////////////////////////////////////////
        // update message text
        $msgDB = Message::fromParams([ 'id' => $idMsg, 'content' => $content]);
        $this->msg_db->update($msgDB);
        ///////////////////////////////////////////
        // delete files
        if ($filesDel && count($filesDel) > 0) {
            $this->_deleteMessageAttachment($idMsg, $filesDel);
        }
        ///////////////////////////////////////////
        // add new files
        if ($filesNew && count($filesNew) > 0) {
            $this->_storeMessageAttachment($idMsg, $idRoom, $idUser, $filesNew);
        }
        ///////////////////////////////////////////
        // get message
        $msgDB = $this->_getMessageSeenReactionFiles($idMsg);
        /////////////////////////////////////////////////////////////////////////////////////
        return new DataResponse([$msgDB]);
    }

    /**
     * Store message in DB
     *
     * @param array $request
     * @return DataResponse
     */
    public function addRoomMessage($message): DataResponse {
        $this->logger->info(sprintf('MessageController::addRoomMessage %s', json_encode($message, JSON_PRETTY_PRINT)));
        /////////////////////////////////////////////////////////////////////////////////////
        $files  = $message['files'];
        $idUser = $message['idUser'];
        $idRoom = $message['idRoom'];

        unset($message['idUser']);
        unset($message['idRoom']);
        unset($message['files']);
        ///////////////////////////////////////
        $ruDB = RoomUser::fromParams([ 'idUser' => $idUser, 'idRoom' => $idRoom]);
        $ruDB = $this->ru_db->findRoomUser($ruDB);
        ///////////////////////////////////////
        $message['idRoomUser'] = $ruDB->getId();
        ///////////////////////////////////////
        $msgDB = Message::fromParams($message);
        $msgDB = $this->msg_db->insert($msgDB);
        $msgDB = $this->msg_db->findMessage($msgDB->getId());
        ///////////////////////////////////////
        $msgDB->files = $this->_storeMessageAttachment($msgDB->getId(), $idRoom, $idUser, $files);
        ///////////////////////////////////////
        // $this->_addMessageStream($msgDB);
        // $this->_getBackgroundJobStatus();
        // $this->_test_run();
        ///////////////////////////////////////
        return new DataResponse([$msgDB]);
    }

    /**
     * Store message in DB
     *
     * @param array $request
     * @return DataResponse
     */
     public function testCompletion($message): DataResponse {
        $this->logger->info(sprintf('MessageController::testCompletion %s', json_encode($message, JSON_PRETTY_PRINT)));
        $this->_test_run();
        return new DataResponse([$msgDB]);
    }

    /**
     * Get all messages
     *
     * @param array $request
     * @return DataResponse
     */
     public function getRoomMessages($request): DataResponse {
        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('MessageController::getRoomMessages %s', json_encode($request, JSON_PRETTY_PRINT)));

        $msgs = $this->msg_db->findRoomMessagesQ($request);
        foreach($msgs as $msg) {
            try{
                $this->_getMessageSeenReactionFiles($msg);
            }catch(\Throwable $ex){
            }
        };
        return new DataResponse($msgs);
    }

    /**
     * Get all newest messages
     *
     * @param array $values
     * @return DataResponse
     */
     public function getNewestMessages($userId): DataResponse {
        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('MessageController::getNewestMessages(userId = %s)', $userId));

        $msgs = [];
        $rooms = $this->ru_db->findRoomsByUserId([ 'userId' => $userId]);
        foreach($rooms as $room) {
            try{
                $msgDB = $this->msg_db->findNewestMessages($room->getIdRoom());
                $msgs[]= $msgDB;
            }catch(\Throwable $ex){
            }
        };
        return new DataResponse($msgs);
    }

    /**
     * Update message seen
     *
     * @param array $request
     * @return DataResponse
     */
     public function updateMessageSeen($request): DataResponse {
        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('MessageController::updateMessageSeen %s', json_encode($request, JSON_PRETTY_PRINT)));
        $reactDB = Reaction::fromParams([ 'idMsg'  => $request['messageId'],
                                          'idUser' => $request['userId'],
                                          'seen'   => $request['lastUpdated']]);
        $this->rc_db->insert($reactDB);
        $msgDB = $this->_getMessageSeenReactionFiles($request['messageId']);
        return new DataResponse([$msgDB]);
    }

    /**
     * Update message  reaction
     *
     * @param array $request
     * @return DataResponse
     */
     public function updateMessageReaction($request): DataResponse {
        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('MessageController::updateMessageReaction %s', json_encode($request, JSON_PRETTY_PRINT)));
        $action  = $request['action'];
        $reactDB = Reaction::fromParams(['idMsg'    => $request['messageId'],
                                         'idUser'   => $request['userId'],
                                         'reaction' => $request['reaction'],
                                         'seen'     => $request['lastUpdated']]);

        if ($action == 'remove'){
            $reactDB = $this->rc_db->deleteReaction($reactDB);
        } else {
            $reactDB = $this->rc_db->insert($reactDB);
        };

        $msgDB = $this->_getMessageSeenReactionFiles($request['messageId']);
        return new DataResponse([$msgDB]);
    }

	/**
     * 
     * Send message to LLaMa and stream response.
     * 
	 * @param $msg Message
	 * @return void
	 */
    public function streamMessage($msg) {
        $this->logger->info('MessageController::streamMessage()');
    }

    /**
     * Update message  reaction
     *
     * @param array $request
     * @return DataResponse
     */
    protected function _getAttachmentFileName($file_name, $file_extn): String {
        /////////////////////////////////////////////////////////////////////////////////////
        return $file_name . '.' . $file_extn;
    }

    /**
     * Update message  reaction
     *
     * @param array $request
     * @return DataResponse
     */
    protected function _storeMessageAttachment($idMsg, $idRoom, $idUser, $files): Array {
        // $this->logger->info(sprintf('MessageController::_storeMessageAttachment %s', json_encode($files, JSON_PRETTY_PRINT)));
        $FilesOut = [];
        /////////////////////////////////////////////////////////////////////////////////////
        foreach($files as $file) {
            ///////////////////////////////////
            unset($file['blob']);
            ///////////////////////////////////
            $file_extn = $file['extension'];
            $file_name = $file['name'];
            $file_nm01 = $this->_getAttachmentFileName($file_name, $file_extn);
            ///////////////////////////////////
            $node = $this->file->createFindRoomFile($this->user->getUserId(), $idRoom, $idUser, $file_name);
            $fl   = $node->nodeExists($file_nm01) ? $node->get($file_nm01)  : $node->newFile($file_nm01);
            ///////////////////////////////////
            $file_url = sprintf('https://office.geoid.ca/apps/files/?dir=/%s/room-%d/user-%s/%s&openfile=%d',
                                            'LLaMa', $idRoom, $idUser, $file_name, $fl->getId());
            if (empty($file['url'])) {
                $file['url'] = $file['localUrl'];
            }
            $file['localUrl'] = $file_url;
            ///////////////////////////////////
            $file['idMsg'] = $idMsg;
            $flDB = File::fromParams($file);
            $flDB = $this->fl_db->insert($flDB);
            ///////////////////////////////////
            // $this->logger->info(sprintf('MessageController::addRoomMessage::file_url %s', $file_url));
            // $this->logger->info(sprintf('MessageController::addRoomMessage::file %s', json_encode($flDB, JSON_PRETTY_PRINT)));
            // _url('https://office.geoid.ca/apps/files/?dir=/LLaMa/4/foothills-schedule&fileid=223298')
            ///////////////////////////////////
            $FilesOut[] = $flDB;
        }
        return $FilesOut;
    }

    /**
     * Update message  reaction
     *
     * @param array $request
     * @return DataResponse
     */
    protected function _deleteMessageAttachment($idMsg, $files): int {
        // $this->logger->info(sprintf('MessageController::_deleteMessageAttachment %s', json_encode($files, JSON_PRETTY_PRINT)));
        $idDel01 = array_column($files, 'id');
        return $this->fl_db->deleteIncludingIds($idMsg, $idDel01);
    }

    /**
     * Update message  reaction
     *
     * @param array $request
     * @return DataResponse
     */
    protected function _getMessageSeenReactionFiles($msg): Object {
        /////////////////////////////////////////////////////////////////////////////////////
        // $this->logger->info(sprintf('MessageController::_getMessageSeenReactionFiles %s', json_encode($param, JSON_PRETTY_PRINT)));

        // /////////////////////////////////////////////
        // check if $msg is int or object
        if (is_int($msg)) {
            $msgDB = $this->msg_db->findMessage($msg);
        } else {
            $msgDB = $msg;
        };
        // ////////////////////////////////////////////////
        $reactDB = $this->rc_db->findReactions($msgDB->getId());
        $seenDB  = $this->rc_db->findSeens($msgDB->getId());
        $filesDB = $this->fl_db->findFiles($msgDB->getId());
        // ////////////////////////////////////////////////
        if(count($reactDB) > 0) {
            $msgDB->setReactions($reactDB);
        }
        if(count($seenDB) > 0) {
            $msgDB->setSeens($seenDB);
        }
        if(count($filesDB) > 0) {
            $msgDB->setFiles($filesDB);
        }
        return $msgDB;
    }

	/**
     * Add StreamMessageJob
     * 
	 * @param $msg Message
	 * @return void
	 */
    protected function _addMessageStream(Message $msg) {
        $this->jobList->add(StreamMessageJob::class, ['msg' => $msg]);
    }

	/**
     * 
	 * @param $msgId
	 * @return void
	 */
	protected function _getBackgroundJobStatus() {
		// iterate job list
		foreach ($this->jobList->getJobs(StreamMessageJob::class, Null, 0) as $job) {
			if ($job->getArgument()['msg']) {
			}
		}

	}

    protected function _test_run() {
        $url = 'https://llama.geoid.ca/api/v1/completion/';

        $client = new \GuzzleHttp\Client();
        $json = json_encode([   
            'prompt'     => 'Question: What are the names of the planets in the solar system? Answer:',
            'max_tokens' => 1024,
            'stream'     => True,
        ]);
        $headers = ['Content-type' => 'application/json', 'Authorization' => 'Bearer 72290464-fdbd-4ce6-aa6c-9ac643740df1'];

        $this->logger->info('MessageController::_test_run');
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
            $this->logger->info($e);
        }

    }

}
