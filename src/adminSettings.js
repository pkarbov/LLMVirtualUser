/* jshint esversion: 6 */
import './bootstrap.js'

import Vue from 'vue'
import AdminSettings from './views/AdminSettings.vue'
import { createPinia, PiniaVuePlugin } from 'pinia'

// eslint-disable-next-line
'use strict'

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

// eslint-disable-next-line
export default new Vue({
    el:     '#llama_admin',
    render: h => h(AdminSettings),
    pinia,
})
