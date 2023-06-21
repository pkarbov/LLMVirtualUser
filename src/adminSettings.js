/* jshint esversion: 6 */
import Vue from 'vue'
import './bootstrap.js'

import AdminSettings from './views/AdminSettings.vue'
import { createPinia, PiniaVuePlugin } from 'pinia'

// eslint-disable-next-line
'use strict'

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

// eslint-disable-next-line
new Vue({
    el:     '#llama_admin',
    render: h => h(AdminSettings),
    pinia,
})
