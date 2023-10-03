import axios from '@nextcloud/axios'

import { generateUrl } from '@nextcloud/router'
import { getRndInteger } from '../utils/random.js'
// import { formatTimestamp } from '../utils/dates.js'

import { Subscription, asyncScheduler, Subject, from, of } from 'rxjs'
import { observeOn, takeUntil, mergeMap, delay, repeat } from 'rxjs/operators'

export default class MessageEventService {

    static instance = null

    _processMessageUpdate_room = () => {}

    onMessageUpdate = null
    onMessageDelete = null
    onMessageRoom = null
    onMessageSend = null
    onLastMessage = null

    /***********************************************************************
     * Constructor
     *
     * @return {void}
     */
     constructor() {
        console.log('RoomEventService::constructor()')

        this.onMessageDelete = new Subject()
        this.onMessageUpdate = new Subject()
        this.onMessageSend = new Subject()
        this.onMessageRoom = new Subject()
        this.onLastMessage = new Subject()
    }

    /***********************************************************************
     * Create global instance
     *
     * @return {MessageEventService::instance}
     */
    static getInstance() {
        if (!MessageEventService.instance) {
            MessageEventService.instance = new MessageEventService()
        }
        return MessageEventService.instance
    }

    /***********************************************************************
     * Signal message was seen
     *
     * @param {currentUserId} Long current user id
     * @param {messageId} Long current message id
     * @param {file} Object File information
     *
     * @return {void}
     */
     deleteMessageFile(messageId, roomId, userId, file) {
        console.log('MessageEventService::deleteMessageFile()', messageId, roomId, userId, file)

        const url = generateUrl('/apps/llamavirtualuser/msg-file-del')

        const request = { messageId, roomId, userId, file }
        const data = {
            request,
        }

        axios({
            method: 'put',
            url,
            data,
            timeout: 8000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                console.log(response.status)
            }
            return response.status
        })
        .catch((error) => {
            console.error(error)
            return -1
        })

    }

    /***********************************************************************
     * Signal message was seen
     *
     * @param {messageId} Long current message id
     * @param {newMessage} Object updated message
     *
     * @return {void}
     */
     deleteMessage(messageId, roomId, date) {
        console.log('MessageEventService::deleteMessage()', messageId, roomId, date)

        const url = generateUrl('/apps/llamavirtualuser/msg-del')

        const request = { messageId, roomId, date }
        const data = {
            request,
        }

        axios({
            method: 'put',
            url,
            data,
            timeout: 8000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                console.log(response.status)
                return
            }
            const messages = this._updateTimeZone(response.data)
            this._scheduleMessages(messages, this.onMessageDelete)
        })
        .catch((error) => {
            console.error(error)
            return -1
        })

    }

    /***********************************************************************
     * Signal message was seen
     *
     * @param {messageId} Long current message id
     * @param {newMessage} Object updated message
     *
     * @return {void}
     */
     updateMessageFile(messageId, roomId, userId, file) {
        console.log('MessageEventService::updateMessageFile()', messageId, roomId, userId, file)

        const url = generateUrl('/apps/llamavirtualuser/msg-file-update')

        const request = { messageId, roomId, userId, file }
        const data = {
            request,
        }

        axios({
            method: 'put',
            url,
            data,
            timeout: 8000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                console.log(response.status)
            }
            return response.status
        })
        .catch((error) => {
            console.error(error)
            return -1
        })

    }

    /***********************************************************************
     * Message reaction was send
     *
     * @param {messageId} Long current message id
     * @param {roomId} Long current room id
     * @param {file} Object file
     * @param {_progress} Function Progress callback.
     * @param {_url} Function Progress end. URL callback.
     * @param {_error} Function Error callback.
     *
     * @return {void}
     */
     uploadMessageFile(messageId, roomId, userId, file, _progress,  _url, _error) {
        // console.log('MessageEventService::uploadMessageFile()', progress)

        const url = generateUrl('/apps/llamavirtualuser/msg-file')
        // const CHUNK_SIZE = 1024 * 512
        const CHUNK_SIZE = 1024 * 1024
        let offset = 0
        // //////////////////////////////////////////////////////////
        const fr = new FileReader()
        fr.onload = function() {

            const request = { roomId, userId, file, offset, bytes: fr.result }
            const data = {
                request,
            }

            axios({
                method: 'put',
                url,
                data,
                timeout: 8000,
                headers: {
                    'Content-Type': 'application/json',
                },
            })
            .then((response) => {
                if (response.status !== 200) {
                    console.log(response.status)
                    return
                }
                _progress({ file, progress: Math.round((offset / file.size) * 100) })
                offset += CHUNK_SIZE
                seek()
            })
            .catch((error) => {
                console.error(error)
                _error()
            })

        }

        fr.onerror = function() {
            console.log('MessageEventService::uploadFile()::error')
            _error()
        }

        seek()

        function seek() {
            if (offset >= file.size) {
                _progress({ file, progress: 100 })
                _url(file.localUrl)
                return
            }
            const slice = file.blob.slice(offset, offset + CHUNK_SIZE)
            fr.readAsDataURL(slice)
        }

    }

    /***********************************************************************
     * Message reaction was send
     *
     * @param {roomId} Long current room id
     * @param {messageId} Long current message id
     * @param {userId} Long current user id
     * @param {reaction} Text reaction
     * @param {action} Boolean "add" or "remove"
     *
     * @return {void}
     */
     updateMessageReactions(messageId, userId, reaction, action, lastUpdated) {
        console.log('MessageEventService::updateMessageReactions()', messageId, userId, reaction, action, lastUpdated)
        const url = generateUrl('/apps/llamavirtualuser/msg-react')
        const request = { messageId, userId, reaction, action, lastUpdated }
        const data = {
            request,
        }

        return axios({
            method: 'put',
            url,
            data,
            timeout: 8000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                // test for status you want, etc
                console.log(response.status)
                return -1
            }
            const messages = this._updateTimeZone(response.data)
            this._scheduleMessages(messages, this.onMessageUpdate)
        })
        .catch((error) => {
            console.error(error)
            return -1
        })
    }

    /***********************************************************************
     * Signal message was seen
     *
     * @param {messageId} Long current message id
     * @param {userId} Long user id
     * @param {lastUpdated} Datetime room last update
     *
     * @return {void}
     */
     updateMessageSeen(messageId, userId, lastUpdated) {
        console.log('MessageEventService::updateMessageSeen()', messageId, userId, lastUpdated)
        const url = generateUrl('/apps/llamavirtualuser/msg-seen')
        const request = { messageId, userId, lastUpdated }
        const data = {
            request,
        }

        return axios({
            method: 'put',
            url,
            data,
            timeout: 8000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                // test for status you want, etc
                console.log(response.status)
                return -1
            }
            // this._scheduleRoomMessages(response.data)
            // console.log('MessageEventService::addRoomMessage()', message)
            // this.onMessageSend.next(response.data)
            return response.data
        })
        .catch((error) => {
            console.error(error)
            return -1
        })
    }

    /***********************************************************************
     * Return messages query
     *
     * @param {userId} Long User id
     * @param {messagesPerPage} Long Messages per page
     * @param {startMessage} Long Total loaded messages
     *
     * @return {Object}
     */
     messagesQuery(roomId, messagesPerPage, startMessage) {
        // console.log('MessageEventService::messagesQuery()', roomId, userId, messagesPerPage, messagesLoadedCount)

        return { roomId, messagesPerPage, startMessage }
    }

    /***********************************************************************
     * Add new message to room
     *
     * @param {message} Object message to add
     *
     * @return {Promise}
     */
     async updateRoomMessage(message) {
        console.log('MessageEventService::updateRoomMessage()', message)

        const url = generateUrl('/apps/llamavirtualuser/msg-update')
        const data = {
            message,
        }

        return axios({
            method: 'put',
            url,
            data,
            timeout: 8000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                // test for status you want, etc
                console.log(response.status)
                return -1
            }
            const messages = this._updateTimeZone(response.data)
            this._scheduleMessages(messages, this.onLastMessage)
            this._scheduleMessages(messages, this.onMessageUpdate)
            this._scheduleMessages(messages, this.onMessageSend)
        })
        .catch((error) => {
            console.error(error)
            return -1
        })
    }

    /***********************************************************************
     * Signal message was seen
     *
     * @param {msg} Long current user id
     *
     * @return {void}
     */
     completeMessagesPHP(message) {
        console.log('MessageEventService::completeMessagesPHP()', message)

        const url = generateUrl('/apps/llamavirtualuser/msg-test-comp')

        const data = {
            message,
        }

        axios({
            method: 'put',
            url,
            data,
            timeout: 1000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                console.log(response.status)
            }
            return response.status
        })
        .catch((error) => {
            console.error(error)
            return -1
        })

    }

    /***********************************************************************
     * Test function to imitate long call
     *
     * @param {value} pass some value
     *
     * @return {Promise}
     */
     async completeMessagesAXIOS(message) {
        console.log('MessageEventService::completeMessagesAXIOS()', message)

        const url = 'https://llama.geoid.ca/api/v1/completion/'
        const data = {
            prompt: 'Question: What are the names of the planets in the solar system? Answer:',
            max_tokens: 1024,
            stream: true,
        }

        // axios.defaults.headers.options['Access-Control-Allow-Origin'] = '*'
        // axios.defaults.headers.post['Access-Control-Allow-Origin'] = '*'

        console.log('MessageEventService::Step 01')
        const response = await axios({
            method: 'post',
            url,
            data,
            headers: {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': '*',
                Authorization: 'Bearer 72290464-fdbd-4ce6-aa6c-9ac643740df1',
            },
            responseType: 'stream'
        })
        console.log('MessageEventService::Step 02')
        const stream = response.data

        console.log('MessageEventService::Step 03')
        stream.on('data', data => {
            console.log(data)
        })

        console.log('MessageEventService::Step 04')
        stream.on('end', () => {
            console.log('stream done')
        })
    }

    /***********************************************************************
     * Add new message to room
     *
     * @param {message} Object message to add
     *
     * @return {Promise}
     */
     async sendRoomMessage(message) {
        console.log('MessageEventService::sendRoomMessage()', message)

        const url = generateUrl('/apps/llamavirtualuser/msg-send')
        const data = {
            message,
        }

        return axios({
            method: 'put',
            url,
            data,
            timeout: 8000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                // test for status you want, etc
                console.log(response.status)
                return -1
            }
            const messages = this._updateTimeZone(response.data)
            this._scheduleMessages(messages, this.onLastMessage)
            this._scheduleMessages(messages, this.onMessageRoom)
            this._scheduleMessages(messages, this.onMessageSend)
        })
        .catch((error) => {
            console.error(error)
            return -1
        })
    }

    /***********************************************************************
     * Get messages for room
     *
     * @param {request} Object Query {roomId, messagesPerPage, messagesStart}
     *
     * @return {Object}
     */
     async getRoomMessages(request) {
        console.log('MessageEventService::getRoomMessages()', request)

        const url = generateUrl('/apps/llamavirtualuser/msg-get')
        const data = {
            request,
        }

        return axios({
            method: 'put',
            url,
            data,
            timeout: 8000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                // test for status you want, etc
                console.log(response.status)
            }
            const messages = this._updateTimeZone(response.data)
            this._scheduleMessages(messages, this.onMessageRoom)
        })
        .catch((error) => {
            console.error(error)
        })
    }

    /***********************************************************************
     * Get last message for rooms
     *
     *
     * @return {Promise}
     */
     async getRoomsLastMessage(userId) {
        console.log('MessageEventService::getRoomsLastMessage()')

        const url = generateUrl('/apps/llamavirtualuser/msg-last')
        const data = {
            userId,
        }
        return axios({
            method: 'put',
            url,
            data,
            timeout: 8000,
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then((response) => {
            if (response.status !== 200) {
                // test for status you want, etc
                console.log(response.status)
            }
            // console.log('MessageEventService::getRoomsLastMessage()', response.data)
            const messages = this._updateTimeZone(response.data)
            this._scheduleMessages(messages, this.onLastMessage)
        })
        .catch((error) => {
            console.error(error)
        })
    }

    /***********************************************************************
     * Schedule execution over last (newest) messages
     *
     * @param {messages} message array to process
     *
     * @return {Subscription}
     */
     _scheduleMessages(messages, eventMessage) {
        // console.log('MessageEventService::scheduleLastMessages()')

        if (messages.length === 0) {
            eventMessage?.next({ msgLen: 0, msg: {} })
            return Subscription.EMPTY
        } else {
            const observable = from(messages).pipe(observeOn(asyncScheduler))
            return observable
                .subscribe(
                    (msg) => { eventMessage?.next({ msgLen: messages.length, msg }) },
                    (err) => { console.error('Gomething wrong occurred:', err) },
                )
        }
    }

    /***********************************************************************
     * Update timestamps to current timezone from UTC zone (DB)
     *
     * @param {messages} Array message array to process
     *
     * @return {messages} Array
     */
     _updateTimeZone(messages) {
        // console.log('MessageEventService::_updateTimeZone()')
        const dateOff = new Date()
        const offset = dateOff.getTimezoneOffset() * 60

        messages.forEach((message) => {
            if (message?.timestamp) {
                message.timestamp.seconds -= offset
            }
        })

        return messages
    }

    /***********************************************************************
     * Schedule event loop
     *
     * @param {stopTimeout} event loop will stop when stopTimeout is signaled
     * @param {delay} event loop deleay
     *
     * @return {Subscription}
     */
     scheduleStart(stopTimeout, _delay) {
        console.log('MessageEventService::scheduleStart()', stopTimeout)

        return of(1).pipe(
            takeUntil(stopTimeout),
            mergeMap(this._doRequest.bind(this)),
            delay(_delay),
            // repeat steps above
            repeat(),
        ).subscribe(response => {
            // console.log('MessageEventService::scheduleStart()::response', response)
            this._scheduleMessages(response, this.onLastMessage)
        })
    }

    /***********************************************************************
     * Request function call periodically
     *
     * @return {Array}
     */
     async _doRequest() {
        // console.log('MessageEventService::doRequest()')

        const url = generateUrl('/apps/llamavirtualuser/msg-last')
        try {
            const response = await axios({
                url,
                method: 'get',
                timeout: 8000,
                headers: {
                    'Content-Type': 'application/json',
                },
            })
            if (response.status !== 200) {
                // test for status you want, etc
                console.log(response.status)
                return []
            }
            // Don't forget to return something
            return response.data
        } catch (err) {
            console.error(err)
            return []
        }
    }

    /***********************************************************************
     * Test function to imitate long call
     *
     * @param {value} pass some value
     *
     * @return {Promise}
     */
     async _asyncTest(value) {
        console.log('MessageEventService::_asyncTest()', value)

        const delay = getRndInteger(2000, 5000)
        return new Promise((resolve) => {
            setTimeout(() => {
                console.log('MessageEventService::_asyncTest()', 'Test 001', delay)
                resolve('Test message 001')
            }, delay)
        })
    }

}
