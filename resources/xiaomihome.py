import logging
import sys
import time
from mihome import *
import requests

def push_data(data):
	r = requests.post(sys.argv[1], json=data, timeout=(0.5, 120), verify=False)

if __name__ == '__main__':
	connector = XiaomiConnector(data_callback=push_data)

	while True:
		connector.check_incoming()
