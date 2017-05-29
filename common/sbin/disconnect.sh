#! /bin/bash
# This script is launched by cron every minute and has role of
# disconnecting clients

/sbin/firewall -d

