import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import { Subject } from 'rxjs'

export default class UserEventService {

    static instance = null
    onLineUsers = null

    /***********************************************************************
     * Constructor
     *
     * @return {void}
     */
     constructor() {
        console.log('MessageEventService::constructor()')

        this.onLineUsers = new Subject()
    }

    /***********************************************************************
     * Create global instance
     *
     * @return {UserEventService}
     */
     static getInstance() {
        if (!UserEventService.instance) {
            UserEventService.instance = new UserEventService()
        }
        return UserEventService.instance
    }

    /***********************************************************************
     * Update user online status
     *
     * @param {userId} long current user id
     * @param {lastChanged} Datetime for status last change
     *
     * @return {void}
     */
     updateUserOnlineStatus(userId, lastChanged) {
        // console.log('MessageEventService::updateUserOnlineStatus()', userId, lastChanged)

        this.onLineUsers?.next({ userId, lastChanged })
    }

    /***********************************************************************
     * Get messages for room
     *
     * @param {userId} Long current user id
     *
     * @return {Promise}
     */
     async getUser(userId) {
        // console.log('MessageEventService::getMessages()', userId, messagesPerPage, messagesLoadedCount)

        const url = generateUrl('/apps/llamavirtualuser/user-find')
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
                return {}
            }
            return response.data
        })
        .catch((error) => {
            console.error(error)
            return {}
        })
    }

    /***********************************************************************
     * Get messages for room
     *
     * @param {userId} Long User id
     *
     * @return {Promise}
     */
     async getUserAsync(userId) {
        // console.log('MessageEventService::getMessages()', userId, messagesPerPage, messagesLoadedCount)

        const url = generateUrl('/apps/llamavirtualuser/user-find')
        const data = {
            userId,
        }
        try {
            const response = await axios({
                method: 'put',
                url,
                data,
                timeout: 8000,
                headers: {
                    'Content-Type': 'application/json',
                },
            })

            if (response.status !== 200) {
                // test for status you want, etc
                console.log(response.status)
                return {}
            }

            return response.data
        } catch (err) {
            console.error(err)
            return {}
        }
    }

}
