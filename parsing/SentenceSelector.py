import sys
import fileinput
import argparse
import time
from collections import Counter, defaultdict
import itertools
from textExtractionUtils import *
import re
from AcronymDetect import acronymDetection
import unicodedata
import string

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
		fusionCount = len(split)
		if fusionCount == 1:
			continue
			
		allGenes = True
		
		geneIDs = ['fusion']
		lookupIDCounter = Counter()
		for s in split:
			key = (s,)
			if key in lookupDict:
				isGene = False
				for type,ids in lookupDict[key]:
					if type == 'gene':
						for tmpid in ids:
							lookupIDCounter[tmpid] += 1

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

		# We're going to check if there are any lookup IDs shared among all the "fusion" terms
		# Hence this may not actually be a fusion, but just using multiple names of a gene
		# e.g. HER2/neu
		completeLookupIDs = [ id for id,count in lookupIDCounter.iteritems() if count == fusionCount ]
		if len(completeLookupIDs) > 0:
			geneIDs = completeLookupIDs
	
		if allGenes:
			#geneTxt = ",".join(map(str,geneIDs))
			termtypesAndids.append([('gene',geneIDs)])
			terms.append(tuple(origWords[i:i+1]))
			locs.append((i,i+1))
			
	return termtypesAndids,terms,locs

