import logging
import sys
import time
import yeelight

def hex_color_to_rgb(color):
    "Convert a hex color string to an RGB tuple."
    color = color.strip("#")
    try:
        red, green, blue = tuple(int(color[i:i + 2], 16) for i in (0, 2, 4))
    except:
        red, green, blue = (255, 0, 0)
    return red, green, blue

bulb = yeelight.Bulb(sys.argv[1], 55443, 'smooth', 500, True)

if sys.argv[2] == 'brightness':
    bulb.set_brightness(sys.argv[3])
elif sys.argv[2] == 'temperature':
    bulb.set_color_temp(sys.argv[3])
elif sys.argv[2] == 'hsv':
    bulb.set_hsv(sys.argv[3], sys.argv[4])
elif sys.argv[2] == 'flow':
    translist = sys.argv[5].split(';')
    flow = Flow(sys.argv[3],'Flow.actions.' + sys.argv[4],translist)
    bulb.start_flow(flow)
elif sys.argv[2] == 'rgb':
    red, green, blue = hex_color_to_rgb(sys.argv[3])
    bulb.set_rgb(red, green, blue)
elif sys.argv[2] == 'toggle':
    bulb.toggle()
elif sys.argv[2] == 'cron':
    bulb.cron_add(CronType.off, sys.argv[3])
elif sys.argv[3] == 'on':
    bulb.turn_on()
elif sys.argv[3] == 'off':
    bulb.turn_off()
elif sys.argv[2] == 'stop':
    bulb.stop_flow()
elif sys.argv[2] == 'status':
    for key, value in bulb.get_properties().items():
        print "Status " + key + " " + value
