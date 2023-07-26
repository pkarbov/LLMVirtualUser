// Example File Path:
// ./src/stores/counter.js

import axios from '@nextcloud/axios'

import { defineStore } from 'pinia'
import { generateUrl } from '@nextcloud/router'

import { getRndInteger } from '../utils/random.js'

export const useMsgStore = defineStore('msgStore', {
    state: () => ({
        subjects: [],
    }),

    getters: {
        // *********************************************************************
        getMessages: (state) => {
            return (roomId) => {
                console.log('msgStore::getMessages()', roomId)
                return new Promise((resolve) => {
                    setTimeout(() => {
                        console.log('chatMsgService::getMessages()', 'Test 001')
                        resolve({ data: [], docs: [] })
                    }, 2000)
                })
            }
	    },
        // *********************************************************************
        addMessage: (state) => {
            return (roomId, message) => {
                console.log('msgStore::addMessage()', roomId, message)
                return new Promise((resolve) => {
                    setTimeout(() => {
                        console.log('chatMsgService::addMessage()', 'Test 001')
                        resolve({ id: 0x343a8654336a38 })
                    }, 2000)
                })
            }
	    },
        // *********************************************************************
	    deleteMessage: (state) => {
	        return (roomId, messageId) => {
                console.log('msgStore::deleteMessage()', roomId, messageId)
            }
	    },
        // *********************************************************************
	    deleteFile: (state) => {
	        return (userId, messageId, szFile) => {
                console.log('msgStore::deleteFile()', userId, messageId, szFile)
            }
	    },
        // *********************************************************************

    },

    actions: {
       /***********************************************************************
	    * Read last message for room
	    *
	    * @param {roomId} room to read last messages
	    * @return {Promise}
	    */
        async lastMessage(roomId) {
            // console.log('msgStore::lastMessage()', roomId)
            const req = { roomId }
            // setup url & ServerStatus
            const url = generateUrl('/apps/llamavirtualuser/msg-last')
            return axios.put(url, req).then((response) => {
                // console.log('chatUserService::lastMessage()', response.data)
                // return response.data
                return response.data
            }).catch((error) => {
                console.error(error)
                return null
            })
	    },
       /***********************************************************************
	    * Test function to imitate long call
	    *
	    * @param {value} pass some value
	    * @return {Promise}
	    */
        async delayTest(value) {
            console.log('msgStore::delayTest()', value)
            const delay = getRndInteger(2000, 5000)
            return new Promise((resolve) => {
                setTimeout(() => {
                    console.log('msgStore::delayTest()', 'Test 001', delay)
                    resolve('Test message 001')
                }, delay)
            })
	    },
        // *********************************************************************
    },

})
