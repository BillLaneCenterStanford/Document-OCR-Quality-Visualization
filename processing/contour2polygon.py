#!/usr/bin/python -tt
import re
import sys

def main(argv):
    for line in open(argv[1]):
        line = line.strip()
        for segs in line.split('|'):
            lat = segs.split(',')[1]
            lng = segs.split(',')[0]
            print '    new google.maps.LatLng(%s,%s),' %(lat, lng)


if __name__ == '__main__':
    main(sys.argv)
