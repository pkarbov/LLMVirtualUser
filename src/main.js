/**
 * SPDX-FileCopyrightText: 2018 John Molakvoæ <skjnldsv@protonmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import './bootstrap.js'

import Vue from 'vue'
import App from './views/MainApp.vue'
// import App from './components/ViewApp/ChatViewContainer.vue'

import { createPinia, PiniaVuePlugin } from 'pinia'
import { VTooltip } from 'v-tooltip'

Vue.directive('tooltip', VTooltip)
Vue.use(PiniaVuePlugin)

const pinia = createPinia()

// eslint-disable-next-line
'use strict'

export default new Vue({
    el: '#llama_main',
    render: h => h(App),
    pinia,
})
