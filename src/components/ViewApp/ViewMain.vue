<template>
    <!--
    SPDX-FileCopyrightText: Pavlo Karbovnyk <pkarbovn@gmail.com>
    SPDX-License-Identifier: AGPL-3.0-or-later
    -->
	<div id="llama-main-app" class="app">
		<NcAppNavigation>
			<NcAppNavigationNew v-if="!loading"
				:text="t('llmvirtualuser', 'New note')"
				:disabled="false"
				button-id="new-llmvirtualuser-button"
				button-class="icon-add"
				@click="newNote" />
			<ul>
				<NcAppNavigationItem v-for="note in notes"
					:key="note.id"
					:title="note.title ? note.title : t('llmvirtualuser', 'New note')"
					:class="{active: currentNoteId === note.id}"
					@click="openNote(note)">
					<template slot="actions">
						<NcActionButton v-if="note.id === -1"
							icon="icon-close"
							@click="cancelNewNote(note)">
							{{ t('llmvirtualuser', 'Cancel note creation') }}
						</NcActionButton>
						<NcActionButton v-else
							icon="icon-delete"
							@click="deleteNote(note)">
							{{ t('llmvirtualuser', 'Delete note') }}
						</NcActionButton>
					</template>
				</NcAppNavigationItem>
			</ul>
		</NcAppNavigation>
		<NcAppContent>
			<div v-if="currentNote">
				<input ref="title"
					v-model="currentNote.title"
					type="text"
					:disabled="updating">
				<textarea ref="content" v-model="currentNote.content" :disabled="updating" />
				<input type="button"
					class="primary"
					:value="t('llmvirtualuser', 'Save')"
					:disabled="updating || !savePossible"
					@click="saveNote">
			</div>
			<div v-else id="emptycontent">
				<div class="icon-file" />
				<h2>{{ t('llmvirtualuser', 'Create a note to get started') }}</h2>
			</div>
		</NcAppContent>
	</div>
</template>

<script>
import {
         NcActionButton,
         NcAppContent,
         NcAppNavigation,
         NcAppNavigationItem,
         NcAppNavigationNew,
         }  from '@nextcloud/vue'

// import '@nextcloud/dialogs/styles/toast.scss'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

export default {
	name: 'ViewMain',
	components: {
		NcActionButton,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationNew,
	},
	data() {
		return {
			notes: [],
			currentNoteId: null,
			updating: false,
			loading: true,
		}
	},
	computed: {

  /**
   * Description
   *
   * Return the currently selected note object
   *
   * @return {object|null}
   */
		currentNote() {
		  console.log('ViewMain::currentNote()')
			if (this.currentNoteId === null) {
				return null
			}
			return this.notes.find((note) => note.id === this.currentNoteId)
		},

  /**
   * Description
   *
   * Returns true if a note is selected and its title is not empty
   *
   * @return {boolean}
   */
		savePossible() {
		  console.log('ViewMain::savePossible()')
			return this.currentNote && this.currentNote.title !== ''
		},
	},

	/**
	 * Fetch list of notes when the component is loaded
	 */
	async mounted() {
		try {
      console.log('ViewMain::mounted()')
			const response = await axios.get(generateUrl('/apps/llmvirtualuser/notes'))
			// this.notes = response.data
			console.log('ViewMain::mounted()', response)
		} catch (e) {
			console.error(e)
			showError(t('notestutorial', 'Could not fetch notes'))
		}
		this.loading = false
	},

	methods: {

  /**
   * Description
   *
   * Create a new note and focus the note content field automatically
   *
   * @param {object} note Note object
   */
		openNote(note) {
      console.log('ViewMain::openNote()', note)
			if (this.updating) {
				return
			}
			this.currentNoteId = note.id
			this.$nextTick(() => {
				this.$refs.content.focus()
			})
		},

  /**
   * Description
   *
   * Action tiggered when clicking the save button
   * create a new note or save
   */
		saveNote() {
      console.log('ViewMain::saveNote()')
			if (this.currentNoteId === -1) {
				this.createNote(this.currentNote)
			} else {
				this.updateNote(this.currentNote)
			}
		},

  /**
   * Description
   *
   * Create a new note and focus the note content field automatically
   * The note is not yet saved, therefore an id of -1 is used until it
   * has been persisted in the backend
   */
		newNote() {
      console.log('ViewMain::newNote()')
			if (this.currentNoteId !== -1) {
				this.currentNoteId = -1
				this.notes.push({
					id: -1,
					title: '',
					content: '',
				})
				this.$nextTick(() => {
					this.$refs.title.focus()
				})
			}
		},

  /**
   * Description
   *
   * Abort creating a new note
   */
		cancelNewNote() {
      console.log('ViewMain::cancelNewNote()')
			this.notes.splice(this.notes.findIndex((note) => note.id === -1), 1)
			this.currentNoteId = null
		},

  /**
   * Description
   *
   * Create a new note by sending the information to the server
   *
   * @param {object} note Note object
   */
		async createNote(note) {
      console.log('ViewMain::createNote()', note)
			this.updating = true
			try {
				const response = await axios.post(generateUrl('/apps/llmvirtualuser/notes'), note)
				const index = this.notes.findIndex((match) => match.id === this.currentNoteId)
				this.$set(this.notes, index, response.data)
				this.currentNoteId = response.data.id
			} catch (e) {
				console.error(e)
				showError(t('notestutorial', 'Could not create the note'))
			}
			this.updating = false
		},

  /**
   * Description
   *
   * Update an existing note on the server
   *
   * @param {object} note Note object
   */
		async updateNote(note) {
			this.updating = true
			try {
        console.log('ViewMain::updateNote()', note)
				await axios.put(generateUrl(`/apps/llmvirtualuser/notes/${note.id}`), note)
			} catch (e) {
				console.error(e)
				showError(t('notestutorial', 'Could not update the note'))
			}
			this.updating = false
		},

  /**
   * Description
   *
   * Delete a note, remove it from the frontend and show a hint
   *
   * @param {object} note Note object
   */
	async deleteNote(note) {
	try {
        console.log('ViewMain::deleteNote()', note)
				await axios.delete(generateUrl(`/apps/llmvirtualuser/notes/${note.id}`))
				this.notes.splice(this.notes.indexOf(note), 1)
				if (this.currentNoteId === note.id) {
					this.currentNoteId = null
				}
				showSuccess(t('llmvirtualuser', 'Note deleted'))
			} catch (e) {
				console.error(e)
				showError(t('llmvirtualuser', 'Could not delete the note'))
			}
		},
	},
}
</script>
<style scoped>
	#app-content > div {
		width: 100%;
		height: 100%;
		padding: 20px;
		display: flex;
		flex-direction: column;
		flex-grow: 1;
	}

	input[type='text'] {
		width: 100%;
	}

	textarea {
		flex-grow: 1;
		width: 100%;
	}
</style>
