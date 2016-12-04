import logging
import sys
import time
import mihome
import requests

def push_data(data):
	r = requests.post(sys.argv[1], json=data, timeout=(0.5, 120), verify=False)

connector = XiaomiConnector(data_callback=push_data)

while True:
	connector.check_incoming()
