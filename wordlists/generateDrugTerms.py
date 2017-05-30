import sys
import json
#import ijson
import ijson.backends.yajl2_cffi as ijson
from collections import Counter
import traceback
import argparse
import codecs

if __name__ == '__main__':
        parser = argparse.ArgumentParser(description='Generate drug list from Wikidata resource')
	parser.add_argument('--wikidataFile', required=True, type=str, help='Path to Wikidata JSON file')
	parser.add_argument('--drugStopwords',required=True,type=str,help='Stopword file for drugs')
	parser.add_argument('--outFile', required=True, type=str, help='Path to output wordlist file')
	args = parser.parse_args()

	mainCount = Counter()
	evidenceCount = Counter()

	with codecs.open(args.drugStopwords,'r','utf8') as f:
		stopwords = [ line.strip().lower() for line in f ]
		stopwords = set(stopwords)

	IS_INSTANCE_ID = 'P31'
	PHARMA_DRUG_ID = 12140

	lines = []

	with open(args.wikidataFile, 'r') as inF, codecs.open(args.outFile,'w','utf8') as outF:
		for itemCount,item in enumerate(ijson.items(inF,'item')):

			if (itemCount%1000) == 0:
				print >> sys.stderr, itemCount
			
			id = item['id']
			try:
				if not 'en' in item['labels']:
					continue

				name = item['labels']['en']['value']
				aliases = []
				if 'en' in item['aliases']:
					aliases = [ a['value'] for a in item['aliases']['en'] ]

				aliases = [ a.replace(u"\xae", '') for a in aliases ]

				instanceOfIds = []
				if IS_INSTANCE_ID in item['claims']:
					instanceOfIds = [ c['mainsnak']['datavalue']['value']['numeric-id'] for c in item['claims'][IS_INSTANCE_ID] ]

				if PHARMA_DRUG_ID in instanceOfIds:
					allNames = [name] + aliases
					allNames = [ n.lower() for n in allNames ]
					allNames = sorted(list(set(allNames)))
					allNames = [ n for n in allNames if not n.lower() in stopwords ]
					allNames = [ n for n in allNames if len(n) > 3 ]

					if len(allNames) > 0:
						for n in allNames:
							assert not '|' in n, unicode(allNames)
						allNamesTxt = u"|".join(allNames)
						line = u"%s\t%s\n" % (id,allNamesTxt)

						outF.write(line)
			except:
				print >> sys.stderr, (id, sys.exc_info(), traceback.format_exc())
		
