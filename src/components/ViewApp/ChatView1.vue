<template>
	<div id="llama-chat-app" class="app-chat">
    <ChatContainer1
      v-if="showChat"
			:theme="theme"
      :is-device="isDevice"
      :add-test-data="addTestData" />
  </div>
</template>

<script>

import { loadState } from '@nextcloud/initial-state'
import { getCurrentUser } from '@nextcloud/auth'

import { useRoomStore } from '../../stores/roomStore.js'
import { getRndInteger, getRandomArray } from '../../utils/random.js'

import ChatContainer1 from './ChatContainer1.vue'

export default {

  name: 'ChatView1',

  components: {
    ChatContainer1,
  },

  setup() {
      const gRoomStore = useRoomStore()
      return { gRoomStore }
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

	watch: {
	  currentUserId() {
		  this.showChat = false
		  setTimeout(() => (this.showChat = true), 150)
		},
	},

	  mounted() {
	    console.log('ChatView1::mounted()')
	    console.log('ChatView1::mounted::getCurrentUser()', getCurrentUser())

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
		    console.log('ChatView1::addTestData()')

		    this.addNewRoom = false
			  this.updatingData = true

        this.users = [this.state.current_user, this.state.llama_user, ...this.users]
        // create random rooms

        for (let i = 0; i < 10; i++) {
          const min = getRndInteger(0, this.users.length - 2)
          const array2 = getRandomArray(min, this.users.length - 1)
          const array3 = array2.map(i => this.users[i])
          // console.log('ChatView1::addTestData', min, array2, array3, this.users)
			    await this.gRoomStore.addRoom({
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
