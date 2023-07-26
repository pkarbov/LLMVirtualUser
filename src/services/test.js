export default class CounterSingleton {

    counter = 0
    static instance = null

    // *********************************************************************
    static getInstance() {
        if (!CounterSingleton.instance) {
            CounterSingleton.instance = new CounterSingleton()
        }
        return CounterSingleton.instance
    }

    add() {
        this.counter++
    }

    show() {
        console.log(this.counter)
    }

}
