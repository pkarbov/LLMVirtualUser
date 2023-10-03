<template>
  <div class="window-container" :class="{ 'window-mobile': isDevice }">
    <!------------------------------------------------------------------------->
    <!-------------------           Add NEW Room            ------------------->
    <!------------------------------------------------------------------------->
    <!-- form v-if="addNewRoom" @submit.prevent="event_createRoom">
        <button class="button-image-ok" type="submit" :disabled="disableForm" />
        <button class="button-image-close" @click="addNewRoom = false" />
        <template v-if="addTestData">
            <button class="button-data" @click="addNewRoom = false; addTestData()" />
        </template>
    </form -->
    <!------------------------------------------------------------------------->
    <!-------------------               Chat                ------------------->
    <!------------------------------------------------------------------------->
    <vue-advanced-chat
        ref="chatWindow"
        :height="screenHeight"
        :theme="theme"
        :styles="JSON.stringify(styles)"
        :current-user-id="currentUserId"
        :room-id="roomId"
        :rooms="JSON.stringify(loadedRooms)"
        :loading-rooms="loadingRooms"
        :rooms-loaded="roomsLoaded"
        :messages="JSON.stringify(messages)"
        :messages-loaded="messagesLoaded"
        :room-message="roomMessage"
        :room-actions="JSON.stringify(roomActions)"
        :menu-actions="JSON.stringify(menuActions)"
        :message-selection-actions="JSON.stringify(messageSelectionActions)"
        :message-actions="JSON.stringify(messageActions)"
        :templates-text="JSON.stringify(templatesText)"
        :emoji-data-source="emojiDataSource"
        :show-audio="showAudio"
        :show-files="showFiles"

        @fetch-more-rooms="event_fetchMoreRooms"
        @fetch-messages="event_fetchMessages($event.detail[0])"
        @send-message="event_sendMessage($event.detail[0])"
        @edit-message="event_editMessage($event.detail[0])"
        @delete-message="event_deleteMessage($event.detail[0])"
        @open-file="event_openFile($event.detail[0])"
        @open-user-tag="event_openUserTag($event.detail[0])"
        @add-room="event_createRoom($event.detail[0])"
        @room-action-handler="event_menuActionHandler($event.detail[0])"
        @menu-action-handler="event_menuActionHandler($event.detail[0])"
        @message-selection-action-handler="event_messageSelectionActionHandler($event.detail[0])"
        @send-message-reaction="event_sendMessageReaction($event.detail[0])"
        @typing-message="event_typingMessage($event.detail[0])"
        @toggle-rooms-list="event_showDemoOptions($event.detail[0].opened)">
        <!-- <div
            v-for="message in messages"
            :slot="'message_' + message._id"
            :key="message._id"
        >
            New message container
        </div> -->
    </vue-advanced-chat>
  </div>
</template>

<script>

import * as firestoreService from './../../database/firestore.js'
// import * as firebaseService from './../../database/firebase.js'
// import * as storageService from './../../database/storage.js'

import { parseTimestamp, formatTimestamp } from './../../utils/dates.js'

import MessageEventService from '../../services/messageService.js'
import UserEventService from '../../services/userService.js'
import RoomEventService from '../../services/roomService.js'

import logoAvatar from '../../icons/logo.png'

import { loadState } from '@nextcloud/initial-state'
import { register } from 'vue-advanced-chat'

import { showSuccess, showError } from '@nextcloud/dialogs'

register()

