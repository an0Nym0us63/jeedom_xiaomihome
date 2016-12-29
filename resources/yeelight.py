from past.builtins import basestring
import socket
import binascii
import struct
import json
import cStringIO


class YeelightConnector:
    """Connector for the Yeelight devices on multicast."""

    MULTICAST_PORT = 1982
    SERVER_PORT = 4321

    MULTICAST_ADDRESS = '239.255.255.250'
    SOCKET_BUFSIZE = 1024

    StringIO = cStringIO

    toReport = ['id', 'model', 'fw_ver', 'power', 'bright', 'color_mode', 'ct', 'rgb', 'hue', 'sat']

    def __init__(self, data_callback=None, auto_discover=True):
        """Initialize the connector."""
        self.data_callback = data_callback
        self.last_tokens = dict()
        self.socket = self._prepare_socket()

    def _prepare_socket(self):
        sock = socket.socket(socket.AF_INET,  # Internet
                             socket.SOCK_DGRAM)  # UDP

        sock.bind(("0.0.0.0", self.MULTICAST_PORT))

        mreq = struct.pack("=4sl", socket.inet_aton(self.MULTICAST_ADDRESS),
                           socket.INADDR_ANY)
        sock.setsockopt(socket.IPPROTO_IP, socket.IP_MULTICAST_TTL, 32)
        sock.setsockopt(socket.IPPROTO_IP, socket.IP_MULTICAST_LOOP, 1)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_RCVBUF,
                        self.SOCKET_BUFSIZE)
        sock.setsockopt(socket.IPPROTO_IP, socket.IP_ADD_MEMBERSHIP, mreq)

        return sock

    def check_incoming(self):
        """Check incoming data."""
        data, addr = self.socket.recvfrom(self.SOCKET_BUFSIZE)
        try:
            for line in self.StringIO.StringIO(data):
                if ': ' in line:
                    args = line.split(': ')
                    if args[1] in self.toReport:
                        print(line)

            self.handle_incoming_data(addr[0],
                                      data)

        except Exception as e:
            raise
            print("Can't handle message %r (%r)" % (data, e))

    def handle_incoming_data(self, data, addr):
        """Handle an incoming payload, save related data if needed,
        and use the callback if there is one.
        """
        if self.data_callback is not None:
            self.data_callback(addr[0],
                               'yeelight',
                               data)
