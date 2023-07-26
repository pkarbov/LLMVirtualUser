// Example File Path:
// ./src/stores/counter.js

import axios from '@nextcloud/axios'
// import { Subject } from 'rxjs'
import { defineStore } from 'pinia'
import { generateUrl } from '@nextcloud/router'

export const useRoomStore = defineStore('roomStore', {
    state: () => ({
        rooms: [],
    }),

    getters: {
        // *********************************************************************
        // rooms
        roomsQuery: (state) => {
            return (userId, roomsPerPage, startRooms) => {
                // console.log('chatRoomService::roomsQuery()', userId, roomsPerPage, startRooms)
                return { userId, roomsPerPage, startRooms }
            }
	    },
        // *********************************************************************
        getRooms: (state) => {
	        return (request) => {
                // console.log('chatRoomService::getRooms()', request)
                const req = {
                 request,
                }
                // setup url & ServerStatus
                const url = generateUrl('/apps/llamavirtualuser/room-find')
                // call route admin-config
                return axios.put(url, req).then((response) => {
                  return response.data
                }).catch((error) => {
                  console.error(error)
                  return { data: [], docs: [] }
                })
            }
	    },
        // *********************************************************************
	    addRoom: (state) => {
	        return (room) => {
                // console.log('chatRoomService::addRoom()', room)
                // call route admin-config
                const req = {
                 room,
                }
                // setup url & ServerStatus
                const url = generateUrl('/apps/llamavirtualuser/room-create')
                // call route admin-config
                return axios.put(url, req).then((response) => {
                  console.log(response)
                  return response.data
                }).catch((error) => {
                  console.error(error)
                  return null
                })
	        }
        },
        // *********************************************************************
	    updateRoom: (state) => {
	        return (roomId, lastUpdated) => {
                console.log('chatMsgService::updateRoom()', roomId, lastUpdated)
                return new Promise((resolve) => {
                    setTimeout(() => {
                        console.log('chatMsgService::updateRoom()', 'Test 001')
                        resolve({ id: 0x343a8654336a38 })
                    }, 2000)
                })
	        }
        },
        // *********************************************************************
        deleteRoom: (state) => {
            return (roomId) => {
                console.log('chatRoomService::deleteRoom()', roomId)
            }
        },
        // *********************************************************************
    },

    actions: {
        updateRoomTypingUsers(roomId, currentUserId,  message) {
            console.log('chatRoomService::updateRoomTypingUsers()', roomId, currentUserId, message)
        },

    },

})
