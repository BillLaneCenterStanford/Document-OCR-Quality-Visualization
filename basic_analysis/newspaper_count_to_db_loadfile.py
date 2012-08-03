#!/usr/bin/python -tt

import sys
import re

def main():
    for line in open('newspaper_count.txt', 'r'):
        if line[0:2] == 'id':
            continue  # skip header line
        line = re.sub('\"', '', line.strip())  # remove \n and \"
        line = re.sub('\t', '<>', line)
        print line

if __name__ == '__main__':
    main()
