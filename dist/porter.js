class Events {
    constructor() {
        this.events = {};
    }
    add(id, callback) {
        this.events[id] = callback;
        return this;
    }
    remove(id) {
        if (this.events[id]) {
            delete this.events[id];
        }
        return this;
    }
    clear() {
        this.events = {};
        return this;
    }
    find(id, defaultCallback) {
        var _a;
        return (_a = this.events[id]) !== null && _a !== void 0 ? _a : defaultCallback;
    }
}
class Store {
    constructor() {
        this.data = {};
    }
    set(key, value) {
        this.data[key] = value;
        return this;
    }
    get(key, defaultValue) {
        var _a;
        return (_a = this.data[key]) !== null && _a !== void 0 ? _a : defaultValue;
    }
    has(key) {
        return this.data.hasOwnProperty(key);
    }
    remove(key) {
        if (this.data[key]) {
            delete this.data[key];
        }
        return this;
    }
}
export default class Client {
    constructor(url) {
        this.ws = new WebSocket(url);
        this.registerEventHandler();
        this._events = new Events;
        this._store = new Store;
    }
    events() {
        return this._events;
    }
    store() {
        return this._store;
    }
    registerEventHandler() {
        this.ws.onmessage = (event) => {
            try {
                this.handleEvent(event);
            }
            catch (error) {
                this.handleMessage(event);
            }
        };
    }
    handleEvent(event) {
        let payload = JSON.parse(event.data);
        let callback = this.events().find(payload.id);
        if (callback === null) {
            return;
        }
        callback(payload.data);
    }
    handleMessage(event) {
        if (!this.onMessage) {
            return;
        }
        this.onMessage(event);
    }
    on(id, callback) {
        this.events().add(id, callback);
        return this;
    }
    event(id, data = {}) {
        let event = JSON.stringify({
            id: id,
            data: data,
        });
        this.ws.send(event);
        return this;
    }
    send(data) {
        this.ws.send(data);
        return this;
    }
    disconnect(code, reason) {
        this.ws.close(code, reason);
        return this;
    }
    connected(callback) {
        this.ws.onopen = callback;
        return this;
    }
    disconnected(callback) {
        this.ws.onclose = (event) => {
            if (this.ping) {
                clearInterval(this.ping);
            }
            callback(event);
        };
        return this;
    }
    error(callback) {
        this.ws.onerror = callback;
        return this;
    }
    message(callback) {
        this.onMessage = callback;
        return this;
    }
    reconnect(seconds = 0, callback) {
        setTimeout(() => {
            if (this.ws.readyState == WebSocket.OPEN || this.ws.readyState == WebSocket.CONNECTING) {
                return;
            }
            if (callback) {
                callback();
            }
            let ws = new WebSocket(this.ws.url);
            ws.onopen = this.ws.onopen;
            ws.onclose = this.ws.onclose;
            ws.onerror = this.ws.onerror;
            ws.onmessage = this.ws.onmessage;
            this.ws = ws;
        }, seconds * 1000);
    }
    pingable(seconds = 55, data = 'ping') {
        this.ping = setInterval(() => {
            if (this.ws.readyState !== WebSocket.OPEN) {
                return;
            }
            this.send(data);
        }, seconds * 1000);
        return this;
    }
}
//# sourceMappingURL=porter.js.map