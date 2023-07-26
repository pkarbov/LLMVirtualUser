// Example File Path:
// ./src/stores/counter.js

import axios from '@nextcloud/axios'
import { defineStore } from 'pinia'
import { generateUrl } from '@nextcloud/router'

export const useUserStore = defineStore('userStore', {
    state: () => ({
        users: [],
    }),

    getters: {
        // *********************************************************************
        updateUser: (state) => {
            return (userId, data) => {
                console.log('chatUserService::updateUser()', userId, data)
                return userId
	        }
	    },
        // *********************************************************************
	    getUser: (state) => {
	        return (userId) => {
                // console.log('chatUserService::getUser()', userId)
                // call route admin-config
                const req = {
                    userId,
                }
                // setup url & ServerStatus
                const url = generateUrl('/apps/llamavirtualuser/user-find')
                // call route admin-config
                return axios.put(url, req).then((response) => {
                    response.data.image = response.data.image ?  atob(response.data.image) : null
                    // console.log('chatUserService::getUser()', response.data)
                    // return response.data
                    return response.data
                }).catch((error) => {
                    console.error(error)
                    return null
                })
            }
	    },
        // *********************************************************************
        removeRoomUser: (state) => {
            return (removeRoomId, removeUserId) => {
                console.log('chatUserService::removeRoomUser()', removeRoomId, removeUserId)
            }
        },
        // *********************************************************************
        deleteUser: (state) => {
            return (deleteUserId) => {
                console.log('chatUserService::deleteUser()', deleteUserId)
            }
	    },
        // *********************************************************************
    },

    actions: {
    },
})
