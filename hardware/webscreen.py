import os
import time

# URL to display
url = "https://smartclass.elektrolosskediri.my.id/onepage.html"

# Command to launch Chromium in kiosk mode
cmd = f"chromium-browser --no-sandbox --noerrdialogs --kiosk {url} --incognito --disable-restore-session-state"

# Optional: delay to ensure network is up
time.sleep(5)

# Execute the command
os.system(cmd)
