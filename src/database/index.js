import { initializeApp } from 'firebase/app'
import { getDatabase } from 'firebase/database'
import { getFirestore } from 'firebase/firestore'
import { getStorage } from 'firebase/storage'

// const config
// = import.meta.env.MODE === 'development'
//      ? JSON.parse(import.meta.env.VITE_APP_FIREBASE_CONFIG)
//      : JSON.parse(import.meta.env.VITE_APP_FIREBASE_CONFIG_PUBLIC)

const config = {
  apiKey: 'AIzaSyD5Y-MGDAmflMRnRgZtrhmPmXirSriS-Ug',
  authDomain: 'test-88585.firebaseapp.com',
  databaseURL: 'https://test-88585-default-rtdb.firebaseio.com/',
  projectId: 'test-88585',
  storageBucket: 'test-88585.appspot.com',
  messagingSenderId: '491383180767',
  appId: '1:491383180767:web:3882effa0feae3fd84086a',
}

initializeApp(config)

export const firestoreDb = getFirestore()
export const realtimeDb = getDatabase()
export const storage = getStorage()
