class Porter {
    events = {};

    shouldSend = [];

    constructor(ws) {
        this.ws = ws;
    }

    close() {
        this.ws.close();
    }

    on(event, handler) {
        this.events[event] = handler;
        return this;
    }

    event(eventId, data) {
        if (this.ws.readyState !== WebSocket.OPEN) {
            return this.shouldSend.push({eventId: eventId, data: data});
        }

        this.ws.send(JSON.stringify({
            eventId: eventId,
            data: data || {},
        }));

        return this;
    }

    listen() {
        this.ws.onopen = () => {
            this.shouldSend.forEach(event => {
                this.event(event.eventId, event.data);
            });

            this.connected.call();
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
}