import sys
import fileinput
import argparse
import time
from collections import Counter
import itertools
from textExtractionUtils import *
import re
from AcronymDetect import acronymDetection

from java.util import *
from edu.stanford.nlp.pipeline import *
from edu.stanford.nlp.ling.CoreAnnotations import *
from edu.stanford.nlp.semgraph.SemanticGraphCoreAnnotations import *

pipeline = None
def getPipeline():
	global pipeline
	if pipeline is None:
		props = Properties()
		props.put("annotators", "tokenize, ssplit, pos, lemma, depparse");
		pipeline = StanfordCoreNLP(props, False)
		
	return pipeline
	
	
minipipeline = None
def getMiniPipeline():
	global minipipeline
	if minipipeline is None:
		props = Properties()
		props.put("annotators", "tokenize");
		minipipeline = StanfordCoreNLP(props, False)
	return minipipeline

def fusionGeneDetection(words, lookupDict):
	termtypesAndids,terms,locs = [],[],[]
	origWords = list(words)
	words = [ w.lower() for w in words ]

	for i,word in enumerate(words):
		split = re.split("[-/]",word)
		if len(split) == 1:
			continue
			
		allGenes = True
		
		geneIDs = ['fusion']
		for s in split:
			key = (s,)
			if key in lookupDict:
				isGene = False
				for type,ids in lookupDict[key]:
					if type == 'gene':
						idsTxt = ";".join(map(str,ids))
						geneIDs.append(idsTxt)
						isGene = True
						break
				if not isGene:
					allGenes = False
					break
			else:
				allGenes = False
				break
	
		if allGenes:
			#geneTxt = ",".join(map(str,geneIDs))
			termtypesAndids.append([('gene',geneIDs)])
			terms.append(tuple(origWords[i:i+1]))
			locs.append((i,i+1))
			
	return termtypesAndids,terms,locs
	
def getTermIDsAndLocations(np, lookupDict):
	termtypesAndids,terms,locs = [],[],[]
	# Lowercase all the tokens
	#np = [ unicodeLower(w) for w in np ]
	orignp = np
	np = [ w.lower() for w in np ]

	# The length of each search string will decrease from the full length
	# of the text down to 1
	for l in reversed(range(1, len(np)+1)):
		# We move the search window through the text
		for i in range(len(np)-l+1):
			# Extract that window of text
			s = tuple(np[i:i+l])
			# Search for it in the dictionary
			if s in lookupDict:
				# If found, save the ID(s) in the dictionary
				termtypesAndids.append(lookupDict[s])
				terms.append(tuple(orignp[i:i+l]))
				locs.append((i,i+l))
				# And blank it out
				np[i:i+l] = [ "" for _ in range(l) ]

	# Then return the found term IDs
	return termtypesAndids,terms,locs

def parseWordlistTerm(text):
	minipipeline = getMiniPipeline()
	tokens = minipipeline.process(text)
	return tuple([ token.word() for token in tokens.get(TokensAnnotation) ])
		
