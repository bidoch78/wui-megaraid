class myEvent {

    #value = null;
    #subscribers = [];

    constructor(value) {
        this.#value = value;
    }

    get value() {
        return this.#value;
    }

    set value(v) {
        this.#value = v;
        for (let subscriber of this.#subscribers) subscriber.raise(v);
    }

    subscribe(subscriber) {
        this.#subscribers.push(subscriber);
    }

    unsubscribe(subscriber) {
        for(var i = 0; i < this.#subscribers.length; i++) {
            if (this.#subscribers[i].identifier == subscriber.identifier) {
                this.#subscribers.splice(i,1);
                return;
            }
        }
    }

}

class myEventSubscriber {

    static #internalId = 0;

    #id = ++myEventSubscriber.#internalId;
    #call = null;

    constructor(call) { 
        this.#call = call;
    }

    get identifier() { return this.#id; }
    raise(v) { this.#call(v); }

}

export { myEvent, myEventSubscriber };