def cleanupVariant(variant):
	variant = variant.upper().replace('P.','')
	aminoAcidInfo = [('ALA','A'),('ARG','R'),('ASN','N'),('ASP','D'),('CYS','C'),('GLU','E'),('GLN','Q'),('GLY','G'),('HIS','H'),('ILE','I'),('LEU','L'),('LYS','K'),('MET','M'),('PHE','F'),('PRO','P'),('SER','S'),('THR','T'),('TRP','W'),('TYR','Y'),('VAL','V')]
	for longA,shortA in aminoAcidInfo:
		variant = variant.replace(longA,shortA)
	return variant

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
def selectSentences(entityRequirements, detectFusionGenes, detectMicroRNA, detectVariants, variantStopwords, detectAcronyms, detectPolymorphisms, outFile, textInput, textSourceInfo):
	pipeline = getPipeline()

	pmid = str(textSourceInfo['pmid'])
	pmcid = str(textSourceInfo['pmcid'])
	pubYear = str(textSourceInfo['pubYear'])

	print "pmid:%s pmcid:%s" % (pmid,pmcid)

	#driven1 = re.compile(re.escape('-driven'), re.IGNORECASE)
	#driven2 = re.compile(re.escape('- driven'), re.IGNORECASE)

	assert isinstance(textInput, list)
	for text in textInput:
		text = unicodedata.normalize('NFKD',text)
		text = "".join( ch if unicodedata.category(ch)!="Pd" else '-' for ch in text )
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
				variantRegex1 = r'^[ACDEFGHIKLMNPQRSTVWY][1-9][0-9]*[ACDEFGHIKLMNPQRSTVWY]$'
				variantRegex2 = r'^(p\.)?((Ala)|(Arg)|(Asn)|(Asp)|(Cys)|(Glu)|(Gln)|(Gly)|(His)|(Ile)|(Leu)|(Lys)|(Met)|(Phe)|(Pro)|(Ser)|(Thr)|(Trp)|(Tyr)|(Val))[1-9][0-9]*((Ala)|(Arg)|(Asn)|(Asp)|(Cys)|(Glu)|(Gln)|(Gly)|(His)|(Ile)|(Leu)|(Lys)|(Met)|(Phe)|(Pro)|(Ser)|(Thr)|(Trp)|(Tyr)|(Val))$'

				filteredWords = [ w for w in words if not w in variantStopwords ]
				snvMatches1 = [ not (re.match(variantRegex1,w) is None) for w in filteredWords ]
				snvMatches2 = [ not (re.match(variantRegex2,w,re.IGNORECASE) is None) for w in filteredWords ]

				snvMatches = [ (match1 or match2) for match1,match2 in zip(snvMatches1,snvMatches2) ]
				for i,(w,snvMatch) in enumerate(zip(words,snvMatches)):
					if snvMatch:
						cleaned = cleanupVariant(w)
						termtypesAndids.append([('mutation',["snv:%s"%cleaned])])
						terms.append((w,))
						locs.append((i,i+1))
			if detectPolymorphisms:
				#snvRegex = r'^[A-Z][0-9]+[A-Z]$'
				polymorphismRegex1 = r'^rs[1-9][0-9]*$'

				#filteredWords = [ w for w in words if not w in variantStopwords ]
				polyMatches = [ not (re.match(polymorphismRegex1,w) is None) for w in words ]

				for i,(w,polyMatch) in enumerate(zip(words,polyMatches)):
					if polyMatch:
						termtypesAndids.append([('mutation',['polymorphism'])])
						terms.append((w,))
						locs.append((i,i+1))

			if detectMicroRNA:
				for i,w in enumerate(words):
					lw = w.lower()
					if lw.startswith("mir-") or lw.startswith("hsa-mir-") or lw.startswith("microrna-") or (lw.startswith("mir") and lw[4] in string.digits):
						termtypesAndids.append([('gene',['mrna'])])
						terms.append((w,))
						locs.append((i,i+1))


			filtered = zip(locs,terms,termtypesAndids)
			filtered = sorted(filtered)


			# We'll attempt to merge terms (i.e. if a gene is referred to using two acronyms together)
			# Example: Hepatocellular carcinoma (HCC) or HER2/ Neu or INK4B P15
			locsToRemove = set()
			merged = []
			for i in range(len(filtered)-1):
				(startA,endA),termsA,termTypesAndIDsA = filtered[i]
				(startB,endB),termsB,termTypesAndIDsB = filtered[i+1]
				
				# Check that the terms are beside each other or separated by a /,- or (
				if startB == endA or (startB == (endA+1) and words[endA] in ['/','-','-LRB-','-RRB-']):
					idsA,idsB = set(),set()

					for termType, termIDs in termTypesAndIDsA:
						for termID in termIDs:
							idsA.add((termType,termID))
					for termType, termIDs in termTypesAndIDsB:
						for termID in termIDs:
							idsB.add((termType,termID))

					idsIntersection = idsA.intersection(idsB)

					# Detect if the second term is in brackets e.g. HER2 (ERBB2)
					firstTermInBrackets,secondTermInBrackets = False,False
					if startB == (endA+1) and endB < len(words) and words[endA] == '-LRB-' and words[endB] == '-RRB-':
						secondTermInBrackets = True
					if startB == (endA+1) and startA > 0 and words[startA-1] == '-LRB-' and words[endA] == '-RRB-':
						firstTermInBrackets = True

					# The two terms share IDs so we're going to merge them
					idsShared = (len(idsIntersection) > 0)

					# We'll also try to catch some special cases (where we can merge SNVs and Polymorphisms with some extra words
					specialMerge = False
					replacementID = None
					if not idsShared:
						specialCases = [('snv',['mutation','somatic mutation']), ('polymorphism',['polymorphism'])]

						idsA_list,idsB_list = list(idsA),list(idsB)
						termsA_flatten = " ".join(termsA).lower()
						termsB_flatten = " ".join(termsB).lower()

						for idPrefix,wordsToMerge in specialCases:
							for wordToMerge in wordsToMerge:
								# Is termA have an ID that starts with our target prefix (e.g. snv) and is the other term a special word (e.g. mutation)
								# This would allow us to merge V600E mutation
								if len(idsA_list) == 1 and idsA_list[0][1].startswith(idPrefix) and termsB_flatten == wordToMerge:
									specialMerge = True
									replacementID = termTypesAndIDsA
									break
								# Or other way around (e.g. somatic mutation (V600E)
								elif len(idsB_list) == 1 and idsB_list[0][1].startswith(idPrefix) and termsA_flatten == wordToMerge:
									specialMerge = True
									replacementID = termTypesAndIDsB
									break


					if idsShared or specialMerge:
						groupedByType = defaultdict(list)
						for termType,termID in idsIntersection:
							groupedByType[termType].append(termID)

						locsToRemove.add((startA,endA))
						locsToRemove.add((startB,endB))

						if secondTermInBrackets:
							thisLocs = (startA,endB+1)
							thisTerms = tuple(words[startA:endB+1])
						elif firstTermInBrackets:
							thisLocs = (startA-1,endB)
							thisTerms = tuple(words[startA-1:endB])
						else:
							thisLocs = (startA,endB)
							thisTerms = tuple(words[startA:endB])


						thisTermTypesAndIDs = [ (termType,sorted(termIDs)) for termType,termIDs in groupedByType.iteritems() ]

						if specialMerge:
							filtered.append((thisLocs,thisTerms,replacementID))
						else:
							filtered.append((thisLocs,thisTerms,thisTermTypesAndIDs))

			# Now we have to remove the terms marked for deletion in the previous section
			filtered = [ (locs,terms,termtypesAndids) for locs,terms,termtypesAndids in filtered if not locs in locsToRemove]
			filtered = sorted(filtered)

			# And we'll check to see if there are any obvious acronyms
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
					

			# Now we have to remove the terms marked for deletion in the previous section
			filtered = [ (locs,terms,termtypesAndids) for locs,terms,termtypesAndids in filtered if not locs in locsToRemove]
			filtered = sorted(filtered)
			
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
	parser.add_argument('--detectPolymorphisms', action='store_true', help='Whether to detect polymorphisms by looking for dbSNP IDs (e.g. rs2736100)')

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
					tmpWordlist[str(i)] = split[0].split('|')

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
		selectSentences(entityRequirements, args.detectFusionGenes, args.detectMicroRNA, args.detectVariants, variantStopwords, args.detectAcronyms, args.detectPolymorphisms, outFile, textInput, textSourceInfo)

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
			processArticleFiles(filename, outFile, selectSentencesWrapper)
		elif args.articleFilelist:
			# Extract the file list from another file
			fileList = [ f.strip() for f in args.articleFilelist]
		
			processArticleFiles(fileList, outFile, selectSentencesWrapper)
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
	

