#!/usr/bin/python -tt
import sys
import re

# year from 1829 to 2008
year_begin = 1829
year_end   = 2008

def output_js(d):
    print "var cityYearCount = ["
    years = range(year_begin, year_end+1)
    for k in sorted(d.keys()):
        s = "    {name:\"%s\", values:[" %(k)
        for year in years:
            if year in d[k].keys():
                nBad = d[k][year][0]
                nTotal = d[k][year][1]
                ratio = 100*float(nBad)/float(nTotal)
                s = '%s%.4f' %(s, ratio)
            else:
                s = s + '0'
            if year != year_end:
                s = s + ','
        s = s + "]},"
        print s
    print "];"

def main():
    d = {}
    for line in open('city_year_count.txt'):
        segs = line.strip().split('<>')
        #key  = ','.join(segs[0:3])  # use pub,city,state as key
        key  = ','.join(segs[0:2])  # use city,state as key
        year = int(segs[2])
        nBad = int(segs[3])
        nTotal = int(segs[4])
        if key not in d.keys():
            d[key] = {}
        d[key][year] = [nBad, nTotal]
    # output d into json format
    output_js(d)


if __name__ == '__main__':
    main()

