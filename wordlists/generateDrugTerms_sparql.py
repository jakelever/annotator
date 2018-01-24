#import sparql
import SPARQLWrapper
import argparse
import codecs
import six
from collections import defaultdict

def runQuery(query):
	endpoint = 'https://query.wikidata.org/sparql'
	sparql = SPARQLWrapper.SPARQLWrapper(endpoint)
	sparql.setQuery(query)
	sparql.setReturnFormat(SPARQLWrapper.JSON)
	results = sparql.query().convert()

	return results['results']['bindings']

if __name__ == '__main__':
	parser = argparse.ArgumentParser(description='Tool to pull certain triple types from WikiData using SPARQL')
	parser.add_argument('--drugStopwords',required=True,type=str,help='Stopword file for drugs')
	parser.add_argument('--outFile',type=str,required=True,help='File to output triples')
	args = parser.parse_args()

	drugTypeID = 'Q12140'

	print("Loading stopwords...")
	with codecs.open(args.drugStopwords,'r','utf8') as f:
		stopwords = [ line.strip().lower() for line in f ]
		stopwords = set(stopwords)

	print("Gathering drugs and aliases from Wikidata")

	query = """
	SELECT ?item1 ?item1Label ?alias WHERE {
		SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
		?item1 wdt:P31 wd:Q12140.
		OPTIONAL {?item1 skos:altLabel ?alias FILTER (LANG (?alias) = "en") .}
	} 

	"""
	#LIMIT 100000

	mainterm = {}
	aliases = defaultdict(set)

	rowCount = 0
	for row in runQuery(query):
		#print(row)
		drugID = row['item1']['value']

		if 'xml:lang' in row['item1Label'] and row['item1Label']['xml:lang'] == 'en':
			mainterm[drugID] = row['item1Label']['value'].lower()

			if 'alias' in row:
				if row['alias']['xml:lang'] == 'en':
					aliases[drugID].add(row['alias']['value'].lower())

		rowCount += 1

	print ("  Got %d drugs (from %d rows)" % (len(mainterm),rowCount))

	with codecs.open(args.outFile,'w','utf-8') as f:
		keys = sorted(mainterm.keys())
		for k in keys:
			combined = aliases[k]
			combined.add(mainterm[k])
			combined = [ t for t in combined if not t in stopwords ]
			combined = sorted(combined)

			shortID = k.split('/')[-1]

			if len(combined) > 0:
				data = [shortID,mainterm[k],"|".join(combined)]
				f.write("\t".join(data) + "\n")


