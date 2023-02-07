class Porter {
    /**
     * @param {object}
     */
    events = {
        porter: {},
        raw: {},
    };

    /**
     * @param {array}
     */
    queue = [];

    /**
     * @param {object}
     */
    options = {
        pingInterval: 30000,
        maxBodySize: 1e+6,
    };

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

    /**
     * @param {number}
     */
    __pingInterval;

    /**
     * @param {object}
     */
    raw = {
        on: null,
        send: null,
    };

    /**
     * @param {WebSocket} ws
     */
    constructor(ws, options) {
        this.ws = typeof ws == 'string' ? new WebSocket(ws) : ws;

        if (options) {
            this.options = {
                ...this.options,
                ...options
            };
        }

        this.raw.on = (data, handler) => {
            if (data == 'pong' && 'pong' in this.events.raw) {
                throw new Error('You cannot override the "pong" service event. Use `porterInstance.pong = () => {...}` instead.');
            }

            this.events.raw[data] = handler;

            return this;
        }

        this.raw.send = (data) => {
            this.ws.send(data);
        }

        this.startPingPong();
    }

    startPingPong() {
        clearInterval(this.__pingInterval);

        this.__pingInterval = setInterval(() => {
            if (this.ws.readyState !== WebSocket.OPEN) return;
            this.raw.send('ping');
        }, this.options.pingInterval);

        this.raw.on('pong', payload => {
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
        this.events.porter[type] = handler;
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
    send(type, data, callback) {
        if (callback) {
            this.on(type, callback);
        }

        if (this.ws.readyState !== WebSocket.OPEN) {
            this.queue.push({
                type: type,
                data: data
            });

            return this;
        }

        let payload = JSON.stringify({
            type: type,
            data: data || {},
        });

        var bodySize = new Blob([payload]).size;
        if (bodySize / this.options.maxBodySize > 1) {
            console.warn(`The event was not dispatched because the body size (${bodySize}) exceeded the allowable value (${this.options.maxBodySize}).`);
            return this;
        };

        this.ws.send(payload);

        return this;
    }

    close() {
        this.ws.close();
    }

    listen() {
        this.ws.onopen = () => {
            this.queue.forEach(event => {
                this.event(event.type, event.data);
            });

            if (this.connected) {
                this.connected.call()
            };
        };

        this.ws.onclose = this.disconnected;

        this.ws.onerror = this.error;

        this.ws.onmessage = event => {
            try {
                var payload = JSON.parse(event.data);
                var handler = this.events.porter[payload.type] || null;
            } catch (error) {
                var payload = event.data;
                var handler = this.events.raw[event.data] || null;
            }

            if (typeof handler == 'function') {
                handler(payload);
            }
        }
    }

    remove(event) {
        if (this.events.porter[event]) {
            delete this.events.porter[event]
        }
    }

    clear() {
        this.events.porter = {};
    }

    reconnect(ws) {
        if (this.ws.readyState == WebSocket.OPEN || this.ws.readyState == WebSocket.CONNECTING) {
            return;
        }

        this.ws = ws || new WebSocket(this.ws.url);
        this.listen();
    }
}