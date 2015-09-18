#!/bin/bash

# Don't suspend monitor
xset s off
xset -dpms
xset s noblank

# Hide mouse cursor
unclutter -idle 5 -root &

while true; do

    # Restore environment
#    rsync -qa --delete --exclude='kiosk.sh' /opt/kiosk/ $HOME/

    # Chromium window size
#    cat ~/.config/chromium/Default/Preferences | perl -pe "s/\"bottom.*/\"bottom\": $(xrandr | grep \* | cut -d' ' -f4 | cut -d'x' -f2),/" > ~/.config/chromium/Default/Preferences
#    cat ~/.config/chromium/Default/Preferences | perl -pe "s/\"right.*/\"right\": $(xrandr | grep \* | cut -d' ' -f4 | cut -d'x' -f1),/" > ~/.config/chromium/Default/Preferences

    # Set keyboard layout
    #setxkbmap -layout us

    # Start chromium
#    chromium-browser %u --kiosk --start-maximized --app=http://192.168.2.1/ds/video.html --incognito
    #chromium-browser %u

    # Start daemon
    $HOME/kiosk/daemon.php

    # Check if still running
    while kill -0 $(cat $HOME/kiosk/data/run/kiosk/kiosk.pid); do
        sleep 5s
    done;

done;
