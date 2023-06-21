/* jshint esversion: 6 */

import Vue from 'vue'
import './bootstrap.js'
import PersonalSettings from './components/PersonalSettings.vue'

// eslint-disable-next-line
'use strict'

// eslint-disable-next-line
new Vue({
    el: '#llama_prefs',
    render: h => h(PersonalSettings),
})
