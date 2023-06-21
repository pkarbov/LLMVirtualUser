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
      {{ t('llamavirtualuser', 'LLaMa integration') }}
    </h2>
    <p v-if="!showOAuth && !connected" class="settings-hint">
      {{ t('llamavirtualuser', 'Ask your administrator to configure the LLaMa integration in Nextcloud.') }}
    </p>
    <div id="llama-content">
      <NcButton v-if="!connected && showOAuth"
        id="llama-connect"
        class="field"
        :disabled="loading === true"
        :class="{ loading }"
        @click="onConnectClick">
        <template #icon>
          <OpenInNewIcon />
        </template>
        {{ t('llamavirtualuser', 'Connect to LLaMa') }}
      </NcButton>
      <div v-if="connected" class="field">
        <label class="llama-connected">
          <CheckIcon :size="24" class="icon" />
          {{ t('llamavirtualuser', 'Connected as {user}', { user: connectedDisplayName }) }}
        </label>
        <NcButton id="llama-rm-cred" @click="onLogoutClick">
          <template #icon>
            <CloseIcon />
          </template>
          {{ t('llamavirtualuser', 'Disconnect from LLaMa') }}
        </NcButton>
      </div>
      <div v-if="connected" id="llama-search-block">
        <NcCheckboxRadioSwitch
          :checked="state.search_pages_enabled"
          @update:checked="onCheckboxChanged($event, 'search_pages_enabled')">
          {{ t('llamavirtualuser', 'Enable searching for LLaMa pages') }}
        </NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch
          :checked="state.search_databases_enabled"
          @update:checked="onCheckboxChanged($event, 'search_databases_enabled')">
          {{ t('llamavirtualuser', 'Enable searching for LLaMa databases') }}
        </NcCheckboxRadioSwitch>
        <br>
        <p v-if="state.search_pages_enabled || state.search_databases_enabled" class="settings-hint">
          <InformationOutlineIcon :size="20" class="icon" style="margin-right: 5px;" />
          {{ t('llamavirtualuser', 'Warning, everything you type in the search bar will be sent in request to LLaMa.') }}
        </p>
      </div>
      <div v-if="connected" id="llama-link-block">
        <NcCheckboxRadioSwitch
          :checked="state.link_preview_enabled"
          @update:checked="onCheckboxChanged($event, 'link_preview_enabled')">
          {{ t('llamavirtualuser', 'Enable link preview for LLaMa pages and databases') }}
        </NcCheckboxRadioSwitch>
      </div>
    </div>
  </div>
</template>

<script>
import CheckIcon from 'vue-material-design-icons/Check.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import LLaMaIcon from './icons/LLaMaIcon.vue'

export default {
  name: 'PersonalSettings',

  components: {
    LLaMaIcon,
    NcButton,
    OpenInNewIcon,
    CloseIcon,
    CheckIcon,
    InformationOutlineIcon,
    NcCheckboxRadioSwitch,
  },

  props: [],

  data() {
    return {
      state: loadState('llamavirtualuser', 'user-config'),
      loading: false,
      redirect_uri: window.location.protocol + '//' + window.location.host + generateUrl('/apps/llamavirtualuser/oauth-redirect'),
    }
  },

  computed: {
    showOAuth() {
      return !!this.state.client_id && !!this.state.client_secret
    },
    connected() {
      return !!this.state.token && !!this.state.user_name
    },
    connectedDisplayName() {
      return this.state.user_name
    },
	},

  watch: {
  },

  mounted() {
    const paramString = window.location.search.substr(1)
    // eslint-disable-next-line
    const urlParams = new URLSearchParams(paramString)
    const glToken = urlParams.get('llamaToken')
    if (glToken === 'success') {
      showSuccess(t('llamavirtualuser', 'Successfully connected to LLaMa!'))
    } else if (glToken === 'error') {
      showError(t('llamavirtualuser', 'Error connecting to LLaMa:') + ' ' + urlParams.get('message'))
    }
  },

  methods: {
    onLogoutClick() {
      this.state.token = ''
      this.saveOptions({ token: '' })
    },
    saveOptions(values) {
      const req = {
        values,
      }
      const url = generateUrl('/apps/llamavirtualuser/config')
      axios.put(url, req).then((response) => {
        if (values.token === '' && response.data.user_name === '') {
          showSuccess(t('llamavirtualuser', 'Successfully disconnected'))
        } else {
          showSuccess(t('llamavirtualuser', 'LLaMa options saved'))
        }
      }).catch((error) => {
        showError(
          t('llamavirtualuser', 'Failed to save LLaMa options')
          + ': ' + (error.response?.request?.responseText ?? '')
        )
        console.error(error)
      }).then(() => {
        this.loading = false
      })
    },
    onConnectClick() {
    },
    connectWithOauth() {
    },
    onCheckboxChanged(newValue, key) {
      this.state[key] = newValue
      this.saveOptions({ [key]: this.state[key] ? '1' : '0' })
    },
  },
}
</script>

<style scoped lang="scss">
#llama_prefs {
  h2 {
    display: flex;

    .llama-icon {
      margin-right: 12px;
    }
  }

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
}
</style>
