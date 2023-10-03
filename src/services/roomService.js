import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { Subscription, asyncScheduler, Subject, from } from 'rxjs'
import { observeOn } from 'rxjs/operators'

export default class RoomEventService {

    static instance = null

    onTypingUser = null
    onRoomUpdated = null
    onRoomDeleted = null
    onRoomCreated = null
    onRoomProcess = null

    /***********************************************************************
     * Constructor
     *
     * @return {void}
     */
     constructor() {
        console.log('RoomEventService::constructor()')

        this.onTypingUser = new Subject()
        this.onRoomUpdated = new Subject()
        this.onRoomDeleted = new Subject()
        this.onRoomCreated = new Subject()
        this.onRoomProcess = new Subject()
    }

    /***********************************************************************
     * Create global instance
     *
     * @return {RoomEventService::instance}
     */
     static getInstance() {
        if (!RoomEventService.instance) {
            RoomEventService.instance = new RoomEventService()
        }
        return RoomEventService.instance
    }

    /***********************************************************************
     * Create new room
     *
     * @param {users} array current message id
     * @param {lastUpdated} Object updated message
     *
     * @return {void}
     */
     createRoom(users, lastUpdated) {
        console.log('RoomEventService::createRoom()', users, lastUpdated)

        const url = generateUrl('/apps/llamavirtualuser/room-create')

        const request = { users, lastUpdated }
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
            this._scheduleRooms(response.data, this.onRoomCreated)
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
     deleteRoom(roomId) {
        console.log('RoomEventService::deleteRoomMessages()', roomId)

        const url = generateUrl('/apps/llamavirtualuser/room-delete')

        const request = { roomId }
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
            this._scheduleRooms(response.data, this.onRoomDeleted)
        })
        .catch((error) => {
            console.error(error)
            return -1
        })

    }

    /***********************************************************************
     * Signal room update
     *
     * @param {roomId} Int current room id
     * @param {lastUpdated} Datetime time for room last update
     *
     * @return {void}
     */
     async updateRoomSeen(roomId, lastUpdated) {
        // console.log('RoomEventService::updateRoomSeen()', Math.round(lastUpdated.getTime() / 1000))

        const url = generateUrl('/apps/llamavirtualuser/room-update')
        const request = { roomId, lastUpdated }
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
            // console.log('RoomEventService::updateRoomSeen()::response', response.data.lastUpdated.seconds)
            this._scheduleRooms(response.data, this.onRoomUpdated)
        })
        .catch((error) => {
            console.error(error)
        })
    }

    /***********************************************************************
     * Signal user typing status
     *
     * @param {roomId} Int Room where message typing began
     * @param {userId} Int User id
     * @param {message} String ["add", "remove"] message action
     *
     * @return {void}
     */
     async updateRoomTypingUsers(roomId, userId, message) {
        // console.log('RoomEventService::updateRoomTypingUsers()', roomId, userId, message)

        this.onTypingUser?.next({ roomId, userId, message })
    }

    /***********************************************************************
     * Return messages query
     *
     * @param {userId} Int current user id
     * @param {roomsPerPage} Int messages per page
     * @param {startRoom} Int total loaded messages
     *
     * @return {Object} Query object
     */
     roomsQuery(userId, roomsPerPage, startRoom) {
        // console.log('RoomEventService::roomsQuery()', userId, roomsPerPage, startRoom)

        return { userId, roomsPerPage, startRoom }
    }

    /***********************************************************************
     * Get messages for room
     *
     * @param {request} Object Query { userId, roomsPerPage, startRoom }
     *
     * @return {Object}
     */
     async fetchMoreRooms(request) {
        // console.log('RoomEventService::fetchMoreRooms()', request)

        const url = generateUrl('/apps/llamavirtualuser/room-find')
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
            // console.log('RoomEventService::fetchMoreRooms()::response', response.data)
            const rooms = this._updateTimeZone(response.data)
            this._scheduleRooms(rooms, this.onRoomProcess)
        })
        .catch((error) => {
            console.error(error)
        })
    }

    /***********************************************************************
     * Schedule execution over room messages
     *
     * @param {rooms} Array Rooms to process
     *
     * @return {Subscription}
     */
     _scheduleRooms(rooms, eventRoom) {
        // console.log('RoomEventService::_scheduleMessages()')
        if (rooms.length === 0) {
            // console.log('RoomEventService::_scheduleMessages():', 0, {})
            eventRoom?.next({ roomsLen: 0, room: {} })
            return Subscription.EMPTY
        } else {
            const observable = from(rooms).pipe(observeOn(asyncScheduler))
            return observable.subscribe(
                    (room) => { eventRoom?.next({ roomsLen: rooms.length, room }) },
                    (err) => { console.error('Gomething wrong occurred:', err) },
                )
        }
    }

    /***********************************************************************
     * Update timestamps to current timezone from UTC zone (DB)
     *
     * @param {rooms} Array Rooms array to process
     *
     * @return {rooms} Array
     */
     _updateTimeZone(rooms) {
        // console.log('MessageEventService::scheduleLastMessages()')
        const dateOff = new Date()
        const offset = dateOff.getTimezoneOffset() * 60

        rooms.forEach(room => {
            room.lastUpdated.seconds -= offset
        })

        return rooms
    }

}
