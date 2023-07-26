import { BehaviorSubject } from 'rxjs'

/**
 * A variant of Subject that requires an initial value and emits its current
 * value whenever it is subscribed to.
 *
 * @class BehaviorSubject<T>
 */
export class MsgBehaviorSubject extends BehaviorSubject {

  constructor(val, room) {
    super(val)
    this._room = room
  }

}
