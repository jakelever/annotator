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

			if isinstance(relationTypes,list) and not relType in relationTypes:
				continue

			relArgs = [ tuple(a.split(':')) for a in infoSplit[1:] ]
			relArgs = tuple(sorted(relArgs))

			relations.append((relType,relArgs))

	return relations
			

def compareFiles(fileA,fileB,relationTypes=None):
	relationsA = loadFile(fileA,relationTypes)
	relationsB = loadFile(fileB,relationTypes)

	combined = set(list(relationsA) + list(relationsB))
	TP,FP,FN = 0,0,0
	for r in combined:
		inA = r in relationsA
		inB = r in relationsB

		if inA and inB:
			TP += 1
		elif inA:
			FP += 1
		elif inB:
			FN += 1
	return TP,FP,FN

def compareDirs(dirA,dirB,relationTypes=None,allowMissingFiles=False):
	filesA = [ f for f in os.listdir(dirA) if f.endswith('.a2') ]
	filesB = [ f for f in os.listdir(dirB) if f.endswith('.a2') ]

	assert allowMissingFiles or set(filesA) == set(filesB), 'Both annotation directories must have matching A2 filenames'
	assert len(filesA) > 0 and len(filesB) > 0, 'Directories must contain A2 files'

	filesBoth = sorted(list(set(filesA).intersection(set(filesB))))

	TPs,FPs,FNs = 0,0,0
	for f in filesBoth:
		TP,FP,FN = compareFiles(os.path.join(dirA,f),os.path.join(dirB,f),relationTypes)
		miniF1 = 0.0
		if (2*TP+FN+FP) != 0:
			miniF1 = (2*TP) / float(2*TP+FN+FP)
		if (FP > 0 or FN > 0):
			print "DIFFERENCE", f, miniF1
		else:
		 	print "MATCHING", f, miniF1

		TPs += TP
		FPs += FP
		FNs += FN

	return TPs,FPs,FNs


if __name__ == '__main__':
	parser = argparse.ArgumentParser('Compare two sets of annotations')
	parser.add_argument('--dirA',type=str,required=True,help='Directory of first set of annotations')
	parser.add_argument('--dirB',type=str,required=True,help='Directory of second set of annotations')
	parser.add_argument('--relationTypes',type=str,required=True,help='Comma-delimited list of relation types to compare (allows filtering out of non-essential ones)')
	parser.add_argument('--allowMissingFiles',action='store_true',help='Only do comparison on files that are matching (and allow missing files)')
	args = parser.parse_args()

	relationTypes = args.relationTypes.split(',')

	TPs,FPs,FNs = compareDirs(args.dirA,args.dirB,relationTypes,bool(args.allowMissingFiles))

	print(TPs,FPs,FNs)

	precision = TPs / float(TPs+FPs)
	recall = TPs / float(TPs+FNs)
	f1score = 2 * (precision*recall) / (precision+recall)

	print "Precision:", precision
	print "Recall:", recall
	print "F1_Score:", f1score
