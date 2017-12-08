import os
import argparse
from collections import defaultdict
import itertools
import codecs

if __name__ == '__main__':
	parser = argparse.ArgumentParser(description='Check that the annotation contains all expected annotations')
	parser.add_argument('--a1Dir',required=True,type=str,help='Directory containing A1 files')
	parser.add_argument('--a2Dir',required=True,type=str,help='Directory containing A2 files')
	parser.add_argument('--outDir',required=True,type=str,help='Directory to output "adjusted" A2 files')
	parser.add_argument('--relTypes',required=True,type=str,help='Comma-delimited set of relations to keep')
	args = parser.parse_args()

	assert os.path.isdir(args.a1Dir)
	assert os.path.isdir(args.a2Dir)

	relTypesToKeep = set(args.relTypes.split(','))

	entityInfo = {}
	for a1File in os.listdir(args.a1Dir):
		if a1File.endswith('.a1'):
			basename = a1File.replace('.a1','')
			entityInfo[basename] = {}
			joinedPath = os.path.join(args.a1Dir,a1File)
			with open(joinedPath) as f:
				for line in f:
					split = line.strip().split()
					eID = split[0]
					eType = split[1]
					eLocs = (int(split[2]),int(split[3]))
					eText = " ".join(split[4:])
					eText = eText.replace(' ','_')
					entityInfo[basename][eID] = (eType,eLocs,eText)

	relInfo = {}
	for a2File in os.listdir(args.a2Dir):
		if a2File.endswith('.a2'):
			basename = a2File.replace('.a2','')
			relInfo[basename] = {}
			joinedPath = os.path.join(args.a2Dir,a2File)
			with open(joinedPath) as f:
				for line in f:
					split = line.strip().split()
					relID = split[0]
					relType = split[1]
					assert not ':' in relType, "Cannot deal with trigger relations"
					
					relArgs = [ tuple(relArgs.split(':')) for relArgs in split[2:] ]
					relArgsDict = { argName:argValue for argName,argValue in relArgs }

					relInfo[basename][relID] = (relType,relArgsDict)

	unexpectedFiles = [ f for f in relInfo.keys() if f not in entityInfo.keys() ]
	missingFiles = [ f for f in entityInfo.keys() if f not in relInfo.keys() ]
	assert len(missingFiles) == 0, "Missing the following files: %s)" % str(missingFiles)
	assert len(unexpectedFiles) == 0, "Did not expect the following files: %s)" % str(unexpectedFiles)
	assert set(entityInfo.keys()) == set(relInfo.keys())

	for basename in sorted(entityInfo.keys()):
		theseEntities = entityInfo[basename]
		theseRelations = relInfo[basename]

		outFilename = os.path.join(args.outDir,"%s.a2"%basename)

		newRelations = set()
		for relID,(relType,relArgsDict) in theseRelations.items():
			if not relType in relTypesToKeep:
				continue

			for argName,argValue in relArgsDict.items():
				assert argName == theseEntities[argValue][0], "Expecting name of argument to match with the type of the entity (%s != %s)" % (argName,theseEntities[argValue])

			newArgs = [ (argName,theseEntities[argValue][2]) for argName,argValue in relArgsDict.items() ]
			newArgs = sorted(newArgs)

			argTexts = [ "%s:%s" % (argName,argText) for argName,argText in newArgs ]
			argText = " ".join(argTexts)
			relText = "%s %s" % (relType,argText)
			newRelations.add(relText)

		newRelations = sorted(list(newRelations))

		with codecs.open(outFilename,'w') as outF:
			for i,relation in enumerate(newRelations):
				outF.write("E%d\t%s\n" % (i+1,relation))



