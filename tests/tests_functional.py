import unittest
import os
from subprocess import check_output
import dns.resolver
from socket import gethostbyname
import time
import pdb


class TestDns(unittest.TestCase):

    @classmethod
    def setUpClass(cls):
        check_output(['bash', 'prepare.sh', 'start'])

    @classmethod
    def tearDownClass(cls):
        check_output(['bash', 'prepare.sh', 'stop'])

    def test_normal(self):
        """ nominal dns test
        """
        check_output(['bash', 'prepare.sh', 'captiveon'])
        check_output(['bash', 'prepare.sh', 'portalvalide'])
        self.assertEqual(gethostbyname('www.linuxfr.org'), '127.0.0.2')

    def test_captive_dns_on(self):
        check_output(['bash', 'prepare.sh', 'captiveon'])
        check_output(['bash', 'prepare.sh', 'portalvalide'])
        dns1 = dns.resolver.Resolver()
        dns1.nameservers = ['8.8.8.8']
        ret = dns1.query('www.linuxfr.org', 'A')

        for data in ret:
            self.assertEqual( '127.0.0.2', data.address)

    def test_captive_dns_off(self):
        check_output(['bash', 'prepare.sh', 'captiveoff'])
        check_output(['bash', 'prepare.sh', 'portalvalide'])
        dns1 = dns.resolver.Resolver()
        dns1.nameservers = ['8.8.8.8']
        ret = dns1.query('www.linuxfr.org', 'A')

        for data in ret:
            print(data.address)
            self.assertEqual( '88.191.250.176', data.address)


if __name__ == '__main__':
    unittest.main(verbosity=2)

