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
     * Constructor.
     *
     * @param {WebSocket} ws
     */
    constructor(ws) {
        this.ws = ws;
    }

    /**
     * Handle event from server.
     *
     * @param {string} eventId
     * @param {function} handler
     * @returns {self}
     */
    on(eventId, handler) {
        this.events[eventId] = handler;
        return this;
    }

    /**
     * Send event to server.
     *
     * @param {string} eventId
     * @param {object} data
     * @param {?function} handler opt_argument Alternative for `on` method.
     * @returns
     */
    event(eventId, data, callback) {
        if (callback) {
            this.on(eventId, callback);
        }

        if (this.ws.readyState !== WebSocket.OPEN) {
            this.shouldSend.push({eventId: eventId, data: data});
            return this;
        }

        let eventData = JSON.stringify({
            eventId: eventId,
            data: data || {},
        });

        if (new Blob([eventData]).size / 1e+6 > 1) {
            this.close();
            return this;
        };

        this.ws.send(eventData);

        return this;
    }

    /**
     * Start listen events from server.
     */
    listen() {
        this.ws.onopen = () => {
            this.shouldSend.forEach(event => {
                this.event(event.eventId, event.data);
            });

            this.connected ? this.connected.call() : null;
        };

        this.ws.onclose = this.disconnected;

        this.ws.onerror = this.error;

        this.ws.onmessage = event => {
            let payload = JSON.parse(event.data);
            let handler = this.events[payload.eventId] || null;

            if (handler) {
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