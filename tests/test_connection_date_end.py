import unittest
import os
from subprocess import check_output
import time
import pdb
import requests
import re
import md5


class Connection(unittest.TestCase):
    ip="192.168.23.124"
    user_mac="b8:27:eb:6a:de:f6"
    timestamp=int(time.time())
    date_end = timestamp + 20
    lifespan = 5
    key = "coin"

    @classmethod
    def setUpClass(cls):
        check_output(['bash', 'connect.sh', 'modify_timeout', '10'])

    @classmethod
    def tearDownClass(cls):
        check_output(['bash', 'connect.sh', 'modify_timeout', '7200'])

    def test_connection(self):
        """ test if connection is not operationnal
        """
        r = requests.get('http://linuxfr.org')
        self.assertNotIn("<title>Accueil - LinuxFr.org</title>", r.text)

    def test_disconnect(self):
        """ check if connection is still active 
        """
        m = md5.new()
        m.update(self.key + self.ip + self.user_mac + str(self.timestamp) + str(self.date_end) + str(self.lifespan))
        token = m.hexdigest()
        requests.get('http://192.168.22.1:81/connect.php?user-ip=%s&user-id=&user-mac=%s&timestamp=%d&redirect=http://www.google.fr&token=%s&date_end=%d&lifespan=%d' % (self.ip, self.user_mac, self.timestamp, token, self.date_end, self.lifespan))
        requests.get('http://192.168.22.1:81/disconnect.php')
        r = requests.get('http://linuxfr.org')
        self.assertIn("<title>Accueil - LinuxFr.org</title>", r.text)

    def test_disconnect_timeout(self):
        """ test if client is not disconnected after wifirover conf file timeout
        """
        time.sleep(11)
        requests.get('http://192.168.22.1:81/disconnect.php')
        r = requests.get('http://linuxfr.org')
        self.assertIn("<title>Accueil - LinuxFr.org</title>", r.text)

    def test_disconnect_timeout_date_end(self):
        """ test if client is disconnected after timeout date end parameter
        """
        time.sleep(11)
        requests.get('http://192.168.22.1:81/disconnect.php')
        r = requests.get('http://linuxfr.org')
        self.assertNotIn("<title>Accueil - LinuxFr.org</title>", r.text)

if __name__ == '__main__':
    unittest.main(verbosity=2)

