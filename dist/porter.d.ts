interface IEvents {
    [key: string]: IEventCallback;
}
interface IData {
    [key: string]: any;
}
interface IEventCallback {
    (data: IData): void;
}
interface IMessageCallback {
    (event: MessageEvent): void;
}
interface IConnectCallback {
    (event: Event): void;
}
interface IDisconnectCallback {
    (event: CloseEvent): void;
}
interface IErrorCallback {
    (event: Event): void;
}
type TPingInterval = number;
declare class Events {
    protected events: IEvents;
    add(id: string, callback: IEventCallback): this;
    remove(id: string): this;
    clear(): this;
    find(id: string, defaultCallback?: IEventCallback): IEventCallback | null;
}
declare class Store {
    protected data: IData;
    set(key: string, value: any): this;
    get(key: string, defaultValue?: any): any;
    has(key: string): boolean;
    remove(key: string): this;
}
export default class Client {
    protected ws: WebSocket;
    protected _events: Events;
    protected _store: Store;
    protected ping?: TPingInterval;
    protected onMessage?: IMessageCallback;
    protected onDisconnect?: IDisconnectCallback;
    constructor(url: string | URL);
    events(): Events;
    store(): Store;
    protected registerEventHandler(): void;
    protected handleEvent(event: MessageEvent): void;
    protected handleMessage(event: MessageEvent): void;
    on(id: string, callback: IEventCallback): this;
    event(id: string, data?: IData): this;
    send(data: string | ArrayBuffer | Blob | ArrayBufferView): this;
    disconnect(code?: number | undefined, reason?: string | undefined): this;
    connected(callback: IConnectCallback): this;
    disconnected(callback: IDisconnectCallback): this;
    error(callback: IErrorCallback): this;
    message(callback: IMessageCallback): this;
    reconnect(seconds?: number, callback?: Function): void;
    pingable(seconds?: number, data?: string): this;
}
export {};
//# sourceMappingURL=porter.d.ts.map