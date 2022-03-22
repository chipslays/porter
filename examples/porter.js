class Porter {
    events = {};

    constructor(ws)
    {
        this.ws = ws;
    }

    close() {
        this.ws.close();
    }

    on(event, handler) {
        this.events[event] = handler;
    }

    event(eventId, data) {
        this.ws.send(JSON.stringify({
            eventId: eventId,
            data: data ?? {},
        }));
    }

    listen() {
        this.ws.onopen = this.connected;
        this.ws.onclose = this.disconnected;
        this.ws.onerror = this.error;

        this.ws.onmessage = event => {
            let payload = JSON.parse(event.data);
            let handler = this.events[payload.event] || null;

            if (handler) {
                handler(payload);
            }
        }
    }
}