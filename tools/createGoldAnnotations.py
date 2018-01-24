import argparse
import os

def loadFile(filename,relationTypes=None):
	assert relationTypes is None or isinstance(relationTypes,list)

	relations = []
	with open(filename) as f:
		for line in f:
			relID,relInfo = line.strip().split('\t')
			infoSplit = relInfo.split(' ')
			relType = infoSplit[0]

			#if isinstance(relationTypes,list) and not relType in relationTypes:
			#	continue

			relArgs = [ tuple(a.split(':')) for a in infoSplit[1:] ]
			relArgs = tuple(sorted(relArgs))

			relations.append((relType,relArgs))

	return relations
	
def saveFile(filename,relations):
	with open(filename,'w') as f:
		for i,relation in enumerate(relations):
			(relType,relArgs) = relation
			relArgs = [ "%s:%s" % (argName,argValue) for argName,argValue in relArgs ]
			relArgsTxt = " ".join(relArgs)
			relTxt = "R%d\t%s %s" % (i+1,relType,relArgsTxt)
			f.write(relTxt + "\n")
			
	return relations
			

def createGoldFile(fileA,fileB,fileC,outFile,relationTypes=None,addNone=False):
	basename = os.path.basename(fileA)
	relationsA = loadFile(fileA,relationTypes)
	relationsB = loadFile(fileB,relationTypes)
	
	hasFileC = os.path.isfile(fileC)
	if hasFileC:
		relationsC = loadFile(fileC,relationTypes)
	else:
		relationsC = []

	combined = set(list(relationsA) + list(relationsB))
	goldSet = []
	for r in combined:
		relType,relArgs = r
		if not relType in relationTypes:
			continue

		inA = r in relationsA
		inB = r in relationsB
		inC = r in relationsC

		if inA and inB:
			print("AGREEMENT in %s" % basename)
			# DirA and DirB have voted majority
			goldSet.append(r)
		else:
			print("DISAGREEMENT in %s" % basename)
			assert hasFileC, "Disagreement exists between first two annotators, need third annotator to vote (%s)" % fileA
			#if not hasFileC:
				#print hasFileC
			#	print fileA
			if inC:
				goldSet.append(r)

	if addNone:
		allArgs = set([ relArgs for relType,relArgs in combined ])
		goldArgs = set([ relArgs for relType,relArgs in goldSet ])
		unassignedArgs = [ relArgs for relArgs in allArgs if not relArgs in goldArgs ]
		noneRelations = [ ('None',relArgs) for relArgs in unassignedArgs ]
		goldSet += noneRelations
		
	saveFile(outFile,goldSet)

def createGoldDir(dirA,dirB,dirC,outDir,relationTypes=None,addNone=False):
	filesA = [ f for f in os.listdir(dirA) if f.endswith('.a2') ]
	filesB = [ f for f in os.listdir(dirB) if f.endswith('.a2') ]
	filesC = [ f for f in os.listdir(dirB) if f.endswith('.a2') ]

	assert set(filesA) == set(filesB), 'Both annotation directories must have matching A2 filenames'
	assert len(filesA) > 0 and len(filesB) > 0 and len(filesC) > 0, 'Directories must contain A2 files'

	TPs,FPs,FNs = 0,0,0
	for f in filesA:
		createGoldFile(os.path.join(dirA,f),os.path.join(dirB,f),os.path.join(dirC,f),os.path.join(outDir,f),relationTypes,addNone)

if __name__ == '__main__':
	parser = argparse.ArgumentParser('Compare two sets of annotations')
	parser.add_argument('--dirA',type=str,required=True,help='Directory of first set of annotations')
	parser.add_argument('--dirB',type=str,required=True,help='Directory of second set of annotations')
	parser.add_argument('--dirC',type=str,required=True,help='Directory of third set of annotations')
	parser.add_argument('--outDir',type=str,required=True,help='Directory of third set of annotations')
	parser.add_argument('--relationTypes',type=str,required=True,help='Comma-delimited list of relation types to filter for')
	parser.add_argument('--addNone',action='store_true',help="Whether to add None relations (for those that don't match a provided relationType")
	args = parser.parse_args()

	relationTypes = args.relationTypes.split(',')

	createGoldDir(args.dirA,args.dirB,args.dirC,args.outDir,relationTypes,bool(args.addNone))


