import logging
import sys
import time
from connector import *
import requests

def push_data(gateway, model, sid, short_id, cmd, data):
	r = requests.post(str(sys.argv[1]) + '&gateway=' + str(gateway) + '&model=' + str(model) + '&short_id=' + str(short_id) + '&sid=' + str(sid) + '&cmd=' + str(cmd), json=data, timeout=(0.5, 120), verify=False)

cb = lambda g, m, i, s, c, d: push_data(g, m, i, s, c, d)

if __name__ == '__main__':
	connector = XiaomiConnector(data_callback=cb)

	while True:
		connector.check_incoming()
        time.sleep(0.05)
