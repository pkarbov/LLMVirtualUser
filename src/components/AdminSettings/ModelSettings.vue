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
    <section id="llama_model_settings" class="model-settings section">
	    <h2>
	        {{ t('llamavirtualuser', 'Model Settings') }}
	    </h2>
      <p class="settings-hint">
        <InformationVariantIcon :size="24" class="icon" />
        {{ t('llamavirtualuser', 'When activating model setting for the model will be disabled.') }}
      </p>

	    <template v-if="server === 2">
		    <div style="display: flex">
			    <NcCheckboxRadioSwitch
				    :button-variant="true"
				    :checked.sync="parameter.level"
				    value="0"
				    name="sharing_permission_radio"
				    type="radio"
				    button-variant-grouped="horizontal"
				    @update:checked="onCheckboxSettings">
				    Basic
				    <template #icon>
				      <CheckIcon :size="20" />
				    </template>
			    </NcCheckboxRadioSwitch>
			    <NcCheckboxRadioSwitch
				    :button-variant="true"
				    :checked.sync="parameter.level"
				    value="1"
				    name="sharing_permission_radio"
				    type="radio"
				    button-variant-grouped="horizontal"
				    @update:checked="onCheckboxSettings">
				    Standard
				    <template #icon>
				      <CancelIcon :size="20" />
				    </template>
				  </NcCheckboxRadioSwitch>
			    <NcCheckboxRadioSwitch
				    :button-variant="true"
				    :checked.sync="parameter.level"
				    value="2"
				    name="sharing_permission_radio"
				    type="radio"
				    button-variant-grouped="horizontal"
				    @update:checked="onCheckboxSettings">
				    Advanced
				    <template #icon>
				      <NumericIcon :size="20" />
				    </template>
			    </NcCheckboxRadioSwitch>
			  </div>
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
		        {{ t('llamavirtualuser', 'Default') }}
	        </div>
	        <div class="head control">
		        {{ t('llamavirtualuser', 'Input') }}
	        </div>
	        <div class="head settings">
		        {{ t('llamavirtualuser', 'Setting') }}
	        </div>
          <!---------------------->
          <!---------------------->
          <template v-for="setting in settings">
            <template v-if="setting.st <= parameter.level">
              <div :key="`${setting.id}_id`" class="id">
                {{ setting.id }}
              </div>
              <div :key="`${setting.id}_type`" class="type">
                <BoolIcon v-if="typeof(setting.val) === 'boolean'" :size="20" />
                <StringIcon v-else-if="typeof(setting.val) === 'string'" :size="20" />
                <NumericIcon v-else-if="typeof(setting.val) === 'number'" :size="20" />
              </div>
              <div :key="`${setting.id}_desc`" class="desc">
                {{ t('llamavirtualuser', setting.desc) }}
              </div>
              <div :key="`${setting.id}_def`" class="def">
                {{ setting.df }}
              </div>
              <div v-if="typeof(setting.val) === 'boolean'" :key="`${setting.id}_bool`" class="control">
                <NcCheckboxRadioSwitch
                  :checked.sync="setting.val"
                  :disabled="checkDisabledBoolean(setting)"
                  type="switch">
                  {{ t('llamavirtualuser', setting.lb) }}
                </NcCheckboxRadioSwitch>
              </div>
              <div v-else-if="typeof(setting.val) === 'number'" :key="`${setting.id}_numeric`" class="control">
                <NcTextField
                  :value.sync="setting.val_str"
                  :error="checkErrorNumeric(setting)"
                  :disabled="checkDisabledNumeric(setting)"
                  type="number">
                  <NumericIcon :size="16" />
                </NcTextField>
              </div>
              <div v-else-if="typeof(setting.val) === 'string'" :key="`${setting.id}_string`" class="control">
                <NcSelect
                  v-if="('select' in setting)"
				          v-model="setting.val"
                  v-bind="selectDict['cache'].props"
				          :disabled="checkDisabledSelect(setting)"
				          :clearable="false"
                  :placeholder="setting.lb" />
                <NcTextField
                  v-else
                  :value.sync="setting.val"
                  :disabled="true"
                  :placeholder="setting.lb">
                  <StringIcon :size="16" />
                </NcTextField>
              </div>
              <div :key="`${setting.id}_settings`" class="settings">
                {{ t('llamavirtualuser', settingTypeString(setting.st)) }}
              </div>
            </template>
          </template>
        </div>
		  </template>
    </section>
</template>

<script>

import InformationVariantIcon from 'vue-material-design-icons/InformationVariant.vue'
import BoolIcon    from 'vue-material-design-icons/OrderBoolDescending.vue'
import StringIcon  from 'vue-material-design-icons/CodeString.vue'
import NumericIcon from 'vue-material-design-icons/Numeric.vue'

import axios from '@nextcloud/axios'

import { delay } from '../../utils/timer.js'
import { generateUrl } from '@nextcloud/router'
import { GenRandomId } from '../../utils/utils.js'
import { mapState, mapActions } from 'pinia'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { globalStore, EngineConst } from '../../utils/settings.js'

import { NcCheckboxRadioSwitch, NcTextField, NcSelect } from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'

const selectDict = {
  cache: {
      name: 'test',
      props: {
        inputId: `select-${GenRandomId()}`,
        value: 'ram',
	      options: [
	        'ram',
	        'disk',
	      ],
    },
  },
}