export default {
    name: 'ChatContainer1',
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
    props: {
        theme: { type: String, required: true },
        isDevice: { type: Boolean, required: true },
        // addTestData: { type: Function, required: true },
    },
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
    emits: ['show-demo-options'],
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
    setup() {},
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
    data() {
        return {
            state: loadState('llamavirtualuser', 'chat-config'),
            emojiDataSource: 'img/data.json',
            showAudio: 'false',
            showFiles: 'false',

            allUsers: [],
            rooms: [],
            roomId: '',
            roomsLoaded: false,
            loadingRooms: true,
            roomsPerPage: 15,
            roomsLoadedCount: 0,
            selectedRoom: null,

            messages: [],
            messagesPerPage: 20,
            messagesLoadedCount: 0,
            messagesLoaded: false,
            messagesLastCount: 0,

            roomMessage: '',
            typingMessageCache: { context: '', timestamp: 0 },
            disableForm: false,
            addNewRoom: null,

            roomActions: [
                { name: 'deleteRoom', title: 'Delete Room' },
            ],
            menuActions: [
                { name: 'deleteRoom', title: 'Delete Room' },
            ],
            messageSelectionActions: [
                { name: 'deleteMessages', title: 'Delete' },
            ],
            messageActions: [
                { name: 'replyMessage', title: 'Reply' },
                { name: 'editMessage', title: 'Edit' },
                { name: 'deleteMessage', title: 'Delete', onlyMe: true },
                { name: 'selectMessages', title: 'Select' },
            ],

            // eslint-disable-next-line vue/no-unused-properties
            styles: { container: { borderRadius: '4px' } },
            templatesText: [
                {
                    tag: 'help',
                    text: 'This is the help',
                },
                {
                    tag: 'action',
                    text: 'This is the action',
                },
                {
                    tag: 'action 2',
                    text: 'This is the second action',
                },
            ],

            currentUserId: -1,
            llamaUser: {},

            msgService: MessageEventService.getInstance(),
            userService: UserEventService.getInstance(),
            roomService: RoomEventService.getInstance(),
            // ,dbRequestCount: 0
        }
    },
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
    computed: {
        loadedRooms() {
            const loaded = this.rooms.slice(0, this.roomsLoadedCount)
            return loaded
        },
// /////////////////////////////////////////////////////////////////////////////
        screenHeight() {
            const height = this.isDevice ? window.innerHeight + 'px' : 'calc(100vh - 80px)'
            return height
        },
    },
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
    mounted() {
        console.log('ChatContainer1::mounted()')
        console.log('ChatContainer1::mounted()::process', process)

        this.llamaUser = this.state.llama_user
        this.currentUser = this.state.current_user
        this.currentUserId = this.state.current_user.id

        this.addCss()
        this.fetchRooms()
    },
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////

    methods: {
        /***********************************************************************
         * Setup event handlers, subscribe listeners etc.
         *
         * @return {void}
         */
         addCss() {
            console.log('ChatContainer1::addCss()')
            // //////////////////////////////////////////////////////////////
            // Date configuration
            const date = new Date()
            const offset = date.getTimezoneOffset()
            console.log('ChatContainer1::addCss()::offset', offset)

            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
            console.log('ChatContainer1::addCss()::timezone', timezone)
            // //////////////////////////////////////////////////////////////
            // User online status
            this.userService.updateUserOnlineStatus(this.currentUserId, new Date())
            // //////////////////////////////////////////////////////////////
            // //////////////////////////////////////////////////////////////
            // User Event listeners
            this.userService.onLineUsers.subscribe(this.listen_UsersOnlineStatus)
            // //////////////////////////////////////////////////////////////
            // Room Event listeners
            this.roomService.onTypingUser.subscribe(this.listen_RoomsTypingUser)
            this.roomService.onRoomUpdated.subscribe(this.listen_RoomsUpdate)
            this.roomService.onRoomDeleted.subscribe(this.listen_RoomsDeleted)
            this.roomService.onRoomProcess.subscribe(this.listen_RoomsProcess)
            this.roomService.onRoomCreated.subscribe(this.listen_RoomsCreated)
            // //////////////////////////////////////////////////////////////
            // Message Event listeners
            this.msgService.onMessageSend.subscribe(this.listen_MessageSend)
            this.msgService.onLastMessage.subscribe(this.listen_LastMessage)
            this.msgService.onMessageRoom.subscribe(this.listen_NewMessageRoom)
            this.msgService.onMessageUpdate.subscribe(this.listen_MessageUpdate)
            this.msgService.onMessageDelete.subscribe(this.listen_MessageDelete)
            // //////////////////////////////////////////////////////////////
        },

        /***********************************************************************
         * Reset rooms data values
         *
         * @return {void}
         */
         resetRooms() {
            console.log('ChatContainer1::resetRooms()')

            this.rooms = []
            this.roomsLoaded = true
            this.loadingRooms = true
            this.roomsLoadedCount = 0
        },

        /***********************************************************************
         * Reset messages data values
         *
         * @return {void}
         */
         resetMessages() {
            console.log('ChatContainer1::resetMessages()')

            this.messages = []
            this.messagesLoaded = false
            this.messagesLoadedCount = 0
            this.messagesLastCount = 0
        },

        /***********************************************************************
         * Reset form data values
         *
         * @return {void}
         */
         resetForms() {
            console.log('ChatContainer1::resetForms()')

            this.disableForm = false
            this.addNewRoom = null
            this.removeRoomId = null
        },

// /////////////////////////////////////////////////////////////////////////////
         fetchRooms() {
            console.log('ChatContainer1::fetchRooms()')

            this.resetRooms()
            this.resetMessages()
            this.event_fetchMoreRooms()
        },
// /////////////////////////////////////////////////////////////////////////////
         event_fetchMoreRooms() {
            console.log('ChatContainer1::event_fetchMoreRooms()')

            const query = this.roomService.roomsQuery(
                this.currentUserId,
                this.roomsPerPage,
                this.roomsLoadedCount
            )
            this.roomService.fetchMoreRooms(query)
        },
// /////////////////////////////////////////////////////////////////////////////
         async uploadFile({ file, messageId, roomId, userId }) {
            console.log('ChatContainer1::uploadFile()', file, messageId, roomId)

            return new Promise(resolve => {
                let type = file.extension || file.type
                if (type === 'svg' || type === 'pdf') {
                    type = file.type
                }
                this.msgService.uploadMessageFile(messageId, roomId, userId, file,
                    _progress => {
                        this.updateFileProgress(messageId, _progress)
                    },
                    _url => {
                        //  const message = await this.msgService.getMessage(roomId, messageId)
                        const message = this.messages.find(message => (message._id === messageId))
                        message.files.forEach(f => {
                            delete f.progress
                            if (f.url === file.localUrl) {
                                f.url = _url
                            }
                        })
                        this.msgService.updateMessageFile(messageId, roomId, userId, file)
                        resolve(true)
                    },
                    _error => {
                        resolve(false)
                        showError('Upload Message Error')
                    }
                )
            })
        },
// /////////////////////////////////////////////////////////////////////////////
         updateFileProgress(messageId, progress) {
            // console.log('ChatContainer1::updateFileProgress()', messageId, progress)

            const message = this.messages.find(message => message._id === messageId)
            if (!message || !message.files) return

            // message.files.find(file => (file.url === fileUrl)).progress = progress
            // message.files.find(file => (file.id === fileId)).progress = progress
            message.files.find(file => (file.name === progress.file.name)).progress = progress.progress
            this.messages = [...this.messages]
        },

        /***********************************************************************
         * Format files to store with Message
         *
         * @param {files} Array files to format
         *
         * @return {formattedFiles} Array with forated files
         */
         formattedFiles(files) {
            // console.log('ChatContainer1::formattedFiles()', files)

            const formattedFiles = []
            files.forEach(file => { formattedFiles.push(this.formattedFile(file)) })
            return formattedFiles
        },

        /***********************************************************************
         * Format file to store with Message
         *
         * @param {file} Object file to format
         *
         * @return {formattedFile} Object with formated file
         */
         formattedFile(file) {
            // console.log('ChatContainer1::formattedFile())', file)

            const formattedFile = {
                name: file.name,
                size: file.size,
                type: file.type,
                extension: file.extension || file.type,
                url: file.url || file.localUrl,
            }
            if (file.audio) {
                formattedFile.audio = true
                formattedFile.duration = file.duration
            }
            return formattedFile
        },

        /***********************************************************************
         * Event open file
         *
         * @param {roomId} Object file to popen
         *
         * @return {void}
         */

         event_openFile({ file }) {
            console.log('ChatContainer1::event_openFile()', file)

            window.open(file.file.localUrl, '_blank')
        },

        /***********************************************************************
         * Event. Show demo options
         *
         * @param {roomId} Object file to popen
         *
         * @return {void}
         */

         event_showDemoOptions(bool) {
            console.log('ChatContainer1::event_showDemoOptions()', bool)
            this.$emit('show-demo-options', bool)
        },

// /////////////////////////////////////////////////////////////////////////////
         async event_openUserTag({ user }) {
            console.log('ChatContainer1::event_openUserTag()', user)

            let roomId
            this.rooms.forEach(room => {
                if (room.users.length === 2) {
                    const userId1 = room.users[0]._id
                    const userId2 = room.users[1]._id
                    if (
                            (userId1 === user._id || userId1 === this.currentUserId)
                         && (userId2 === user._id || userId2 === this.currentUserId)
                      ) {
                          roomId = room.roomId
                      }
                }
            })

            if (roomId) {
                this.roomId = roomId
                return
            }

            const query1 = await firestoreService.getUserRooms(
                this.currentUserId,
                user._id,
            )

            if (query1.data.length) {
                return this.loadRoom(query1)
            }

            const query2 = await firestoreService.getUserRooms(
                user._id,
                this.currentUserId,
            )

            if (query2.data.length) {
                return this.loadRoom(query2)
            }

            const users = (user._id === this.currentUserId)
                ? [this.currentUserId]
                : [user._id, this.currentUserId]

            const room = await firestoreService.addRoom({
                users,
                lastUpdated: new Date(),
            })

            this.roomId = room.id
            this.fetchRooms()
        },
// /////////////////////////////////////////////////////////////////////////////
         async loadRoom(query) {
            console.log('ChatContainer1::loadRoom()', query)

            query.forEach(async room => {
                if (this.loadingRooms) return
                await this.roomService.updateRoomSeen(room.id, new Date())
                this.roomId = room.id
                this.fetchRooms()
            })
        },

        /***********************************************************************
         * Event. Menu action handler
         *
         * @param {action} Text Action name
         * @param {roomId} Long Room ID reaction attached to
         *
         * @return {void}
         */
         event_menuActionHandler({ action, roomId }) {
            console.log('ChatContainer1::event_menuActionHandler()', action, roomId)

            switch (action.name) {
                case 'deleteRoom':
                    return this.deleteRoom(roomId)
            }
        },

        /***********************************************************************
         * Event. Menu selection action handler
         *
         * @param {action} Text Action name
         * @param {roomId} Long Room ID reaction attached to
         *
         * @return {void}
         */
         event_messageSelectionActionHandler({ action, messages, roomId }) {
            console.log('ChatContainer1::event_messageSelectionActionHandler()', action, messages, roomId)

            switch (action.name) {
                case 'deleteMessages':
                    messages.forEach(message => {
                          this.event_deleteMessage({ message, roomId })
                    })
            }
        },

        /***********************************************************************
         * Event. Send message reaction
         *
         * @param {reaction} Text Reaction
         * @param {remove} Boolean Remove or Add reaction
         * @param {messageId} Long Message ID reaction attached to
         * @param {roomId} Long Room ID reaction attached to
         *
         * @return {void}
         */
         async event_sendMessageReaction({ reaction, remove, messageId, roomId }) {
            console.log('ChatContainer1::event_sendMessageReaction()', reaction, remove, messageId, roomId)

            this.msgService.updateMessageReactions(
                  messageId,
                  this.currentUserId,
                  reaction.unicode,
                  remove ? 'remove' : 'add',
                  new Date()
            )
        },

        /***********************************************************************
         * Event. Typing message
         *
         * @param {message} Text Message
         * @param {roomId} Long Room id
         *
         * @return {void}
         */
         event_typingMessage({ message, roomId }) {
            // console.log('ChatContainer1::event_typingMessage()', message, roomId)

            if (roomId) {
                if (message?.length > 1) {
                    this.typingMessageCache.context = message
                    return
                }
                if (message?.length === 1 && this.typingMessageCache.context) {
                    this.typingMessageCache.context = message
                    return
                }
                if (message?.length === 0) {
                    this.typingMessageCache.timestamp = 0
                }
                if (message?.length === 1) {
                    this.typingMessageCache.timestamp = new Date()
                }
                this.typingMessageCache.context = message

                this.roomService.updateRoomTypingUsers(
                    roomId,
                    this.currentUserId,
                    message ? 'add' : 'remove'
                )
            }
        },

        /***********************************************************************
         * Listener. Typing user
         *
         * @param {query} Object { userId: long, lastChanged: Date}
         *
         * @return {void}
         */
         listen_RoomsTypingUser(query) {
            // console.log('ChatContainer1::listen_RoomsTypingUser()', query)

            const foundRoom = this.rooms.find(r => (r.roomId === query.roomId))
            if (foundRoom) {
                if (query.message === 'add') {
                    foundRoom.typingUsers = query.userId
                } else if (query.message === 'remove') {
                    foundRoom.typingUsers = null
                }
            }

        },

        /***********************************************************************
         * Listener. Room delete
         *
         * @param {room} Object { id: long, lastUpdated: seconds}
         *
         * @return {void}
         */
         listen_RoomsCreated({ roomsLen, room }) {
            console.log('ChatContainer1::listen_RoomsCreated()', room)
            this.fetchRooms()
        },

        /***********************************************************************
         * Listener. Room delete
         *
         * @param {room} Object { id: long, lastUpdated: seconds}
         *
         * @return {void}
         */
         listen_RoomsDeleted({ roomsLen, room }) {
            console.log('ChatContainer1::listen_RoomsDeleted()', room)
            this.fetchRooms()
        },

        /***********************************************************************
         * Listener. for room updates
         *
         * @param {room} Object { id: long, lastUpdated: seconds}
         *
         * @return {void}
         */
         listen_RoomsUpdate({ roomsLen, room }) {
            // console.log('ChatContainer1::listen_RoomsUpdate()', roomsLen, room)

            const foundRoom = this.rooms.find(r => (r.roomId === room.id))
            if (foundRoom) {
                foundRoom.index = room.lastUpdated.seconds
            }

        },

        /***********************************************************************
         * Listener. for room process
         *
         * @param {room} Object { id: long, lastUpdated: seconds}
         *
         * @return {void}
         */
         async listen_RoomsProcess({ roomsLen, room }) {
            console.log('ChatContainer1::listen_RoomsProcess()', roomsLen, room)
           // ///////////////////////////////////////////////////////////////////
           if (roomsLen === 0) {
                  this.roomsLoaded = true
                  this.loadingRooms = false
                  return
            }
            // ///////////////////////////////////////////////////////////////////
            const roomUserIds = []
            room.users.forEach(userId => {
                const foundUser = this.allUsers.find(user => (user?.id === userId))
                if (!foundUser && roomUserIds.indexOf(userId) === -1) {
                    roomUserIds.push(userId)
                }
            })
            // ///////////////////////////////////////////////////////////////////
            const rawUsers = []
            roomUserIds.forEach(userId => {
                const user = this.userService.getUser(userId)
                rawUsers.push(user)
            })
            this.allUsers = [...this.allUsers, ...(await Promise.all(rawUsers))]
            // ///////////////////////////////////////////////////////////////////
            const userList = []
            room.users.forEach(userId => {
                const foundUser = this.allUsers.find(user => (user?.id === userId))
                if (foundUser) userList.push(foundUser)
            })
            room.users = userList
            const roomContacts = userList.filter(
                user => user._id !== this.currentUserId
            )
            // ///////////////////////////////////////////////////////////////////
            // get room name
            const roomName =
                roomContacts.map(user => user.username).join(', ') || 'Myself'
            // get room Avatar
            const roomAvatar =
                (roomContacts.length === 1) && (roomContacts[0].avatar)
                    ? roomContacts[0].avatar
                    : logoAvatar
            // ///////////////////////////////////////////////////////////////////
            //  Create room
            const formattedRoom = {
                ...room,
                roomName,
                roomId: room.id,
                avatar: roomAvatar,
                index: room.lastUpdated.seconds,
                // unreadCount: 4,
                lastMessage: {
                    content: 'Room created',
                    timestamp: formatTimestamp(
                        new Date(room.lastUpdated.seconds),
                        room.lastUpdated
                    ),
                },
            }
            // ///////////////////////////////////////////////////////////////////
            // Add to global rooms
            this.rooms.push(formattedRoom)
            // ///////////////////////////////////////////////////////////////////
            // Loaded last room
            if ((roomsLen + this.roomsLoadedCount) === this.rooms.length) {
                this.msgService.getRoomsLastMessage(this.currentUserId)
                this.rooms.sort((a, b) => {
                    return (a.index - b.index)
                })
            }

        },

        /***********************************************************************
         * Listener. Messages send by users
         *
         * @param {message} Object Message
         *
         * @return {void}
         */
         async listen_MessageSend({ msgLen, msg }) {
            console.log('ChatContainer1::listen_MessageSend()', msgLen, msg)

            const roomId = msg.idRoom
            let file

            if (msg.files) {
                for (let index = 0; index < msg.files.length; index++) {
                    file = msg.files[index]
                    try {
                        file.blob = await fetch(file.url).then(r => r.blob())
                        await this.uploadFile({ file, messageId: msg.id, roomId, userId: this.currentUserId })
                    } catch (err) {
                        console.log(file)
                        // console.error(err)
                    }
                }
            }

            this.msgService.completeMessagesPHP(msg)
            // this.msgService.completeMessagesAXIOS(msg)

            this.roomService.updateRoomSeen(roomId, new Date((msg.timestamp.seconds) * 1000))
        },

        /***********************************************************************
         * Event. Add room
         *
         * @param {event} Object
         *
         * @return {void}
         */
         event_addRoom(event) {
            console.log('ChatContainer1::event_addRoom()', event)

            if (!this.addNewRoom) {
                this.addRoom()
            } else {
              this.addNewRoom = false
            }
        },

        /***********************************************************************
         * Function add room
         *
         * @return {void}
         */
         addRoom() {
            console.log('ChatContainer1::addRoom()')

            this.resetForms()
            this.addNewRoom = true
        },
// /////////////////////////////////////////////////////////////////////////////
        async event_createRoom(event) {
            console.log('ChatContainer1::event_createRoom()', event)

            this.disableForm = true
            // add room
            const userArray = [this.state.current_user, this.state.llama_user]
            this.roomService.createRoom(userArray, new Date())

            this.addNewRoom = false
            showSuccess('Room Created')
        },

        /***********************************************************************
         * Function. Delete room
         *
         * @return {void}
         */
         async deleteRoom(roomId) {
            console.log('ChatContainer1::deleteRoom()', roomId)

            this.roomService.deleteRoom(roomId)
            showSuccess('Room Deleted')
        },

        /***********************************************************************
         * Listener. Loading room messages
         *
         * @param {userData} Object { userId: long, lastChanged: Date}
         *
         * @return {void}
         */
         listen_UsersOnlineStatus(userData) {
            console.log('ChatContainer1::listen_UsersOnlineStatus()', userData)

            const lastChanged = formatTimestamp(
                new Date(userData.lastChanged),
                new Date(userData.lastChanged)
            )
            const online = 'online'
            // /////////////////////////////////////////////////////////////////
            // Update user status
            this.rooms.forEach(room => {
                room.users.forEach(user => {
                    if (user.id === userData.userId) {
                        user.status = { online, lastChanged }
                    }
                })
            })
        },

        /***********************************************************************
         * Fetch messages for room
         *
         * @param {room} Object Room
         * @param {message} Object Message
         *
         * @return {void}
         */
         event_fetchMessages({ room, options = {} }) {
            // console.log('ChatContainer1::event_fetchMessages()', room, options)

            this.$emit('show-demo-options', false)

            if (options.reset) {
                this.resetMessages()
            }

            const query = this.msgService.messagesQuery(
                room.id,
                this.messagesPerPage,
                this.messagesLoadedCount
            )
            this.selectedRoom = room.id
            // this.showFiles = (room.id === 1) ? 'true' : 'false'
            this.showFiles = 'true'
            this.msgService.getRoomMessages(query)

        },
        /***********************************************************************
         * Mark message seen
         *
         * @param {room} Object Room
         * @param {message} Object Message
         *
         * @return {void}
         */

         markMessagesSeen(message) {
            // console.log('ChatContainer1::markMessagesSeen()', message)

            if ((message.idUser !== this.currentUserId) &&
                (!message.seen || !message.seen[this.currentUserId])) {
                    console.log('ChatContainer1::markMessagesSeen()', message)
                    this.msgService.updateMessageSeen(message.id, message.idUser, new Date())
            }
        },

        /***********************************************************************
         * Format message
         *
         * @param {room} Object Room
         * @param {message} Object Message
         *
         * @return {void}
         */
         formatLastMessage(room, message) {
            // console.log('ChatContainer1::formatLastMessage()', room, message)

            if (!message.timestamp) return
            let content = message.content

            if (message.files?.length) {
                  const file = message.files[0]
                  content = `${file.name}.${file.extension || file.type}`
            }

            const username =
                  (message.idUser !== this.currentUserId)
                      ? room.users.find(user => message.idUser === user._id)?.username
                      : ''

            return {
                ...message,
                ...{
                  _id: message.id,
                  content,
                  senderId: message.idUser,
                  timestamp: formatTimestamp(
                        new Date(message.timestamp.seconds * 1000),
                        message.timestamp
                  ),
                  username,
                  distributed: true,
                  seen: (message.idUser === this.currentUserId) ? message.seen : null,
                  new:
                      (message.idUser !== this.currentUserId) &&
                      (!message.seen || !message.seen[this.currentUserId]),
                },
            }
        },

        /***********************************************************************
         * Format message
         *
         * @param {room} Object Room
         * @param {message} Object Message
         *
         * @return {void}
         */
         formatMessage(room, message) {
            // console.log('ChatContainer1::formatMessage()', message)

            // const senderUser = room.users.find(user => user._id === message.sender_id)
            const formattedMessage = {
                ...message,
                ...{
                    senderId: message.idUser,
                    _id: message.id,
                    seconds: message.timestamp.seconds,
                    timestamp: parseTimestamp(message.timestamp, 'HH:mm'),
                    date: parseTimestamp(message.timestamp, 'DD MMMM YYYY'),
                    username: room.users.find(user => message.idUser === user._id)?.username,
                    // avatar: senderUser ? senderUser.avatar : null,
                    distributed: true,
                },
            }
            const parentMessage = this.messages.find(msg => (msg.idParent === message.id))
            if (parentMessage) {
                // console.log('ChatContainer1::formatMessage()::replyMessage', formattedMessage, replyMessage)
                parentMessage.replyMessage = {
                    ...formattedMessage,
                    ...{
                        senderId: formattedMessage.idUser,
                    },
                }
            }
            const replyMessage = this.messages.find(msg => (msg.id === message.idParent))
            if (replyMessage) {
                formattedMessage.replyMessage = {
                    ...replyMessage,
                    ...{
                        senderId: replyMessage.idUser,
                    },
                }
            }
            // console.log('ChatContainer1::formatMessage()', formattedMessage)
            return formattedMessage
        },

        /***********************************************************************
         * Send message
         *
         * @param {content} Text Message text
         * @param {roomId} Long Room id
         * @param {files} Text Files attached to message
         * @param {replyMessage} Object Parent to message
         *
         * @return {void}
         */
         async event_sendMessage({ content, roomId, files, replyMessage }) {
            // console.log('ChatContainer1::event_sendMessage()', content, roomId, files, replyMessage)

            const message = {
                idUser: this.currentUserId,
                idRoom: roomId,
                content,
                timestampEnd: new Date(),
                timestampStart: this.typingMessageCache.timestamp
                                    ? this.typingMessageCache.timestamp
                                    : new Date(),
            }

            if (files) {
                message.files = this.formattedFiles(files)
            }
            if (replyMessage) {
                message.idParent = replyMessage.id
            }

            this.msgService.sendRoomMessage(message)
            showSuccess('Message Sent')
        },

        /***********************************************************************
         * Edit message
         *
         * @param {message} Object chat message
         *
         * @return {void}
         */
         async event_editMessage({ messageId, newContent, roomId, files }) {
            console.log('ChatContainer1::event_editMessage()', messageId, newContent, roomId, files)

            const mesg = this.messages.find(m => (m.id === messageId))
            const message = {
                id: messageId,
                idUser: this.currentUserId,
                idRoom: roomId,
                newContent,
                lastUpdated: new Date(),
            }

            if (files) {
                const filesOld = files.filter(obj => Object.prototype.hasOwnProperty.call(obj, 'id'))
                const filesNew = files.filter(obj => !Object.prototype.hasOwnProperty.call(obj, 'id'))
                let filesDel = []
                if (mesg?.files) {
                    filesDel = mesg.files.filter(obj1 => !filesOld.some(obj2 => obj1.id === obj2.id))
                }
                // ////////////////////////////////////////
                message.filesDel = filesDel
                message.filesNew = filesNew
            } else {
                if (mesg?.files) {
                    message.filesDel = mesg.files
                    message.filesNew = []
                }
                delete mesg.files
            }

            this.msgService.updateRoomMessage(message)
            showSuccess('Message Edited')
        },

        /***********************************************************************
         * Delete message (mark as deleted)
         *
         * @param {message} Object chat message
         *
         * @return {void}
         */
         async event_deleteMessage({ message, roomId }) {
            console.log('ChatContainer1::event_deleteMessage()', message, roomId)

            this.msgService.deleteMessage(message._id, roomId, new Date())
            const { files } = message
            if (files) {
                files.forEach(file => {
                    this.msgService.deleteMessageFile(message._id, roomId, this.currentUserId, file)
                })
            }
        },

        /***********************************************************************
         * Update room messages.
         *
         * @param {MsgLen} Int array length
         * @param {message} Object chat message
         *
         * @return {void}
         */
         listen_MessageDelete({ msgLen, msg }) {
            console.log('ChatContainer1::listen_MessageDelete(): ', msgLen, msg)

            // ///////////////////////////////////////////////////////////////////
            // Empty message set - No messages.
            if (msgLen === 0) {
                return
            }

            if (this.selectedRoom !== msg.idRoom) return

            const mesgIndex = this.messages.findIndex(m => (m.id === msg.id))
            const roomIndex = this.rooms.findIndex(r => (r.id === msg.idRoom))

            // ///////////////////////////////////////////////////////////////////
            // Check room exist
            if (mesgIndex >= 0) {
                const room = this.rooms[roomIndex]
                const formattedMessage = this.formatMessage(room, msg)
                this.messages[mesgIndex] = formattedMessage
                // ///////////////////////////////////////////////////////////////
                // last message loaded
                this.messages.sort((a, b) => {
                    return (a.seconds - b.seconds)
                })
            }
        },

        /***********************************************************************
         * Update room messages.
         *
         * @param {MsgLen} Int array length
         * @param {message} Object chat message
         *
         * @return {void}
         */
         listen_MessageUpdate({ msgLen, msg }) {
            console.log('ChatContainer1::listen_MessageUpdate(): ', msgLen, msg)

            // ///////////////////////////////////////////////////////////////////
            // Empty message set - No messages.
            if (msgLen === 0) {
                return
            }

            const mesgIndex = this.messages.findIndex(m => (m.id === msg.id))
            // ///////////////////////////////////////////////////////////////////
            // Check message exist
            if (mesgIndex >= 0) {
                // content
                this.messages[mesgIndex].content = msg.content
                // reactions
                if (msg?.reactions) {
                    this.messages[mesgIndex].reactions = msg.reactions
                } else {
                    delete this.messages[mesgIndex].reactions
                }
                // seen
                if (msg?.seen) {
                    this.messages[mesgIndex].seen = msg.seen
                } else {
                    delete this.messages[mesgIndex].seen
                }
                // files
                if (msg?.files) {
                    this.messages[mesgIndex].files = msg.files
                } else {
                    delete this.messages[mesgIndex].files
                }
                // ///////////////////////////////////////////////////////////////
                this.messages.sort((a, b) => {
                    return (a.seconds - b.seconds)
                })
            }
        },

        /***********************************************************************
         * Loading room messages.
         *
         * @param {MsgLen} Int array length
         * @param {message} Object chat message
         *
         * @return {void}
         */
         listen_NewMessageRoom({ msgLen, msg }) {
            console.log('ChatContainer1::listen_NewMessageRoom(): ', msgLen, msg)

            // ///////////////////////////////////////////////////////////////////
            // Empty message set - No messages.
            if (msgLen === 0) {
                this.messagesLoaded = true
                return
            }

            if (this.selectedRoom !== msg.idRoom) return
            const roomIndex = this.rooms.findIndex(r => (r.id === msg.idRoom))
            // ///////////////////////////////////////////////////////////////////
            // Check room exist
            if (roomIndex >= 0) {
                const room = this.rooms[roomIndex]
                const formattedMessage = this.formatMessage(room, msg)
                this.messages.unshift(formattedMessage)
                this.markMessagesSeen(msg)
                // ///////////////////////////////////////////////////////////////
                // last message loaded
                if ((msgLen + this.messagesLoadedCount) === this.messages.length) {
                    // this.messagesLoaded = true
                    this.messagesLoadedCount = this.messages.length
                    this.messages.sort((a, b) => {
                        return (a.seconds - b.seconds)
                    })
                }
            }
        },

        /***********************************************************************
         * Loading last message for room id
         *
         * @param {msgLen} Int room Id
         * @param {message} Object chat message
         *
         * @return {void}
         */
         listen_LastMessage({ msgLen, msg }) {
            console.log('ChatContainer1::listen_LastMessage(): ', msgLen, msg)
            // ///////////////////////////////////////////////////////////////
            // no messages
            if (msgLen === 0) {
                this._lastMessages_completed()
                return
            }
            // ///////////////////////////////////////////////////////////////
            // find room
            const roomIndex = this.rooms.findIndex(r => (r.id === msg.idRoom))
            this.messagesLastCount++

            if (roomIndex >= 0) {
                const room = this.rooms[roomIndex]
                const lastMessage = this.formatLastMessage(room, msg)
                this.rooms[roomIndex].lastMessage = lastMessage
                // this.rooms = [...this.rooms]
            }
            // ///////////////////////////////////////////////////////////////
            // Last message
            if (this.messagesLastCount === msgLen) {
                this._lastMessages_completed()
            }
        },

        /***********************************************************************
         * Loading last messages completed
         *
         * @return {void}
         */
         _lastMessages_completed() {
            this.messagesLastCount = 0
            this.roomsLoaded = true
            this.loadingRooms = false
            this.roomsLoadedCount = this.rooms.length
        },

    },
}
</script>

