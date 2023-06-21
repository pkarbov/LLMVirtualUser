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
    <div id="llama_prefs" class="section">
        <h2>
            <LLaMaIcon class="llama-icon" />
            {{ t('llamavirtualuser', 'LLaMa Integration') }}
        </h2>
        <NcSettingsSection :title="t('llamavirtualuser', 'Status')">
          <NcNoteCard v-if="connected" show-alert type="success">
            {{ t('llamavirtualuser', 'Connected successfully.') }}
          </NcNoteCard>
          <NcNoteCard v-else-if="!connected" type="error">
            {{ t('llamavirtualuser', 'Check LLaMa server address. Connection fail.') }}
          </NcNoteCard>
        </NcSettingsSection>
        <NcSettingsSection :title="t('llamavirtualuser', 'Server Config')">
          <p class="settings-hint">
              <InformationVariantIcon :size="24" class="icon" />
              {{ t('llamavirtualuser', 'Make sure you set the "Redirect URI" to') }}
              &nbsp;<b> {{ redirect_uri }} </b>
          </p>
          <div class="field">
              <label for="llama-client-id">
                  <KeyIcon :size="20" class="icon" />
                  {{ t('llamavirtualuser', 'LLaMa Server') }}
              </label>
              <input id="llama-client-id"
                  v-model="state.client_id"
                  type="password"
                  :readonly="readonly"
                  :placeholder="t('llamavirtualuser', 'Address of your LLaMa server')"
                  @input="onInput"
                  @focus="readonly = false">
          </div>
          <div class="field">
              <label for="llama-client-secret">
                  <KeyIcon :size="20" class="icon" />
                  {{ t('llamavirtualuser', 'Application secret') }}
              </label>
              <input id="llama-client-secret"
                  v-model="state.client_secret"
                  type="password"
                  :readonly="readonly"
                  :placeholder="t('llamavirtualuser', 'Client secret of your LLaMa application')"
                  @focus="readonly = false"
                  @input="onInput">
          </div>
        </NcSettingsSection>
        <NcSettingsSection :title="t('llamavirtualuser', 'Engine Config')">
        </NcSettingsSection>
    </div>
</template>

<script>
import InformationVariantIcon from 'vue-material-design-icons/InformationVariant.vue'
import KeyIcon from 'vue-material-design-icons/Key.vue'

import LLaMaIcon from './icons/LLaMaIcon.vue'

import { NcNoteCard, NcSettingsSection } from '@nextcloud/vue'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
    name: 'AdminSettings',

    components: {
        LLaMaIcon,
        NcNoteCard,
        NcSettingsSection,
        InformationVariantIcon,
        KeyIcon,
    },

    props: [],

    data() {
        return {
            state: loadState('llamavirtualuser', 'server-config'),
            readonly: true,   // to prevent some browsers to fill fields with remembered passwords
            connected: false, // check if connected
            redirect_uri: window.location.protocol + '//' + window.location.host + generateUrl('/apps/llamavirtualuser/oauth-redirect'),
        }
    },

    watch: {
    },

    mounted() {
    },

    methods: {
        onUsePopupChanged(newValue) {
            this.saveOptions({ use_popup: newValue ? '1' : '0' })
        },
        onInput() {
            delay(() => {
                this.saveOptions({
                    client_id: this.state.client_id,
                    client_secret: this.state.client_secret,
                })
            }, 2000)()
        },
        saveOptions(values) {
            const req = {
                values,
            }
            const url = generateUrl('/apps/llamavirtualuser/admin-config')
            axios.put(url, req).then((response) => {
                showSuccess(t('llamavirtualuser', 'LLaMa admin options saved'))
            }).catch((error) => {
                showError(
                    t('llamavirtualuser', 'Failed to save LLaMa admin options')
                    + ': ' + (error.response?.request?.responseText ?? '')
                )
                console.error(error)
            })
        },
    },
}
</script>

<style scoped lang="scss">
#llama_prefs {
    .field {
        display: flex;
        align-items: center;
        margin-left: 30px;

        input,
        label {
            width: 300px;
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

    h2 {
        display: flex;
        .llama-icon {
            margin-right: 12px;
        }
    }
}
</style>
