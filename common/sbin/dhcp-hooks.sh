#!/bin/bash
# Removes the script name from the list of the args
#shift

# call each hook with all the args passed
for i in /etc/dhcp-hooks.d/*
do
    $i $@ >> /tmp/autowhitelist.log
done
exit 0;
