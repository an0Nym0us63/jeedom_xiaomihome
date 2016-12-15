import socket
s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
s.bind(("224.0.0.50", 4321))
while 1:
    data, addr = s.recvfrom(1024)
    print data
    r = requests.post(str(sys.argv[1]), json=data, timeout=(0.5, 120), verify=False)
