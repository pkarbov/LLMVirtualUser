import { realtimeDb } from './index.js'

import {
	onDisconnect,
	onValue,
	ref,
	serverTimestamp,
	set,
} from 'firebase/database'

export const firebaseListener = onValue

export const userStatusRef = userId => {
	return ref(realtimeDb, '/status/' + userId)
}

export const updateUserOnlineStatus = currentUserId => {
	const isOfflineData = {
		state: 'offline',
		lastChanged: serverTimestamp(),
	}

	const isOnlineData = {
		state: 'online',
		lastChanged: serverTimestamp(),
	}

	const connectedRef = ref(realtimeDb, '.info/connected')

	onValue(connectedRef, snap => {
		console.log('firebaseService::updateUserOnlineStatus()', connectedRef, snap)
		if (snap.val() === true) {
			onDisconnect(userStatusRef(currentUserId))
				.set(isOfflineData)
				.then(() => {
					console.log('firebaseService::updateUserOnlineStatus()::set::then()')
					set(userStatusRef(currentUserId), isOnlineData)
					.then(() => {
						console.log('firebaseService::updateUserOnlineStatus()::OnLine()')
					})
				})
		}
	})
}
