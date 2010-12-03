from tornad_io import SocketIOHandler

class EchoHandler(SocketIOHandler):
    def on_open(self, *args, **kwargs):
        logging.info("Socket.IO Client connected with protocol '%s' {session id: '%s'}" % (self.protocol, self.session.id))
        logging.info("Extra Data for Open: '%s'" % (kwargs.get('extra', None)))

    def on_message(self, message):
        logging.info("[echo] %s" % message)
        self.send("[echo] %s" % message)

    def on_close(self):
        logging.info("Closing Socket.IO Client for protocol '%s'" % (self.protocol))