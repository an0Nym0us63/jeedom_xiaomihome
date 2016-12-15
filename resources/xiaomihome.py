import socket
import binascii
import struct
import sys
import requests

UDP_IP = "192.168.0.107"
UDP_PORT_FROM = 54322
UDP_PORT = 54321

MULTICAST_PORT = 9898
SERVER_PORT = 4321

MULTICAST_ADDRESS = '224.0.0.50'
SOCKET_BUFSIZE = 1024
MESSAGE = binascii.unhexlify('21310020ffffffffffffffffffffffffffffffffffffffffffffffffffffffff')

sock = socket.socket(socket.AF_INET, # Internet
                     socket.SOCK_DGRAM) # UDP

sock.bind(("0.0.0.0", MULTICAST_PORT))

mreq = struct.pack("=4sl", socket.inet_aton(MULTICAST_ADDRESS), socket.INADDR_ANY)
sock.setsockopt(socket.IPPROTO_IP, socket.IP_MULTICAST_TTL, 32)
sock.setsockopt(socket.IPPROTO_IP, socket.IP_MULTICAST_LOOP, 1)
sock.setsockopt(socket.SOL_SOCKET, socket.SO_RCVBUF, SOCKET_BUFSIZE)
sock.setsockopt(socket.IPPROTO_IP, socket.IP_ADD_MEMBERSHIP, mreq)

while True:
    data, addr = sock.recvfrom(SOCKET_BUFSIZE) # buffer size is 1024 bytes
    print ("received message:", data)
    r = requests.post(str(sys.argv[1]), json=data, timeout=(0.5, 120), verify=False)
