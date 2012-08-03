#!/usr/bin/python
import sys
import re

def main(argv):
    contents = []
    dCnt = buildMapCount('after_step1.dat')
    dLoc = buildMapLocation('location.txt')
    for k in dCnt:
        if k not in dLoc.keys():
            continue
        dContent = {}
        dContent['code'] = k
        dContent['lat'] = dLoc[k].split('<>')[1]
        dContent['lon'] = dLoc[k].split('<>')[0]
        cnt = [float(num) for num in dCnt[k].split('<>')]
        ratio = (cnt[0] + cnt[1]) / cnt[2]
        dContent['badRatio'] = ratio
        contents.append(dContent)
    print 'var dataContent = ['
    for i in contents:
        print '\t{ code: \"%s\", lat: %s, lon: %s, badRatio: \"%s\" },' %(i['code'], i['lat'], i['lon'], str(i['badRatio'])[0:4])
    print '];'


def buildMapCount(fname):
    d = {}
    for line in open(fname):
        segs = line.strip().split('<>')
        k = segs[0]
        k = k.lower()
        d[k] = segs[1]+"<>"+segs[2]+"<>"+segs[3]  # read cumulative values
    return d


def buildMapLocation(fname):
    d = {}
    for line in open(fname):
        segs = line.strip().split('<>')
        k = segs[1] + '.' + segs[3]
        k = k.lower()
        v = segs[4] + '<>' + segs[5]
        d[k] = v
    return d


if __name__ == '__main__':
    main(sys.argv)

