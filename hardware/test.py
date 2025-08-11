import configparser

config = configparser.ConfigParser()
config['DEFAULT'] = {'RelayMode': 'auto', 'on': '06:00', 'off':'05:00'}

with open('config.ini', 'w') as configfile:
    config.write(configfile)
