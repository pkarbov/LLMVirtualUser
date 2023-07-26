import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { getRndInteger, random } from '../utils/random.js'

import { of, asyncScheduler, from } from 'rxjs'
import { observeOn, takeUntil, mergeMap, delay, repeat } from 'rxjs/operators'

export default class MessageEventService {

    static _count = 0
    static instance = null

    _rooms = []
    _lastMessage_completed = () => {}
    _lastMessage_room = () => {}

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
	* Schedule event loop
	*
	* @param {stopTimeout} event loop will stop when stopTimeout is signaled
	* @param {delay} event loop deleay
	* @return {Subscription}
	*/
    scheduleStart(stopTimeout, _delay) {
        console.log('MessageEventService::scheduleStart()', stopTimeout)

        return of(1).pipe(
            takeUntil(stopTimeout),
            mergeMap(this.doRequest.bind(this)),
            delay(_delay),
            // repeat steps above
            repeat(),
        ).subscribe(response => {
            // console.log('MessageEventService::scheduleStart()::response', response)
            this.scheduleLastMessages(response)
        })
	}

   /***********************************************************************
	* Request function call periodically
	*
	* @return {Observable}
	*/
	async doRequest() {
	    // console.log('MessageEventService::doRequest()')

	    // const array2 = getRandomArray(1, 5)
        // const array3 = array2.map(i => this._testMessage(i))
        // return of(array3).pipe(delay(getRndInteger(2500, 3500)))
        // return of(array2).pipe(delay(getRndInteger(2500, 3500)))
        const url = generateUrl('/apps/llamavirtualuser/msg-last')
        try {
           const res = await axios({
                url,
                method: 'get',
                timeout: 8000,
                headers: {
                    'Content-Type': 'application/json',
                },
            })
            if (res.status !== 200) {
                // test for status you want, etc
                console.log(res.status)
                return []
            }
            // Don't forget to return something
            return res.data
        } catch (err) {
            console.error(err)
            return []
        }
    }

   /***********************************************************************
	* Schedule execution over messages
	*
	* @param {messages} message array to process
	* @return {Subscription}
	*/
    scheduleLastMessages(messages) {
        // console.log('MessageEventService::scheduleLastMessages()')

        const observable = from(messages).pipe(observeOn(asyncScheduler))
        return observable
            .subscribe(
                (msg) => { this._lastMessage_room(msg) },
                (err) => { console.error('Gomething wrong occurred:', err) },
                this._lastMessage_completed(),
            )
	}

   /***********************************************************************
	* Test function create message
	*
	* @return {Message}
	*/
    _testMessage(id) {
        const message = {
            id: MessageEventService._count++,
            sender_id: getRndInteger(1, 5),
            content: 'Test 001 ' + random(5),
            timestamp:  Date.now(),
        }
        // console.log('MessageEventService::_testMessage()::message: ', message)
        const roomId = getRndInteger(1, 500)
        return { roomId, message }
	}

   /***********************************************************************
	* Test function to imitate long call
	*
	* @param {value} pass some value
	* @return {Promise}
	*/
    async _asyncTest(value) {
        console.log('MessageEventService::delayTest()', value)
        const delay = getRndInteger(2000, 5000)
        return new Promise((resolve) => {
            setTimeout(() => {
                console.log('MessageEventService::_asyncTest()', 'Test 001', delay)
                resolve('Test message 001')
            }, delay)
        })
	}

}