<style lang="scss" scoped>

  .window-container {
      width: 100%;
  }

  .window-mobile {
      form {
          padding: 0 10px 10px;
      }
  }

  form {
      padding-bottom: 20px;
  }

  input {
      padding: 5px;
      width: 140px;
      height: 21px;
      border-radius: 4px;
      border: 1px solid #d2d6da;
      outline: none;
      font-size: 14px;
      vertical-align: middle;
      &::placeholder {
          color: #9ca6af;
      }
  }

  button {
      background: #1976d2;
      color: #fff;
      outline: none;
      cursor: pointer;
      border-radius: 4px;
      padding: 8px 12px;
      margin-left: 10px;
      border: none;
      font-size: 14px;
      transition: 0.3s;
      vertical-align: middle;
      &:hover {
          opacity: 0.8;
      }
      &:active {
          opacity: 0.6;
      }
      &:disabled {
          cursor: initial;
          background: #c6c9cc;
          opacity: 0.6;
      }
  }

  .button-cancel {
      color: #a8aeb3;
      background: none;
      margin-left: 5px;
  }

  .button-image-close {
      background:url(@mdi/svg/svg/close-circle-outline.svg) no-repeat;
      width:50px;
  }

  .button-image-ok {
      background:url(@mdi/svg/svg/check-circle-outline.svg) no-repeat;
      width:50px;
  }

  .button-data {
      background:url(@mdi/svg/svg/database.svg) no-repeat;
      width:50px;
  }

  select {
      vertical-align: middle;
      height: 33px;
      width: 152px;
      font-size: 13px;
      margin: 0 !important;
  }
</style>
