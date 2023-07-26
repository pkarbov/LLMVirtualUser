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
    <section id="llama_request_settings" class="request-settings section">
	    <h2>
	        {{ t('llamavirtualuser', 'Request Settings') }}
	    </h2>

	    <template v-if="server === 2">
        <br>
        <div id="settings_list">
	        <div class="head id">
		        {{ t('llamavirtualuser', 'Id') }}
	        </div>
	        <div class="head type">
		        {{ t('llamavirtualuser', 'Type') }}
	        </div>
	        <div class="head desc">
		        {{ t('llamavirtualuser', 'Desc') }}
	        </div>
	        <div class="head def">
		        {{ t('llamavirtualuser', 'MinMax') }}
	        </div>
	        <div class="head control">
		        {{ t('llamavirtualuser', 'Input') }}
	        </div>
          <!---------------------->
          <!---------------------->
          <template v-for="setting in settings">
              <div :key="`${setting.id}_id`" class="id">
                {{ setting.id }}
              </div>
              <div :key="`${setting.id}_type`" class="type">
                <BoolIcon v-if="typeof(setting.val) === 'boolean'" :size="20" />
                <NumericIcon v-else-if="typeof(setting.val) === 'number'" :size="20" />
              </div>
              <div :key="`${setting.id}_desc`" class="desc">
                {{ t('llamavirtualuser', setting.desc) }}
              </div>
              <div :key="`${setting.id}_def`" v-tooltip="setting.tt" class="def">
                {{ setting.df }}
              </div>
              <div v-if="typeof(setting.val) === 'boolean'" :key="`${setting.id}_bool`" class="control">
                <NcCheckboxRadioSwitch
                  :checked.sync="setting.val"
                  type="switch">
                  {{ t('llamavirtualuser', setting.lb) }}
                </NcCheckboxRadioSwitch>
              </div>
              <div v-else-if="typeof(setting.val) === 'number'" :key="`${setting.id}_numeric`" class="control">
                <NcSelect
                  v-if="('select' in setting)"
				          v-model="setting.val_str"
                  v-bind="selectDict['mirostat_mode'].props"
				          :clearable="false" />
                <NcTextField
                  v-else
                  :value.sync="setting.val_str"
                  :placeholder="setting.lb"
                  :error="checkErrorNumeric(setting)"
                  type="number">
                  <NumericIcon :size="16" />
                </NcTextField>
              </div>
          </template>
        </div>
		  </template>
    </section>
</template>

<script>

import BoolIcon    from 'vue-material-design-icons/OrderBoolDescending.vue'
import NumericIcon from 'vue-material-design-icons/Numeric.vue'

import axios from '@nextcloud/axios'

import { generateUrl } from '@nextcloud/router'
import { globalStore } from '../../utils/settings.js'
import { GenRandomId } from '../../utils/utils.js'
import { mapState, mapActions } from 'pinia'
import { showSuccess, showError } from '@nextcloud/dialogs'

import { NcCheckboxRadioSwitch, NcTextField, NcSelect } from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'

const selectDict = {
  mirostat_mode: {
      props: {
        inputId: `select-${GenRandomId()}`,
	      options: [
		      '0',
	        '1',
		      '2',
	      ],
    },
  },
}

export default {
    name: 'PersonalRequestSettings',

    components: {
      NcCheckboxRadioSwitch,
      NcTextField,
      NcSelect,
		  BoolIcon,
      NumericIcon,
    },

    setup() {
      const gStore = globalStore()
      return { gStore }
    },

    data() {
      return {
        settings: loadState('llamavirtualuser', 'request-settings'),
        selectDict,
      }
    },

    computed: {
      ...mapState(globalStore, ['server']),
      ...mapState(globalStore, ['engine']),
    },

    mounted() {
      console.log('PersonalRequestSettings::mounted')
      this.gStore.$onAction(this.gStoreCallbackAction)
    },

    methods: {

      ...mapActions(globalStore, ['engine_error']),
      ...mapActions(globalStore, ['engine_active']),
      ...mapActions(globalStore, ['engine_inactive']),
      ...mapActions(globalStore, ['engine_inprogress']),

      // ***********************************************************************
      // ***********************************************************************

      checkErrorNumeric(setting) {
        return this.checkNullPositive(setting)
      },

      checkNullPositive(setting) {
        const val = parseFloat(setting.val_str)
        if (isNaN(val)) return true
        if ('minmax' in setting) {
          return (val < setting.minmax[0]) || (val > setting.minmax[1])
        }
        return (setting.val_str.length === 0)
      },

      async saveOptions(values) {
        // console.log('PersonalRequestSettings::saveOptions: ', values)
        const req = {
          values,
        }
        const url = generateUrl('/apps/llamavirtualuser/request-config')
        await axios.put(url, req).then((response) => {
          // console.log(response)
          showSuccess(t('llamavirtualuser', 'LLaMa parameter options saved'))
        }).catch((error) => {
            showError(
            t('llamavirtualuser', 'Failed to save LLaMa parameter options')
            + ': ' + (error.response?.request?.responseText ?? '')
             )
            console.error(error)
          })
      },
      // ***********************************************************************
      // ***********************************************************************

      gStoreCallbackAction(value) {
        // console.log('PersonalRequestSettings::gStoreCallbackAction', value)
        if (value.name === 'server_connected') {
            console.log('PersonalRequestSettings::gStoreCallbackAction::server_connected', value)
          // this.getSettings()
        } else if (value.name === 'model_set') {
          console.log('PersonalRequestSettings::gStoreCallbackAction::model_set', value)
        }
      },
      // ***********************************************************************

      async getSettings() {
        // console.log('PersonalRequestSettings::saveOptions')
        const url = generateUrl('/apps/llamavirtualuser/request-settings')
        await axios.get(url).then((response) => {
          // console.log(response.data)
          this.settings = response.data
        }).catch((error) => {
          console.error(error)
        })
      },

      // ***********************************************************************
      // ***********************************************************************

    },
}
</script>

<style lang="scss" scoped>
.request-settings.section {
    #settings_list {
	    display: grid;
	    grid-template-columns: minmax(100px, 120px) minmax(100px, 110px) 1fr minmax(100px, 110px) minmax(100px, 160px);
	    grid-column-gap: 10px;
	    grid-row-gap: 10px;

      .head {
        padding-bottom: 5px;
        border-bottom: 1px solid var(--color-border);
        font-weight: bold;
      }
      .v-select.select {
	      /* Override default vue-select styles */
	      height: 24px;
	      min-width: 160px;
	    }
    }
    .settings-hint {
      display: flex;
      align-items: center;
    }
}
</style>
