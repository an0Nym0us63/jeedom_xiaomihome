import logging
import sys
import time
from mihome import *
import requests

def push_data(model, sid, cmd, data):
	r = requests.post(str(sys.argv[1]) + '&model=' + str(model) + '&sid=' + str(sid) + '&cmd=' + str(cmd), json=data, timeout=(0.5, 120), verify=False)

cb = lambda m, s, c, d: push_data(m, s, c, d)

if __name__ == '__main__':
	connector = XiaomiConnector(data_callback=cb)

	while True:
		connector.check_incoming()
