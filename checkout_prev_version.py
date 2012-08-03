#!/usr/bin/python -tt
import sys
import os

#svn co -r 10 http://texasnewspapervis.googlecode.com/svn/trunk vis_TX_v10

online_url_prefix = 'http://mappingtexts.stanford.edu'
trunk_url = 'http://texasnewspapervis.googlecode.com/svn/trunk'

def main(argv):
    # compute versions to check out
    num_to_checkout = int(argv[1]) / 10
    versions = [(i+1) * 10 for i in range(num_to_checkout)]

    # check out previous version
    cmd_format = 'svn co -r %d %s ../texas_v%d'
    cmds = [cmd_format % (v, trunk_url, v) for v in versions]
    [os.system(cmd) for cmd in cmds]

    # record urls
    url_format = '%s/texas_v%d'
    urls = [url_format %(online_url_prefix, v) for v in versions]
    print '\n'.join(urls)


if __name__ == '__main__':
    main(sys.argv)

