#!/usr/bin/python
import sys
import re

def main(argv):
    d = {}
    for line in open('newspaper_count.txt'):
        segs = line.strip().split('<>')
        k = segs[2] + '.' + segs[3]
        k = k.lower()
        if k not in d.keys():
            d[k] = [0, 0, 0]
        d[k] = [d[k][0] + int(segs[9]), \
                d[k][1] + int(segs[10]), \
                d[k][2] + int(segs[11])]

    for k in d:
        print '%s<>%d<>%d<>%d' %(k, d[k][0], d[k][1], d[k][2])


if __name__ == '__main__':
    main(sys.argv)

