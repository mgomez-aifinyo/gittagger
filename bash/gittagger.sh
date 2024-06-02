#!/bin/bash -i
shopt -s extglob

source functions.sh
source Semver.sh

# shopt -s extglob
prepareArguments $1

cd $repodir
pullTags

lastTag=$(getLastTag "${tagList[@]}")

showMenu $lastTag
readMenuOption $lastTag
if [ $nextTag == "" ]; then
    exit 1
fi

validateTag $nextTag
if [ $? -eq 1 ]; then
    echo "Error: Invalid tag name"
    exit 1
fi

tagExists $nextTag
if [ $? == true ]; then
    echo "Error: Tag does already exist in the repository"
    exit 1
fi

createTag $nextTag "$nextTag"
echo "Tag created: $nextTag"
pushTag $nextTag
echo "Tag $nextTag pushed to remote $DEFAULT_REMOTE"
