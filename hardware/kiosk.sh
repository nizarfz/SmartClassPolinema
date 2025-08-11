#!/bin/bash

xset s off
xset -dpms
xset s noblank

sleep 30

# Start openbox window manager
openbox-session &

# Jalankan Chromium dalam mode kiosk
chromium-browser --noerrdialogs --disable-infobars --kiosk "https://smartclass.elektrolosskediri.my.id/onepage.html" --incognito --disable-translate
