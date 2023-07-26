// Example File Path:
// ./src/stores/counter.js

// import axios from '@nextcloud/axios'
import { defineStore } from 'pinia'

const ENGINE_ACTIVE = 2
const ENGINE_INACTIVE = 1
const ENGINE_INPROGRESS = 3
const ENGINE_ERROR = 0

const SERVER_NOTSET = 3
const SERVER_CONNECTED = 2
const SERVER_CONNECTING = 1
const SERVER_ERROR = 0

export const EngineConst = {
    ENGINE_ACTIVE,
    ENGINE_INACTIVE,
    ENGINE_ERROR,
    ENGINE_INPROGRESS,
}

export const ServerConst = {
    SERVER_NOTSET,
    SERVER_CONNECTED,
    SERVER_CONNECTING,
    SERVER_ERROR,
}

export const globalStore = defineStore('Settings', {
  state: () => ({
    server: 0, // 0 - error, 1 - trying,   2 - connected
    engine: 0, // 0 - error, 1 - inactive, 2 - active
    model: null, // null or not null
  }),

  actions: {
    // *****************
    // engine set status
    model_set(value) {
      // console.log('GlobalStore::model_set', value)
      this.model = value
    },
    // *****************
    // engine set status
    engine_set(status) {
      // console.log('GlobalStore::engine_set', status)
      switch (status) {
        case 0 : return this.engine_error()
        case 1 : return this.engine_inactive()
        case 2 : return this.engine_active()
      }
    },
    // engine Ok
    engine_inprogress() {
      // console.log('GlobalStore::engine_active')
      this.engine = ENGINE_INPROGRESS
    },
    // engine Ok
    engine_active(id = null) {
      // console.log('GlobalStore::engine_active')
      this.engine = ENGINE_ACTIVE
    },
    // engine Ok
    engine_inactive() {
      // console.log('GlobalStore::engine_inactive')
      this.engine = ENGINE_INACTIVE
      this.model = null
    },
    // engine error
    engine_error() {
      // console.log('GlobalStore::engine_error')
      this.engine = ENGINE_ERROR
      this.model = null
    },
    // *****************
    // server set status
    server_set(status) {
      // console.log('GlobalStore::server_set', status)
      switch (status) {
        case 0 : return this.server_error()
        case 1 : return this.server_connecting()
        case 2 : return this.server_connected()
      }
    },
    // server connecting
    server_connecting() {
      // console.log('GlobalStore::server_connecting')
      this.server = SERVER_CONNECTING
      this.model = null
    },
    // server Ok
    server_connected() {
      // console.log('GlobalStore::server_connected')
      this.server = SERVER_CONNECTED
      this.model = null
    },
    // server error
    server_error() {
      // console.log('GlobalStore::server_error')
      this.server = SERVER_ERROR
      this.model = null
    },
    // *****************
  },
})
