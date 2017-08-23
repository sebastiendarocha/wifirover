import unittest
import os
from subprocess import check_output
import time
import pdb
import requests
import re


class Connection(unittest.TestCase):

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
        requests.get('http://192.168.22.1:81/login?&username=toto&date_end=%s&userurl=http://www.google.fr&response=toto' % str(int(time.time()) + 20))
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

