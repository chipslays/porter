/**
 * @package Porter
 * @description An easy way to build real-time apps with a WebSocket server/client and channels.
 * @author chipslays
 * @license MIT
 * @link https://github.com/chipslays/porter
 */
class Porter {
    /**
     * Object with events.
     */
    events = {};

    /**
     * Raw websocket events.
     */
    rawEvents = {};

    /**
     * Options.
     */
    options = {
        pingInterval: 30000,
        maxBodySize: 1e+6,
    };

    /**
     * Queue while WebSocket not connected.
     */
    shouldSend = [];

    /**
     * @param {function}
     */
    connected = null;

    /**
     * @param {function}
     */
    disconnected = null;

    /**
     * @param {function}
     */
    error = null;

    /**
     * @param {function}
     */
    pong;

    pingInterval;

    /**
     * Constructor.
     *
     * @param {WebSocket} ws
     */
    constructor(ws, options) {
        this.ws = ws;

        if (options) {
            this.options = {
                ...this.options,
                ...options
            };
        }

        this.initPingPongEvent();
    }

    initPingPongEvent() {
        this.pingInterval ?
            clearInterval(this.pingInterval) :
            this.pingInterval = setInterval(() => {
                this.sendRaw('ping');
            }, this.options.pingInterval);

        this.onRaw('pong', payload => {
            // code...

            // exec user function for pong event
            if (typeof this.pong == 'function') {
                this.pong(payload);
            }
        });
    }

    /**
     * Handle event from server.
     *
     * @param {string} type
     * @param {function} handler
     * @returns {self}
     */
    on(type, handler) {
        this.events[type] = handler;
        return this;
    }

    /**
     * Send event to server.
     *
     * @param {string} type
     * @param {object} data
     * @param {?function} handler opt_argument Alternative for `on` method.
     * @returns
     */
    event(type, data, callback) {
        if (callback) {
            this.on(type, callback);
        }

        if (this.ws.readyState !== WebSocket.OPEN) {
            this.shouldSend.push({
                type: type,
                data: data
            });
            return this;
        }

        let eventData = JSON.stringify({
            type: type,
            data: data || {},
        });

        var bodySize = new Blob([eventData]).size;
        if (bodySize / this.options.maxBodySize > 1) {
            console.warn(
                `The event was not dispatched because the body size (${bodySize}) exceeded the allowable value (${this.options.maxBodySize}).`
            );
            return this;
        };

        this.ws.send(eventData);

        return this;
    }

    onRaw(data, handler) {
        if (data == 'pong' && 'pong' in this.rawEvents) {
            throw new Error(
                'You cannot override the "pong" service event. Use `porterInstance.pong = () => {...}` instead.'
            );
        }

        this.rawEvents[data] = handler;
        return this;
    }

    sendRaw(data) {
        this.ws.send(data);
    }

    /**
     * Start listen events from server.
     */
    listen() {
        this.ws.onopen = () => {
            this.shouldSend.forEach(event => {
                this.event(event.type, event.data);
            });

            this.connected && this.connected.call();
        };

        this.ws.onclose = this.disconnected;

        this.ws.onerror = this.error;

        this.ws.onmessage = event => {
            try {
                var payload = JSON.parse(event.data);
                var handler = this.events[payload.type] || null;
            } catch (error) {
                var payload = event.data;
                var handler = this.rawEvents[event.data] || null;
            }

            if (typeof handler == 'function') {
                handler(payload);
            }
        }
    }

    /**
     * Close connection.
     */
    close() {
        this.ws.close();
    }
}