#!/bin/bash
for i in /etc/watchdog.d/*
do
    $i
done
exit 0;
