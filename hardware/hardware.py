from gpiozero import RotaryEncoder, Button, LED
from signal import pause
import st7789
from PIL import Image, ImageDraw, ImageFont
import socket
import subprocess
import psutil
import configparser
from datetime import datetime, timedelta
import time


config = configparser.ConfigParser()
config.read('config.ini')

def loadconfig():
    global RelayMode, onTime, offTime
    RelayMode = config['DEFAULT'].get('relaymode')
    onTime = config['DEFAULT'].get('on')
    offTime = config['DEFAULT'].get('off')

loadconfig()

def saveconfig():
    with open('config.ini', 'w') as configfile:
        config.write(configfile)
    loadconfig()

# === Konfigurasi GPIO ===
encoder = RotaryEncoder(a=22, b=27, max_steps=0)
button = Button(17, pull_up=True)
relay1 = LED(14, active_high=False)
relay2 = LED(15, active_high=False)
ledgreen = LED(13)
ledyellow = LED(6)
ledred = LED(5)

# === VARIABEL ===
onError = False

def get_cpu_temp():
    temp = subprocess.check_output(["vcgencmd", "measure_temp"]).decode("utf-8")
    return float(temp.replace("temp=", "").replace("'C\n", ""))

def get_cpu_load():
    return psutil.cpu_percent(interval=1)

def get_ram_usage():
    return psutil.virtual_memory()

def get_local_ip():
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    s.settimeout(0)
    try:
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
    except Exception:
        ip = "Unable to get IP"
    finally:
        s.close()
    return ip

last_bytes_sent = psutil.net_io_counters().bytes_sent
last_bytes_recv = psutil.net_io_counters().bytes_recv

def netdata():
    global last_bytes_recv
    global last_bytes_sent
    net = psutil.net_io_counters()
    new_sent = net.bytes_sent
    new_recv = net.bytes_recv

    if new_sent != last_bytes_sent or new_recv != last_bytes_recv:
        ledyellow.on()
        time.sleep(0.1)
        ledyellow.off()

    last_bytes_sent = new_sent
    last_bytes_recv = new_recv

def system_reboot():
    os.system("sudo reboot")

def system_shutdown():
    os.system("sudo shutdown now")

# Create a display instance
disp = st7789.ST7789(port=0, cs=0, rst=24, dc=25, backlight=None, spi_speed_hz=80 * 1000 * 1000)

# Added: Change to SPI MODE 3
disp._spi.mode = 2
disp.reset()
disp._init()

# Define fonts
FONT_ROBOTO = ImageFont.truetype("Roboto-Medium.ttf", 24)
FONT_NOTO = ImageFont.truetype("NotoSansCJK-Regular.ttc", 48)
FONT_NOTO2 = ImageFont.truetype("NotoSansCJK-Regular.ttc", 36)

# Define colors
COLOR_ORANGE = (255, 167, 38)
COLOR_STATUS = "green"
# Create a background image
image = Image.new("RGB", (disp.width, disp.height), "blue")

font = ImageFont.truetype("Roboto-Medium.ttf", 24)

# === Menu Setup ===
layer = "layer_home"
menu_items = ["STATISTIK", "MODE TV", "REBOOT", "SHUTDOWN", "HOME"]
tv_items = ["MANUAL ON", "MANUAL OFF", "AUTO (6-17 WIB)", "BACK", "HOME"]
confirms_items = ["YES","NO"]

pilihan = [""]
menu_index = 0
in_menu = True

def layer_home():
    cpu_temp = get_cpu_temp()
    cpu_load = get_cpu_load()
    ram_usage = get_ram_usage()
    localip = get_local_ip()
    status = ""
    if localip == "Unable to get IP":
        status = "NET ERR"
        COLOR_STATUS = "red"
    else:
        status = "NORMAL"
        COLOR_STATUS = "lime"
        netdata()
    # Draw some text
    draw = ImageDraw.Draw(image)
    # Hapus layar dengan mengisi warna hitam
    draw.rectangle((0, 0, 240, 240), fill="black")
    draw.text((0, 0), "SMARTCLASS", font=FONT_NOTO2, fill=COLOR_ORANGE)
    draw.text((0, 48), "IP: {}".format(localip), font=FONT_ROBOTO, fill="cyan")
    draw.text((0, 76), "STATUS: {}".format(status), font=FONT_ROBOTO, fill=COLOR_STATUS)
    draw.text((0,104), "CPU Temp : {}°C".format(cpu_temp), font=FONT_ROBOTO, fill=COLOR_STATUS)
    draw.text((0,132), "CPU Load  : {}%".format(cpu_load), font=FONT_ROBOTO, fill=COLOR_STATUS)
    draw.text((0,160), "RAM           : {}%".format(ram_usage.percent), font=FONT_ROBOTO, fill=COLOR_STATUS)
    
    draw.text((0, 200), "Press to open menu", font=FONT_ROBOTO, fill="orange") 
    # Show it on display
    disp.display(image)
    time.sleep(0.01)

