import { BehaviorSubject } from 'rxjs'

/**
 * A variant of Subject that requires an initial value and emits its current
 * value whenever it is subscribed to.
 *
 * @class BehaviorSubject<T>
 */
export class MsgBehaviorSubject<T,I> extends BehaviorSubject<T> {
  constructor(val: T,
              private _room_id: I) {
    super(val);
  }

  get room(): I {
    return this.getRoom();
  }

  getRoom(): I {
    const { hasError, thrownError, _room_id } = this;
    if (hasError) {
      throw thrownError;
    }
    return _room_id;
  }

}
