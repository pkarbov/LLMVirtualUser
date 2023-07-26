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
    <div id="llama_server_status" class="section">
        <h2>
            <LLaMaIcon class="llama-icon" />
            {{ t('llamavirtualuser', 'LLaMa Integration') }}
            <small>{{ t('llamavirtualuser', 'Beta') }}</small>
        </h2>
          <div id="server">
            <NcNoteCard v-if="server === 2" show-alert type="success">
					    {{ t('llamavirtualuser', 'Server: Connected successfully.') }}
            </NcNoteCard>
            <NcNoteCard v-else-if="server === 0" show-alert type="error">
					    {{ t('llamavirtualuser', 'Server: Check address. Connection fail.') }}
            </NcNoteCard>
            <NcNoteCard v-else-if="server === 3" show-alert type="warning">
					    {{ t('llamavirtualuser', 'Server: Address not set.') }}
            </NcNoteCard>
            <NcNoteCard v-else show-alert type="warning">
					      {{ t('llamavirtualuser', 'Server: Connecting.') }}
            </NcNoteCard>
          </div>

          <div id="engine">
            <NcNoteCard v-if="engine === 2" show-alert type="success">
					    {{ t('llamavirtualuser', 'Engine: LLaMa running.') }}
            </NcNoteCard>
            <NcNoteCard v-else-if="engine === 0" show-alert type="error">
					    {{ t('llamavirtualuser', 'Engine: LLaMa error.') }}
            </NcNoteCard>
            <NcNoteCard v-else-if="engine === 1" show-alert type="warning">
					    {{ t('llamavirtualuser', 'Engine: LLaMa paused.') }}
            </NcNoteCard>
            <NcNoteCard v-else-if="engine === 3" show-alert type="warning">
					    {{ t('llamavirtualuser', 'Engine: LLaMa in progress.') }}
            </NcNoteCard>
          </div>
    </div>
</template>

<script>

import LLaMaIcon from '../../icons/LLaMaIcon.vue'

import { mapState } from 'pinia'
import { NcNoteCard } from '@nextcloud/vue'
import { globalStore } from '../../utils/settings.js'

export default {
    name: 'ServerStatus',

    components: {
      LLaMaIcon,
      NcNoteCard,
    },

    computed: {
      ...mapState(globalStore, ['server']),
      ...mapState(globalStore, ['engine']),
    },

}

</script>

<style scoped lang="scss">
#llama_server_status {
    h2 {
        display: flex;
        .llama-icon {
            margin-right: 12px;
        }
    }

    small {
	    color: var(--color-warning);
	    border: 1px solid var(--color-warning);
	    border-radius: 16px;
	    padding: 0 9px;
    }
}
</style>
