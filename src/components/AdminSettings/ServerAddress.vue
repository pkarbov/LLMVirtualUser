<!--
 - @copyright Copyright (c) 2023 Pavlo Karbovnyk <pkarbovn@gmail.com>
 -
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
    <div id="llama_server_address" class="section">
	    <h2>
	        {{ t('llamavirtualuser', 'Server Config') }}
	    </h2>
          <p class="settings-hint">
              <InformationVariantIcon :size="24" class="icon" />
              {{ t('llamavirtualuser', 'Shared secred is ignored.') }}
          </p>
          <div class="field">
            <label for="llama-server-address">
              <KeyIcon :size="20" class="icon" />
              {{ t('llamavirtualuser', 'LLaMa server') }}
            </label>
            <NcTextField
              :value.sync="state.server_address"
              :error="checkURLError()"
              :success="checkURLSuccess()"
              :disabled="checkURLDisabled()"
              :placeholder="t('llamavirtualuser', 'Address of your LLaMa server')"
              type="url"
              @update:value="onInput()">
              <MemoryIcon :size="16" />
            </NcTextField>
            <!-- input id="llama-server-address"
              v-model="state.server_address"
              type="url"
              :placeholder="t('llamavirtualuser', 'Address of your LLaMa server')"
              @input="onInput"
              @focus="readonly = false" -->
          </div>
          <div class="field">
            <label for="llama-server-secret">
              <KeyIcon :size="20" class="icon" />
                {{ t('llamavirtualuser', 'Shared secret') }}
              </label>
            <NcTextField
              :value.sync="state.server_secret"
              :error="checkSecretError()"
              :success="checkSecretSuccess()"
              :disabled="checkSecretDisabled()"
              :placeholder="t('llamavirtualuser', 'Client secret of your LLaMa application')"
              type="text"
              @update:value="onInput()">
              <StringIcon :size="16" />
            </NcTextField>
          </div>
    </div>
</template>

<script>

import InformationVariantIcon from 'vue-material-design-icons/InformationVariant.vue'
import StringIcon  from 'vue-material-design-icons/CodeString.vue'
import MemoryIcon from 'vue-material-design-icons/Memory.vue'
import KeyIcon from 'vue-material-design-icons/Key.vue'

import { NcTextField } from '@nextcloud/vue'
import axios   from '@nextcloud/axios'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'

import { delay } from '../../utils/timer.js'
import { globalStore } from '../../utils/settings.js'
import { mapState, mapActions } from 'pinia'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
    name: 'ServerAddress',

    components: {
        InformationVariantIcon,
        NcTextField,
        MemoryIcon,
        StringIcon,
        KeyIcon,
    },

    setup() {
      // const gStore = globalStore()
      // return { gStore }
    },

    data() {
        return {
            state: loadState('llamavirtualuser', 'server-config'),
        }
    },

    computed: {
      ...mapState(globalStore, ['server']),
      ...mapState(globalStore, ['engine']),
    },

    mounted() {
      console.log('ServerAddress::mounted')
      this.server_set(this.state.server_connected)
      this.engine_set(this.state.engine_connected[0])
    },

    methods: {

      ...mapActions(globalStore, ['server_set']),
      ...mapActions(globalStore, ['engine_set']),

      ...mapActions(globalStore, ['engine_error']),
      ...mapActions(globalStore, ['engine_active']),
      ...mapActions(globalStore, ['engine_inactive']),

      ...mapActions(globalStore, ['server_error']),
      ...mapActions(globalStore, ['server_connected']),
      ...mapActions(globalStore, ['server_connecting']),

      // ====================================================
      // URL error
      checkURLError() {
        return this.server === 0
      },

      // URL success
      checkURLSuccess() {
        return this.server === 2
      },

      // disable URL
      checkURLDisabled() {
        return this.server === 1
      },
      // ====================================================

      // ====================================================
      // secret error
      checkSecretError() {
        return this.server === 0
      },

      // secret success
      checkSecretSuccess() {
        return this.server === 2
      },

      // disable secret
      checkSecretDisabled() {
        return this.server === 1
      },
      // ====================================================

      // input changed Event
      onInput() {
        // console.log('ServerAddress::saveOptions')
        delay(() => {
          this.saveOptions({
            server_address: this.state.server_address,
            server_secret:  this.state.server_secret,
          })
        }, 2000)()
      },

      // save options
      async saveOptions(values) {
        // console.log('ServerAddress::saveOptions')
        await this.saveServerOptions(values)
        await this.checkServerConnection(values)
      },

      // check connection
      async saveServerOptions(values) {
        // console.log('ServerAddress::saveServerOptions')
        const req = {
         values,
        }
        // setup url & ServerStatus
        const url = generateUrl('/apps/llamavirtualuser/server-address-config')
        // call route admin-config
        await axios.put(url, req).then((response) => {
          // console.log(response)
          showSuccess(t('llamavirtualuser', 'LLaMa server options saved'))
        }).catch((error) => {
          showError(
            t('llamavirtualuser', 'Failed to save LLaMa server options')
            + ': ' + (error.response?.request?.responseText ?? '')
          )
          console.error(error)
        })
      },

      // check connection
      async checkServerConnection(values) {
        // console.log('ServerAddress::checkServerConnect')
        // setup url & ServerStatus
        const url = generateUrl('/apps/llamavirtualuser/server-status')
        this.server_connecting()
        // call route admin-config
        await axios.get(url).then((response) => {
          // console.log(response)
          switch (response.data.connected) {
            case 0 :
              this.server_error()
              this.engine_error()
              showError(t('llamavirtualuser', 'Connection to LLaMa server: Failed'))
            break
            case 2 :
              this.server_connected()
              showSuccess(t('llamavirtualuser', 'Connection to LLaMa server: Ok'))
            break
          }
        }).catch((error) => {
          this.server_error()
          this.engine_error()
          // this.update_timer.pause()
          showError(
            t('llamavirtualuser', 'Connection to LLaMa server: Failed')
            + ': ' + (error.response?.request?.responseText ?? '')
          )
          console.error(error)
        })
      },

    },
}
</script>

<style scoped lang="scss">
#llama_server_address {
    .field {
        display: flex;
        align-items: center;
        margin-left: 30px;

        .input-field,
        input,
        label {
            width: 350px;
        }

        label {
            display: flex;
            align-items: center;
        }
        .icon {
            margin-right: 8px;
        }
    }
    .settings-hint {
        display: flex;
        align-items: center;
    }
}
</style>
