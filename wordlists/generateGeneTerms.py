"""
This script is used to build a word-list of relevant gene terms from the HUGO gene list
"""
import argparse
import sys
import codecs
from collections import defaultdict

def cleanupQuotes(text):
	"""
	Removes quotes if text starts and ends with them

	Args:
		text (str): Text to cleanup

	Returns:
		Text with quotes removed from start and end (if they existed) or original string (if not)
	"""
	if text.startswith('"') and text.endswith('"'):
		return text[1:-1]
	else:
	 	return text

def loadHGNCToUMLSID(filename):
	"""
	Loads the UMLS metathesaurus and extracts mappings from Hugo GeneIDs to Metathesaurus IDs

	Args:
		filename (str): Filename of UMLS Concept file (MRCONSO.RRF)

	Returns:
		Dictionary where each key (CUID) points to a list of strings (terms)
	"""
	mapping = {}
	with codecs.open(filename,'r','utf8') as f:
		for line in f:
			split = line.split('|')
			cuid = split[0]
			externalID = split[13]
			if externalID.startswith('HGNC:'):
				assert not externalID in mapping or mapping[externalID] == cuid, "%s maps to %s and %s" % (externalID,cuid,mapping[externalID])
				mapping[externalID] = cuid
	return mapping
	

def loadMetathesaurus(filename):
	"""
	Loads the UMLS metathesaurus into a dictionary where CUID relates to a set of terms. Only English terms are included

	Args:
		filename (str): Filename of UMLS Concept file (MRCONSO.RRF)

	Returns:
		Dictionary where each key (CUID) points to a list of strings (terms)
	"""
	meta = defaultdict(list)
	with codecs.open(filename,'r','utf8') as f:
		for line in f:
			split = line.split('|')
			cuid = split[0]
			lang = split[1]
			term = split[14]
			if lang != 'ENG':
				continue
			meta[cuid].append(term)
	return meta

if __name__ == '__main__':

	parser = argparse.ArgumentParser(description='Generate term list from NCBI gene resource')
	parser.add_argument('--ncbiGeneInfoFile', required=True, type=str, help='Path to NCBI Gene Info file')
	parser.add_argument('--umlsConceptFile', required=True, type=str, help='Path on the MRCONSO.RRF file in UMLS metathesaurus')
	parser.add_argument('--geneStopwords',required=True,type=str,help='Stopword file for genes')
	parser.add_argument('--outFile', required=True, type=str, help='Path to output wordlist file')
	args = parser.parse_args()

	genes = []

	print "Loading metathesaurus..."
	hugoToCUID = loadHGNCToUMLSID(args.umlsConceptFile)
	metathesaurus = loadMetathesaurus(args.umlsConceptFile)

	print "Loading stopwords..."
	with codecs.open(args.geneStopwords,'r','utf8') as f:
		geneStopwords = [ line.strip().lower() for line in f ]
		geneStopwords = set(geneStopwords)

	print "Processing"
	with codecs.open(args.ncbiGeneInfoFile,'r','utf8') as ncbiF:
		for line in ncbiF:
			split = line.rstrip('\n\r').split('\t')

			# Get the relevant fields for the gene
			taxonomy_id = split[0]
			type_of_gene = split[9]


			# Only select human genes
			if taxonomy_id == '9606' and type_of_gene == 'protein-coding':
				ncbi_id = split[1]
				symbol = split[2]
				synonyms = split[4].split('|')
				dbXrefs = split[5].split('|')
				nomenclature_symbol = split[10]
				nomenclature_full = split[11]

				hugo_id = None
				for dbXref in dbXrefs:
					if dbXref.startswith('HGNC:'):
						hugo_id = dbXref[5:]

				if hugo_id is None:
					print "Skipping %s as no HUGO id is found" % symbol
					continue

				# Gather up the names from the NCBI file
				allNames = [symbol,nomenclature_symbol,nomenclature_full] + synonyms

				# Add in names from the Metathesaurus
				metathesaurusTerms = []
				if hugo_id in hugoToCUID:
					cuid = hugoToCUID[hugo_id]
					metathesaurusTerms = metathesaurus[cuid]
				allNames = allNames + metathesaurusTerms


				allNames = [ x.strip().lower() for x in allNames ]
				allNames = [ x for x in allNames if x ]
				allNames = [ x for x in allNames if x != '-' ]
				allNames = [ cleanupQuotes(x) for x in allNames ]

				# Try adding a few extra synonyms (by removing the final word gene, e.g. KRAS gene -> KRAS)
				extraNames = []
				for name in allNames:
					if name.endswith(' gene'):
						extraNames.append(name[:-len(' gene')])
				allNames = allNames + extraNames
				
				# Remove instances with commas
				allNames = [ x for x in allNames if not "," in x ]

				# Remove any duplicates
				noDuplicates = sorted(list(set(allNames)))
				noDuplicates = [ g for g in noDuplicates if not g in geneStopwords ]
				noDuplicates = [ g for g in noDuplicates if len(g) >= 3 ]

				if len(noDuplicates) > 0:

					numeric_id = int(hugo_id.replace('HGNC:',''))

					gene = (numeric_id,hugo_id,noDuplicates)
					genes.append(gene)

	genes = sorted(genes)

	with codecs.open(args.outFile,'w','utf8') as outF:
		for _,hugo_id,synonyms in genes:
			# Then output to the file
			line = u"%s\t%s" % (hugo_id, u"|".join(synonyms))
			outF.write(line + "\n")

	print "Successfully output to %s" % args.outFile

		

