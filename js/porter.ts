interface IEvents {
    [key: string]: IEventCallback
}

interface IData {
    [key: string]: any
}

interface IEventCallback {
    (data: IData): void
}

interface IMessageCallback {
    (event: MessageEvent): void
}

interface IConnectCallback {
    (event: Event): void
}

interface IDisconnectCallback {
    (event: CloseEvent): void
}

interface IErrorCallback {
    (event: Event): void
}

type TPingInterval = number;

class Events {
    protected events: IEvents = {};

    public add(id: string, callback: IEventCallback): this {
        this.events[id] = callback;

        return this;
    }

    public remove(id: string): this {
        if (this.events[id]) {
            delete this.events[id];
        }

        return this;
    }

    public clear(): this {
        this.events = {};

        return this;
    }

    public find(id: string, defaultCallback?: IEventCallback): IEventCallback|null {
        return this.events[id] ?? defaultCallback;
    }
}

class Store {
    protected data: IData = {};

    public set(key: string, value: any): this {
        this.data[key] = value;

        return this;
    }

    public get(key: string, defaultValue?: any): any {
        return this.data[key] ?? defaultValue;
    }

    public has(key: string): boolean {
        return this.data.hasOwnProperty(key);
    }

    public remove(key: string): this {
        if (this.data[key]) {
            delete this.data[key];
        }

        return this;
    }
}

export default class Client {
    protected ws: WebSocket;

    protected _events: Events;

    protected _store: Store;

    protected ping?: TPingInterval;

    protected onMessage?: IMessageCallback;

    protected onDisconnect?: IDisconnectCallback;

    public constructor(url: string|URL) {
        this.ws = new WebSocket(url);

        this.registerEventHandler();

        this._events = new Events;
        this._store = new Store;
    }

    public events(): Events
    {
        return this._events;
    }

    public store(): Store
    {
        return this._store;
    }

    protected registerEventHandler() {
        this.ws.onmessage = (event: MessageEvent) => {
            try {
                this.handleEvent(event);
            } catch (error) {
                this.handleMessage(event);
            }
        };
    }

    protected handleEvent(event: MessageEvent): void {
        let payload = JSON.parse(event.data);

        let callback = this.events().find(payload.id);

        if (callback === null) {
            return;
        }

        callback(payload.data);
    }

    protected handleMessage(event: MessageEvent): void {
        if (!this.onMessage) {
            return;
        }

        this.onMessage(event);
    }

    public on(id: string, callback: IEventCallback): this {
        this.events().add(id, callback);

        return this;
    }

    public event(id: string, data: IData = {}): this {
        let event = JSON.stringify({
            id: id,
            data: data,
        });

        this.ws.send(event);

        return this;
    }

    public send(data: string|ArrayBuffer|Blob|ArrayBufferView): this {
        this.ws.send(data);

        return this;
    }

    public disconnect(code?: number | undefined, reason?: string | undefined): this {
        this.ws.close(code, reason);

        return this;
    }

    public connected(callback: IConnectCallback): this {
        this.ws.onopen = callback;

        return this;
    }

    public disconnected(callback: IDisconnectCallback): this {
        this.ws.onclose = (event) => {
            if (this.ping) {
                clearInterval(this.ping);
            }

            callback(event);
        };

        return this;
    }

    public error(callback: IErrorCallback): this {
        this.ws.onerror = callback;

        return this;
    }

    public message(callback: IMessageCallback): this {
        this.onMessage = callback;

        return this;
    }

    public reconnect(seconds: number = 0, callback?: Function) {
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

    public pingable(seconds: number = 55, data: string = 'ping'): this {
        this.ping = setInterval(() => {
            if (this.ws.readyState !== WebSocket.OPEN) {
                return;
            }

            this.send(data);
        }, seconds * 1000);

        return this;
    }
}