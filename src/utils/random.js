export const random = (length = 8) => {
    // Declare all characters
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'

    // Pick characers randomly
    let str = ''
    for (let i = 0; i < length; i++) {
        str += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return str
}

export const getRndInteger = (min, max) => {
    return Math.floor(Math.random() * (max - min + 1)) + min
}

export const getRandomArray = (min, max, length = Number.MAX_VALUE) => {
  if (length > (max - min + 1)) {
    length = (max - min + 1)
  }

  const randomArray = []
  const uniqueNumbers = Array.from({ length: max - min + 1 }, (_, i) => i + min)

  while (randomArray.length < length) {
    const randomIndex = Math.floor(Math.random() * uniqueNumbers.length)
    const randomNumber = uniqueNumbers[randomIndex]
    randomArray.push(randomNumber)
    uniqueNumbers.splice(randomIndex, 1)
  }

  return randomArray
}