export default {
    name: 'ModelSettings',

    components: {
      InformationVariantIcon,
      NcCheckboxRadioSwitch,
      NcTextField,
      NcSelect,
		  BoolIcon,
		  StringIcon,
      NumericIcon,
    },

    setup() {
      const gStore = globalStore()
      return { gStore }
    },

    data() {
      return {
        settings: loadState('llamavirtualuser', 'model-settings'),
        parameter: loadState('llamavirtualuser', 'parameter-level'),
        selectDict,
      }
    },

    computed: {
      ...mapState(globalStore, ['server']),
      ...mapState(globalStore, ['engine']),
    },

    mounted() {
      console.log('ModelSettings::mounted')
      this.gStore.$onAction(this.gStoreCallbackAction)
    },

    methods: {

      ...mapActions(globalStore, ['engine_error']),
      ...mapActions(globalStore, ['engine_active']),
      ...mapActions(globalStore, ['engine_inactive']),
      ...mapActions(globalStore, ['engine_inprogress']),

      settingTypeString(id) {
        switch (id) {
          case 0 : return 'Basic'
          case 1 : return 'Standard'
          case 2 : return 'Advanced'
        }
        return 'Unknown'
      },
      // ***********************************************************************
      // ***********************************************************************

      checkDisabledNumeric(setting) {
        // console.log('ModelSettings::checkDisabledNumeric: ', setting)
        if (this.checkDisabledBoolean(setting)) {
          return true
        }
        switch (setting.id) {
          case 'cache_size' : return !this.checkCacheEnabled()
        }
        return false
      },

      checkDisabledBoolean(setting) {
        // console.log('ModelSettings::checkDisabledBoolean: ', setting)
        return (this.engine === EngineConst.ENGINE_ACTIVE)
               || (this.engine === EngineConst.ENGINE_INPROGRESS)
      },

      checkDisabledSelect(setting) {
        // console.log('ModelSettings::checkDisabledBoolean: ', setting)
        if (this.checkDisabledBoolean(setting)) {
          return true
        }
        switch (setting.id) {
          case 'cache_type' : return !this.checkCacheEnabled()
        }
        return false
      },

      checkCacheEnabled() {
        // console.log('ModelSettings::checkCacheEnabled: ', setting)
        const bCache = this.settings.find(elem => elem.id === 'cache')
        return bCache ? bCache.val : true
      },

      // ***********************************************************************
      // ***********************************************************************

      checkErrorNumeric(setting) {
        return this.checkNullPositive(setting)
      },

      checkNullPositive(setting) {
        const val = parseInt(setting.val_str)
        if (isNaN(val)) return true
        if ('minmax' in setting) {
          return (val < setting.minmax[0]) || (val > setting.minmax[1])
        }
        return (setting.val_str.length === 0) || (val < 0)
      },

      // ***********************************************************************
      // ***********************************************************************
      onCheckboxSettings() {
        // console.log('ModelSettings::onCheckboxSettings: ', this.parameter.level)
        delay(() => {
            this.saveOptions({
              level: this.parameter.level,
            })
        }, 2000)()
      },

      async saveOptions(values) {
        // console.log('ModelSettings::saveOptions: ', values)
        const req = {
          values,
        }
        const url = generateUrl('/apps/llamavirtualuser/parameter-level-config')
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
        // console.log('ModelSettings::gStoreCallbackAction', value)
        if (value.name === 'server_connected') {
          this.getSettings()
        } else if (value.name === 'model_set') {
          if (value.args) {
            this.serviceModel({
              model: value.args[0],
              param: this.settings,
            })
          }
        }
      },
      // ***********************************************************************

      async getSettings() {
        // console.log('ModelSettings::saveOptions')
        const url = generateUrl('/apps/llamavirtualuser/model-settings')
        await axios.get(url).then((response) => {
          // console.log(response.data)
          this.settings = response.data
        }).catch((error) => {
          console.error(error)
        })
      },

      // ***********************************************************************
      async serviceModel(values) {
        // console.log('ModelSettings::serviceModel', values)
        const req = {
            values,
        }
        const url = generateUrl('/apps/llamavirtualuser/activate-model')
        this.engine_inprogress()
        await axios.put(url, req).then((response) => {
          const resp = response.data.replace(/"/gi, '')
          console.log('ModelSettings::serviceModel', resp)
          switch (resp) {
            case 'Activated' :
              this.engine_active()
              break
            case 'Deactivated' :
              this.engine_inactive()
              break
          }
          // this.engine_active()
          showSuccess(t('llamavirtualuser', 'LLaMa model : ' + resp))
        }).catch((error) => {
            showError(
            t('llamavirtualuser', 'Failed to activate LLaMa model')
            + ': ' + (error.response?.request?.responseText ?? '')
             )
            this.engine_error()
            console.error(error)
          })
      },
      // ***********************************************************************
      // ***********************************************************************

    },
}
</script>

<style lang="scss" scoped>
.model-settings.section {
    #settings_list {
	    display: grid;
	    grid-template-columns: minmax(100px, 120px) minmax(100px, 110px) 1fr minmax(100px, 110px) minmax(100px, 160px) minmax(100px, 120px);
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
