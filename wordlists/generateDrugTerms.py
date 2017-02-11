import sys
import json
#import ijson
import ijson.backends.yajl2_cffi as ijson
from collections import Counter
import traceback

filename = 'wikidata.json'

mainCount = Counter()
evidenceCount = Counter()

IS_INSTANCE_ID = 'P31'
PHARMA_DRUG_ID = 12140

lines = []

with open(filename, 'r') as f:
	#parser = ijson.parse(f)
	#for i,(prefix, event, value) in enumerate(parser):
	#	out = "%s\t%s\t%s" % (prefix,event,value)
	#	print out.encode('utf8')
	#	if i > 10000:
	#		break

	for itemCount,item in enumerate(ijson.items(f,'item')):
		#print item.keys()

		if (itemCount%1000) == 0:
			print >> sys.stderr, itemCount
		#if itemCount > 10000:
		#	break

		#print item
		#print json.dumps(item, sort_keys=True, indent=4)
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
				#print id, name, aliases
				allNames = sorted(list(set([name] + aliases)))
				for n in allNames:
					assert not '|' in n, unicode(allNames)
				allNamesTxt = u"|".join(allNames)
				line = u"%s\t%s" % (id,allNamesTxt)
				print line.encode('utf8')
				#lines.append(line)
				#break
		except:
			print >> sys.stderr, (id, sys.exc_info(), traceback.format_exc())
	
#lines = sorted(lines)
#for l in lines:
#	print l
