import tornado.httpserver
import tornado.ioloop
import tornado.web
from tornad_io import SocketIOServer
from websockets.test import EchoHandler
import os

application = tornado.web.Application([
        EchoHandler.routes("echoTest", "(?P<sec_a>123)(?P<sec_b>.*)", extraSep='/')
    ],  static_path=os.path.join(os.path.dirname(__file__), "static"),
        cookie_secret="AONCVUOIGPNBVOA89q873g.l,v,adva=",
        xsrf_cookies=True,
        enabled_protocols=['websocket', 'flashsocket', 'xhr-multipart', 'xhr-polling'],
        flash_policy_port=8043,
        flash_policy_file='/etc/lighttpd/flashpolicy.xml',
        socket_io_port=8888)
   
if __name__ == "__main__":
    socketio_server = SocketIOServer(application)
