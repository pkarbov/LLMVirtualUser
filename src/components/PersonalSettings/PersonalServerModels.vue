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
    <section id="llama_server_models" class="models section">
	    <h2>
	        {{ t('llamavirtualuser', 'Models') }}
	    </h2>

	    <!-- eslint-disable-next-line vue/no-v-html -->
      <p class="settings-hint">
        <InformationVariantIcon :size="24" class="icon" />
        {{ t('llamavirtualuser', 'Only one model can be active at a time.') }}
      </p>
	    <!-- p class="settings-hint" v-html="commandHint" / -->

      <template v-if="server === 2">
	      <div id="models_list">
	          <div class="head id">
		          {{ t('llamavirtualuser', 'Id') }}
	          </div>
	          <div class="head type">
		          {{ t('llamavirtualuser', 'Type') }}
	          </div>
	          <div class="head name">
		          {{ t('llamavirtualuser', 'Name') }}
	          </div>
	          <div class="head vocab">
		          {{ t('llamavirtualuser', 'Vocab') }}
	          </div>
	          <div class="head layers">
		          {{ t('llamavirtualuser', 'Layers') }}
	          </div>
	          <div class="head size">
		          {{ t('llamavirtualuser', 'Size') }}
	          </div>
	          <div class="head select">
		          {{ t('llamavirtualuser', 'Select') }}
	          </div>

	          <template v-for="model in models">
              <!-- template v-if="model.file_type === 7" -->
		            <div :key="`${model.id}_id`" class="id">
		              {{ model.id }}
		            </div>
		            <div :key="`${model.id}_type`" class="type">
		              {{ humanModelType(model.file_type) }}
		            </div>
		            <div :key="`${model.id}_name`" class="name">
		              {{ model.name }}
		            </div>
		            <div :key="`${model.id}_vocab`" class="vocab">
		              {{ model.vocab }}
		            </div>
		            <div :key="`${model.id}_layer`" class="layer">
		              {{ model.layer }}
		            </div>
		            <div :key="`${model.id}_size`" class="size">
		              {{ humanFileSize(model.size) }}
		            </div>
	              <div :key="`${model.id}_switch`" class="switch">
	                <NcCheckboxRadioSwitch
                    type="switch"
                    :checked.sync="model.active"
                    :disabled="checkDisabled(model)"
                    @update:checked="onInputBoolean(model)">
                      {{ t('llamavirtualuser', activeLabel(model)) }}
                  </NcCheckboxRadioSwitch>
                </div>
              <!-- /template -->
	          </template>
	      </div>
	    </template>
    </section>
</template>

<script>

import InformationVariantIcon from 'vue-material-design-icons/InformationVariant.vue'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

import axios from '@nextcloud/axios'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { mapState, mapActions } from 'pinia'
import { globalStore, EngineConst } from '../../globalStore.js'
import { Timer } from '../../utils/time.js'
import { humanModelType, humanFileSizer } from '../../utils/size.js'

export default {
    name: 'ServerModels',

    components: {
      InformationVariantIcon,
      NcCheckboxRadioSwitch,
    },

    setup() {
      const gStore = globalStore()
      return { gStore }
    },

    data() {
	    return {
	        models: loadState('llamavirtualuser', 'server-models'),
	    }
    },

    computed: {
      ...mapState(globalStore, ['server']),
      ...mapState(globalStore, ['engine']),
    },

    mounted() {
      this.gStore.$onAction(this.gStoreCallbackAction)
      this.update_timer = new Timer(this.checkEngineConnection)
	    // set only one active
    },

    methods: {

      humanFileSize,
      humanModelType,

      ...mapActions(globalStore, ['model_set']),

      ...mapActions(globalStore, ['engine_error']),
      ...mapActions(globalStore, ['engine_active']),

      activeLabel(model) {
        // console.log('ServerModels::activeLabel')
        return model.active ? 'Active' : 'Activate'
      },

      checkDisabled(model) {
        // console.log('ServerModels::checkDisabled: ', model)
        if (this.engine === EngineConst.ENGINE_INPROGRESS) {
          return true
        }
        if (this.engine === EngineConst.ENGINE_ACTIVE) {
          return !model.active // only one must be enabled we need to switch it off
        }
        return false
      },

      onInputBoolean(model) {
        // console.log('ServerModels::onInputBoolean: ', model)
        this.model_set(model)
      },

      // ***********************************************************************
      // ***********************************************************************

      gStoreCallbackAction(value) {
        // console.log('ServerModels::gStoreCallbackAction', value)
        if (value.name === 'server_connected') {
          this.getModels()
        } else if (value.name === 'engine_error') {
	        this.models.forEach((model) => {
	          if (model.active) model.active = false
	        })
        } else if (value.name === 'engine_active') {
          if (value.args.length === 1) {
            const mdId = value.args[0][1].replace(/"/g,'');
            this.models.forEach((model) => {
	            model.active = (model.id === mdId)
              console.log('ServerModels::gStoreCallbackAction', mdId, model.id)
              console.log('ServerModels::gStoreCallbackAction', model.active)
	          })
          }
        }
      },
      // ***********************************************************************

      async getModels() {
        // console.log('ModelSettings::getModels')
        const url = generateUrl('/apps/llamavirtualuser/server-models')
        await axios.get(url).then((response) => {
          // console.log(response.data)
          this.models = response.data
          this.updateModels(this.models)
        }).catch((error) => {
          console.error(error)
        })
      },

      // ***********************************************************************
      updateModels(models, id = null) {
        // console.log('ServerModels::updateModels')
	      let bFound = false
	      models.forEach((model) => {
	        if (bFound) model.active = false
	        if (!id) {
	          if (model.active) {
	            bFound = true
	            this.model_set(model)
	          }
	        } else {
	          if (model.id === id) {
	            bFound = true
	            model.active = true
	            this.model_set(model)
	          }
	        }
	      })
      },
      // ***********************************************************************
      // ***********************************************************************
      // check connection
      async checkEngineConnection() {
        console.log('ServerAddress::checkEngineConnection')
        // this.update_timer = new Timer(this.checkConnection)
        const url = generateUrl('/apps/llamavirtualuser/engine-status')
        // call route admin-config
        await axios.get(url).then((response) => {
          // console.log(response)
          this.update_timer = new Timer(this.checkEngineConnection)
          switch (response.data.connected[0]) {
            case 0 :
              this.engine_error()
            break
            case 2 :
              this.engine_active(response.data.connected)
            break
          }
        }).catch((error) => {
          this.update_timer = new Timer(this.checkEngineConnection)
          this.engine_error()
          // this.update_timer.pause()
          console.error(error)
        })
      },
    },
}
</script>

<style lang="scss" scoped>
.models.section {
    #models_list {
	    display: grid;
	    grid-template-columns: minmax(100px, 120px) minmax(100px, 120px)  1fr minmax(100px, 110px) minmax(100px, 110px) minmax(100px, 150px) minmax(100px, 110px);
	    grid-column-gap: 5px;
	    grid-row-gap: 10px;

	    .head {
        padding-bottom: 5px;
        border-bottom: 1px solid var(--color-border);
        font-weight: bold;
	    }
    }
	input {
		&[type='checkbox'],
		&[type='radio'] {
		  min-height: 0px;
		  height: 20px;
		}
	}
  .settings-hint {
    display: flex;
    align-items: center;
  }
}
</style>
