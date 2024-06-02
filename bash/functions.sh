#!/bin/bash -i

function prepareArguments() {
  if [ -z "$1" ]
  then
    repodir="."
  else
    repodir="$1"
  fi
  DEFAULT_REPO="origin"
}

function createTag() {
  git tag -a $1 -m "$2"
}

function pushTag() {
  git push origin $1
}

function pullTags() {
  tagList=()
  while read -r line
  do
    tag=$(echo $line | cut -d ' ' -f 1)

    tagList+=("$tag")
  done < <(git for-each-ref --sort=taggerdate --format '%(refname:short) %(taggerdate)' refs/tags)
}

function getLastTag() {
  local array=("$@")
  local lastElement=""
  if [ ${#array[@]} -ne 0 ]; then
    lastElement=${array[${#array[@]}-1]}
  fi
  echo $lastElement
}

function tagExists() {
  local targetTag=$1
  for tag in "${tagList[@]}"; do
    if [[ $tag == $targetTag ]]; then
      return 1
    fi
  done
  return 0
}

showMenu() {
  currentTag=$1
  repoPath=$(git rev-parse --show-toplevel)
  currentBranch=$(git rev-parse --abbrev-ref HEAD)
  nextMajor=$(calculateNextTag $currentTag "FACTOR_MAJOR")
  nextMinor=$(calculateNextTag $currentTag "FACTOR_MINOR")
  nextRevision=$(calculateNextTag $currentTag "FACTOR_REVISION")

  echo "## GitTagger - Automatic semver based git tag creator ##"
  echo ""
  echo "Repository: $repoPath"
  echo "Branch: $currentBranch"
  echo "Last created tag: $currentTag"
  echo ""
  echo "Choose next tag by option number:"
  echo "1. Increase major ($nextMajor)"
  echo "2. Increase minor ($nextMinor)"
  echo "3. Increase revision ($nextRevision)"
  echo "4. Other/custom"
  echo ""
  echo -n "Enter option number: "
}

readMenuOption() {
  nextTag=""
  read option
  case $option in
    1)
      nextTag=$(calculateNextTag $1 "FACTOR_MAJOR")
      ;;
    2)
      nextTag=$(calculateNextTag $1 "FACTOR_MINOR")
      ;;
    3)
      nextTag=$(calculateNextTag $1 "FACTOR_REVISION")
      ;;
    4)
      echo -n "Enter custom tag: "
      read nextTag
      ;;
    *)
      echo "Invalid option. Exiting."
      exit 1
      ;;
  esac
}

validateTag() {
  tag=$1
  result=$(Semver::validate $tag)

  if [[ -z "$result" ]]; then
    return 1
  else
    return 0
  fi
}

calculateNextTag() {
  currentTag=$1
  factor=$2
  if [ $factor == "FACTOR_MAJOR" ]; then
    echo $(Semver::increment_major $currentTag)
  elif [ $factor == "FACTOR_MINOR" ]; then
    echo $(Semver::increment_minor $currentTag)
  elif [ $factor == "FACTOR_REVISION" ]; then
    echo $(Semver::increment_patch $currentTag)
  fi
}
