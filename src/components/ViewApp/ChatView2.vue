<template>
	<div id="llama-chat-app" class="app-chat">
    <ChatContainer2
      v-if="showChat"
			:theme="theme"
			:is-device="isDevice"
			:add-test-data="addTestData" />
  </div>
</template>

<script>

import * as firestoreService from './../../database/firestore.js'

import { loadState } from '@nextcloud/initial-state'
import { getCurrentUser } from '@nextcloud/auth'

import { getRndInteger, getRandomArray } from '../../utils/random.js'

import ChatContainer2 from './ChatContainer2.vue'

export default {

  name: 'ChatView2',

  components: {
    ChatContainer2,
  },

  setup() {
  },

  data() {
    return {
      state: loadState('llamavirtualuser', 'chat-config'),

      theme: 'dark',
      showChat: true,

	    isDevice: false,
      updatingData: false,
      showDemoOptions: true,

	    users: [],
      llamaUser: {},
      currentUser: {},

	  }
  },

  computed: {
	  showOptions() {
	    return !this.isDevice || this.showDemoOptions
	  },
	},

	  mounted() {
	    console.log('ChatView2::mounted()')
	    console.log('ChatView2::mounted::getCurrentUser()', getCurrentUser())

	    this.users = this.state.test_users
      this.llamaUser = this.state.llama_user
      this.currentUser = this.state.current_user

	    // console.log('ChatView1::mounted::users()', this.users)
	    // console.log('ChatView1::mounted::llamaUser()', this.llamaUser)
	    // console.log('ChatView1::mounted::currentUser()', this.currentUser)

		  this.isDevice = (window.innerWidth < 500)
		  window.addEventListener('resize', ev => {
			  if (ev.isTrusted) this.isDevice = (window.innerWidth < 500)
		  })
	  },

	  methods: {
// /////////////////////////////////////////////////////////////////////////////
		  async addTestData() {
		    console.log('ChatView2::addTestData()', this.users)

			  this.updatingData = true

        const rawUsers = []
        // this.state.current_user.avatar = 'test'
        this.users = [this.state.current_user, this.state.llama_user, ...this.users]
        this.users.forEach(user => {
            user._id = user._id.toString()
            const promise = firestoreService.addIdentifiedUser(user._id, user)
            rawUsers.push(promise)
        })
        await Promise.all(rawUsers)
        // create random rooms
        for (let i = 0; i < 10; i++) {
          const min = getRndInteger(0, this.users.length - 2)
          const array2 = getRandomArray(min, this.users.length - 1)
          const array3 = array2.map(i => this.users[i]._id)

			    await firestoreService.addRoom({
				    users: array3,
				    lastUpdated: new Date(),
			    })
        }
			  this.updatingData = false
			  location.reload()
		  },
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
	  },

}
</script>

<style lang="scss">

#content {
  border-radius: 0;
}
</style>

<style scoped lang="scss">

#content[class*='app-'] * {
  width:100%;
}

#llama-chat-app {
  body {
      font-family: 'Quicksand', sans-serif;
  }
}
</style>
