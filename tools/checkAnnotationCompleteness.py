import os
import argparse
from collections import defaultdict
import itertools

if __name__ == '__main__':
	parser = argparse.ArgumentParser(description='Check that the annotation contains all expected annotations')
	parser.add_argument('--a1Dir',required=True,type=str,help='Directory containing A1 files')
	parser.add_argument('--a2Dir',required=True,type=str,help='Directory containing A2 files')
	parser.add_argument('--relTypes', required=True,type=str,help='String that describes expected entities in each relation. Separate entities with commas and entities with pipes.')
	args = parser.parse_args()

	assert os.path.isdir(args.a1Dir)
	assert os.path.isdir(args.a2Dir)

	relTypes = []
	for r in args.relTypes.split('|'):
		relType = r.split(',')
		relTypes.append(relType)

	entityInfo = defaultdict(dict)
	for a1File in os.listdir(args.a1Dir):
		if a1File.endswith('.a1'):
			basename = a1File.replace('.a1','')
			joinedPath = os.path.join(args.a1Dir,a1File)
			with open(joinedPath) as f:
				for line in f:
					split = line.strip().split()
					eID = split[0]
					eType = split[1]
					eLocs = (int(split[2]),int(split[3]))
					entityInfo[basename][eID] = (eType,eLocs)

	relInfo = defaultdict(dict)	
	for a2File in os.listdir(args.a2Dir):
		if a2File.endswith('.a2'):
			basename = a2File.replace('.a2','')
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
		
		entityTypesToIDs = defaultdict(list)
		for eID,(eType,eLocs) in theseEntities.items():
			entityTypesToIDs[eType].append(eID)

		seenArgs = []
		for relID,(relType,relArgsDict) in theseRelations.items():
			for argName,argValue in relArgsDict.items():
				assert argName == theseEntities[argValue][0], "Expecting name of argument to match with the type of the entity (%s != %s)" % (argName,theseEntities[argValue])
			seenArgs.append(relArgsDict)

		#print entityTypesToIDs
		expectedAnnotations = []
		for relType in relTypes:
			#print relType
			replacedWithIDs = [ entityTypesToIDs[eType] for eType in relType ]
			#print replacedWithIDs

			for expectedAnnotation in itertools.product(*replacedWithIDs):
				#print expectedAnnotation
				annotationDict = { entityType:entityID for entityType,entityID in zip(relType,expectedAnnotation) }

				# Check for clashing locs
				annotationLocs = [ theseEntities[eID][1] for eID in expectedAnnotation ]
				duplicateLocs = any(annotationLocs.count(x) > 1 for x in annotationLocs)

				if not duplicateLocs:
					expectedAnnotations.append(annotationDict)

			#print expectedAnnotations

		#print "seenArgs", seenArgs
		#print "expected", expectedAnnotations

		missing = [ a for a in expectedAnnotations if not a in seenArgs ]
		unexpected = [ a for a in seenArgs if not a in expectedAnnotations ]

		assert len(missing) == 0, "%s: Missing the following annotations: %s)" % (basename,str(missing))
		assert len(unexpected) == 0, "%s: Did not expect the following annotations: %s)" % (basename,str(unexpected))

		print "%s okay" % basename
		#break