def layer_menu(selected_index):
    image = Image.new('RGB', (240, 240), (0, 0, 0))
    draw = ImageDraw.Draw(image)
    draw.text((0, 0), "MENU", font=FONT_NOTO2, fill=COLOR_ORANGE)

    for i, item in enumerate(pilihan):
        y = 45 + i * 30
        prefix = "> " if i == selected_index else "  "
        draw.text((20, y), prefix + item, font=font, fill=(255, 255, 0)) if i == selected_index else draw.text((30, y), prefix + item, font=font, fill=(255, 255, 255))
    
    disp.display(image)

def draw_selected_action(index):
    image = Image.new('RGB', (240, 240), (0, 0, 50))
    draw = ImageDraw.Draw(image)
    draw.text((30, 100), f"Selected: {pilihan[index]}", font=font, fill=(0, 255, 0))
    disp.display(image)

# === Callback ===
def on_rotated():
    global menu_index
    #print(encoder.steps)
    menu_index = max(0, min(len(pilihan) - 1, encoder.steps))
    if encoder.steps > len(pilihan) - 1:
        encoder.steps = len(pilihan) - 1
    if encoder.steps < 0:
        encoder.steps = 0
    print(menu_index)
    if layer == "layer_menu": layer_menu(menu_index)

select = ""
def on_button_press():
    global layer
    global pilihan
    global menu_index
    global select
    print("pressed")
    if layer == "layer_home":
        pilihan = menu_items
        layer = "layer_menu"
    elif layer == "layer_menu":
        if menu_index == 4:
            layer = "layer_home"
        elif menu_index == 1:
            pilihan = tv_items
            layer = "layer_menu"
        elif menu_index == 2:
            select = "reboot"
            pilihan = confirms_items
            layer = "layer_menu"
        elif menu_index == 3:
            select = "shutdown"
            pilihan = confirms_items
            layer = "layer_menu"
        if pilihan == confirms_items and menu_index == 0 and select == "reboot":
            system_reboot()
        if pilihan == confirms_items and menu_index == 0 and select == "shutdown":
            system_reboot()
        if pilihan == confirms_items and menu_index == 1:
            pilihan = menu_items
            layer = "layer_menu"
        if pilihan == tv_items and menu_index == 0:
            config['DEFAULT']['relaymode'] = "on"
            saveconfig()
        elif pilihan == tv_items and menu_index == 1:
            config['DEFAULT']['relaymode'] = "off"
            saveconfig()
        elif pilihan == tv_items and menu_index == 2:
            config['DEFAULT']['relaymode'] = "auto"
            saveconfig()
        elif pilihan == tv_items and menu_index == 3:
            pilihan = menu_items
            layer = "layer_menu"
        elif pilihan == tv_items and menu_index == 4:
            layer == "layer_home"
    #menu_index = 0
    encoder.steps = menu_index  # reset posisi scroll ke item yang dipilih

# === Assign callbacks ===
encoder.when_rotated = on_rotated
button.when_pressed = on_button_press

def relay():
    now = datetime.now()
    now_time = now.time()
    on = datetime.strptime(onTime, "%H:%M").time()
    off = datetime.strptime(offTime, "%H:%M").time()
    if RelayMode == "auto":
        if now_time >= on and now_time <= off:
            relay1.on()
        else:
            relay1.off()
    elif RelayMode == "on":
        relay1.on()
    elif RelayMode == "off":
        relay1.off()
while(1):
    if layer == "layer_home" : layer_home()
    if layer == "layer_menu" : layer_menu(menu_index)
    relay()
    time.sleep(0.01)
    if get_cpu_temp() >= 70 or get_local_ip() == "Unable to get IP":
        onError = True
    else:
        onError = False
    ledgreen.on() if onError else ledgreen.off()
    ledred.off() if onError else ledred.on()