from __builtin__ import zip
def selectSentences(entityRequirements, detectFusionGenes, detectMicroRNA, detectVariants, variantStopwords, detectAcronyms, outFile, textInput, textSourceInfo):
	pipeline = getPipeline()

	pmid = str(textSourceInfo['pmid'])
	pmcid = str(textSourceInfo['pmcid'])
	pubYear = str(textSourceInfo['pubYear'])

	print "pmid:%s pmcid:%s" % (pmid,pmcid)

	#driven1 = re.compile(re.escape('-driven'), re.IGNORECASE)
	#driven2 = re.compile(re.escape('- driven'), re.IGNORECASE)

	assert isinstance(textInput, list)
	for text in textInput:
		text = text.strip().replace('\n', ' ').replace('\r',' ').replace('\t',' ')
		text = text.replace(u'\u2028',' ').replace(u'\u2029',' ').replace(u'\u202F',' ').replace(u'\u2012',' ').replace(u'\u2010',' ')
		text = "".join(ch for ch in text if unicodedata.category(ch)[0]!="C")
		text = text.decode('utf-8','ignore').encode("utf-8")
		text = text.strip()

		#text = driven1.sub(' driven',text)
		#text = driven2.sub(' driven',text)

		if len(text) == 0:
			continue

		assert isinstance(text, str) or isinstance(text, unicode)

		document = pipeline.process(text)
		for sentence in document.get(SentencesAnnotation):
			sentenceStart = None
			
			words = []
			positions = []
			for i,token in enumerate(sentence.get(TokensAnnotation)):
				if sentenceStart is None:
					sentenceStart = token.beginPosition()

				word = token.word()
				startPos = token.beginPosition() - sentenceStart
				endPos = token.endPosition() - sentenceStart
				words.append(word)
				positions.append((startPos,endPos))
			
			


			termtypesAndids,terms,locs = getTermIDsAndLocations(words,lookup)

			if detectFusionGenes:
				fusionTermtypesAndids,fusionTerms,fusionLocs = fusionGeneDetection(words,lookup)
				
				termtypesAndids += fusionTermtypesAndids
				terms += fusionTerms
				locs += fusionLocs
		
			if detectVariants:
				#snvRegex = r'^[A-Z][0-9]+[A-Z]$'
				snvRegex = r'^[ACDEFGHIKLMNPQRSTVWY][1-9][0-9]*[ACDEFGHIKLMNPQRSTVWY]$'
				filteredWords = [ w for w in words if not w in variantStopwords ]
				snvMatches = [ not (re.match(snvRegex,w) is None) for w in filteredWords ]

				for i,(w,snvMatch) in enumerate(zip(words,snvMatches)):
					if snvMatch:
						termtypesAndids.append([('mutation',['snv'])])
						terms.append((w,))
						locs.append((i,i+1))

			if detectMicroRNA:
				for i,w in enumerate(words):
					if w.lower().startswith("mir-") or w.lower().startswith("hsa-mir-") or w.lower().startswith("microrna-"):
						termtypesAndids.append([('gene',['mrna'])])
						terms.append((w,))
						locs.append((i,i+1))

			locsToRemove = set()
		
			if detectAcronyms:
				acronyms = acronymDetection(words)
				for (wordsStart,wordsEnd,acronymLoc) in acronyms:
					wordIsTerm = (wordsStart,wordsEnd) in locs
					acronymIsTerm = (acronymLoc,acronymLoc+1) in locs
					
					if wordIsTerm and acronymIsTerm:
						# Remove the acronym
						locsToRemove.add((acronymLoc,acronymLoc+1))
					elif acronymIsTerm:
						# Remove any terms that contain part of the spelt out word
						newLocsToRemove = [ (i,j) for i in range(wordsStart,wordsEnd) for j in range(i,wordsEnd+1) ]
						locsToRemove.update(newLocsToRemove)
					

			zipped = zip(locs,terms,termtypesAndids)
			filtered = [ (locs,terms,termtypesAndids) for locs,terms,termtypesAndids in zipped if not locs in locsToRemove]

			requirementsMatched = { entityType:False for entityType in entityRequirements }
			#requiredLocs = {}

			for loc,term,typeAndIDs in filtered:
				for entityType,_ in typeAndIDs:
					if entityType in entityRequirements:
						requirementsMatched[entityType] = True	
						#uniqLocs.add(loc)

			if all(requirementsMatched.values()):
				out = [pmid,pmcid,pubYear,unicode(sentence)]
				for (startT,endT),term,thesetypesAndIDs in filtered:
					for type,termid in thesetypesAndIDs:
						startPos = positions[startT][0]
						endPos = positions[endT-1][1]
						#termTxt = " ".join(term)
						termTxt = sentence.toString()[startPos:endPos]
						data = [ type, ",".join(map(str,termid)), startPos, endPos, termTxt ]
						txt = u"|".join(map(unicode,data))
						out.append(txt)

				outLine = "\t".join(out)
				outFile.write(outLine + "\n")
			

