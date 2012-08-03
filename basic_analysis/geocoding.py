#!/usr/bin/python -tt
import sys
import os
from xml.dom.minidom import parse


def main(argv):
    # download from yahoo geo api
    # store locally
    # extract fields of interest
    api2apiResultFile(argv[1])

    # clean up result and store in outout file
    os.system('sort /tmp/location_coding_raw.csv | uniq > %s' %(argv[2]))


def api2apiResultFile(locationFilename):
    for line in open(locationFilename):
        [city, state] = [i.strip().replace(' ', '+') for i in line.split(',')]

        # query yahoo geo api and downlaod
        os.system('curl -o /tmp/geocoding_%s_%s.xml http://where.yahooapis.com/geocode?q=%s,%s' %(city, state, city, state))

        # extract results by row
        apiResultFile2rowResult('/tmp/geocoding_%s_%s.xml' %(city, state))


def apiResultFile2rowResult(filename):
    doc = parse(filename)

    fields = ['uzip', 'city', 'county', 'state', 'longitude', 'latitude']
    header = ''
    row    = ''
    for field in fields:
        header = header + field + ','
        row    = row + getTextField(doc, field) + ','
    print header
    print row

    resultFile = open('/tmp/location_coding_raw.csv', 'a')
    resultFile.write(header + '\n' + row + '\n')
    resultFile.close()


# based on the assumption that we only need one (and only one) entry
def getTextField(doc, field):
    elements = doc.getElementsByTagName(field)
    if len(elements) == 0:
        return ''
    if elements[0].firstChild == None:
        return ''
    return elements[0].firstChild.nodeValue


if __name__ == '__main__':
    main(sys.argv)

