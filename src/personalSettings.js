/* jshint esversion: 6 */
import './bootstrap.js'

import Vue from 'vue'
import PersonalSettings from './views/PersonalSettings.vue'
import { createPinia, PiniaVuePlugin } from 'pinia'
import { VTooltip } from 'v-tooltip'

// eslint-disable-next-line
'use strict'

Vue.directive('tooltip', VTooltip)
Vue.use(PiniaVuePlugin)

const pinia = createPinia()

// eslint-disable-next-line
new Vue({
    el: '#llama_personal',
    render: h => h(PersonalSettings),
    pinia,
})