# It's the main bit. Yay!
if __name__ == "__main__":

	# Arguments for the command line
	parser = argparse.ArgumentParser(description='')

	parser.add_argument('--wordlistInfo', required=True, type=str, help='A tab-delimited file with an entity type and filename on each line. Each wordlist can be one column (with no IDs) or two column tab-delimited (with the ID in the first and terms in the second). The terms can be separated with a | character.')
	parser.add_argument('--entityRequirements', required=False, type=str, help='Comma-delimited list of types of entities that must be in a sentence')

	parser.add_argument('--stopwordsFile',  type=str, help='A path to a stopwords file that will be removed from term-lists (e.g. the, there)')
	parser.add_argument('--removeShortwords', help='Remove short words from any term lists (<=2 length)', action='store_true')

	parser.add_argument('--abstractsFile', type=argparse.FileType('r'), help='MEDLINE file containing abstract data')
	parser.add_argument('--articleFile', type=argparse.FileType('r'), help='PMC NXML file containing a single article')
	parser.add_argument('--articleFilelist', type=argparse.FileType('r'), help='File containing filenames of multiple PMC NXML files')

	parser.add_argument('--detectFusionGenes', action='store_true', help='Whether to try and detect fusion terms using the "gene" type')
	parser.add_argument('--detectMicroRNA', action='store_true', help='Whether to detect microRNA mentions and add to the "gene" type')
	parser.add_argument('--detectVariants', action='store_true', help='Whether to detect variants and add to the "mutation" type')
	parser.add_argument('--variantsStopwordsFile', type=str, help='File containing stopwords terms to filter out extra variants before adding to "mutation" type')
	parser.add_argument('--detectAcronyms', action='store_true', help='Whether to detect acronyms and filter out when the full term is there')

	parser.add_argument('--outFile', type=str, help='File to output cooccurrences')

	args = parser.parse_args()

	wordlistInfo = {}
	print "Loading wordlist info..."
	with open(args.wordlistInfo,'r') as wordlistF:
		for wordlistLine in wordlistF:
			termType,wordlistFilename = wordlistLine.strip().split('\t')
			wordlistInfo[termType] = wordlistFilename
	
	wordlists = {}
	for termType,wordlistFilename in wordlistInfo.iteritems():
		print "Loading wordlist [%s]..." % termType
		tmpWordlist = {}
		with codecs.open(wordlistFilename, "r", "utf-8") as f:
			columnSet,isTwoColumn = False,False
			for i,line in enumerate(f):
				split = line.strip().split('\t')
				if not columnSet:
					assert len(split) == 1 or len(split) == 2, "Word-list file (%s) must be one or two columned" % wordlistFilename
					isTwoColumn = (len(split)==2)
					columnSet = True

				if isTwoColumn:
					assert len(split) == 2, "Expecting two columns for entire file (%s)" % wordlistFilename
					tmpWordlist[split[0]] = split[1].split('|')
				else:
					assert len(split) == 1, "Expecting one column for entire file (%s)" % wordlistFilename
					tmpWordlist[i] = split[0].split('|')

		wordlists[termType] = tmpWordlist

	if args.detectFusionGenes:
		assert "gene" in wordlists, "Can only use --detectFusionGenes if 'gene' is one of the wordlists"

	if args.entityRequirements:
		print "Loading entity requirements..."
		entityRequirements = set(args.entityRequirements.lower().strip().split(','))
	else:
		print "Setting entity argument requirements to all known entity types: ", wordlists.keys()
		entityRequirements = set(wordlists.keys())

	print "Generating lookup table..."
	duplicates = set()
	lookup = defaultdict(list)
	for termType,mainDict in wordlists.iteritems():
		for id,lst in mainDict.iteritems():
			lst = list(set(lst))
			keys = set( [ parseWordlistTerm(x.lower()) for x in lst ] )
			for key in keys:
				matching = None
				if key in lookup:
					prevItems = lookup[key]
					
					matching = None
					for i,(prevType,prevIds) in enumerate(prevItems):
						if prevType == termType:
							matching = i
							break
					
				if matching is None:
					item = (termType,[id])
					lookup[key].append(item)
				else:
					prevItems[matching][1].append(id)
					lookup[key] = prevItems
			 
	stopwords = []
	if args.stopwordsFile:
		with codecs.open(args.stopwordsFile, "r", "utf-8") as f4:
			stopwords = [ line.strip() for line in f4 ]

		print "Removing stopwords..."
		for stopword in stopwords:
			key = tuple(stopword.lower().split(' '))
			if key in lookup:
				del lookup[key]

	variantStopwords = set()
	if args.variantsStopwordsFile:
		with codecs.open(args.variantsStopwordsFile, "r", "utf-8") as f4:
			variantStopwords = [ line.strip() for line in f4 ]
		variantStopwords = set(variantStopwords)

	if args.removeShortwords:
		print "Removing short words..."
		lookup = { key:val for key,val in lookup.iteritems() if not (len(key)==1 and len(key[0]) <= 2) }

	outFile = codecs.open(args.outFile, "w", "utf-8")

	# Create wrapper that passes in the entity requirements
	def selectSentencesWrapper(outFile, textInput, textSourceInfo):
		selectSentences(entityRequirements, args.detectFusionGenes, args.detectMicroRNA, args.detectVariants, variantStopwords, args.detectAcronyms, outFile, textInput, textSourceInfo)

	print "Starting processing..."
	startTime = time.time()
	# And now we try to process either an abstract file, single article file or multiple
	# article files
	try:
		if args.abstractsFile:
			processAbstractFile(args.abstractsFile, outFile, selectSentencesWrapper)
		elif args.articleFile:
			# Just pull the filename and pass that, instead of the object
			filename = args.articleFile.name
			args.articleFile.close()
			processArticleFiles(filename, outFile, selectSentences)
		elif args.articleFilelist:
			# Extract the file list from another file
			fileList = [ f.strip() for f in args.articleFilelist]
		
			processArticleFiles(fileList, outFile, selectSentences)
	except:
		print "Unexpected error:", sys.exc_info()[0]
		print "COMMAND: " + " ".join(sys.argv)
		raise

	endTime = time.time()
	duration = endTime - startTime
	print "Processing Time: ", duration
	
	# Print completion if we were working on an outFile
	if args.outFile:
		print "Finished output to:", args.outFile
	

