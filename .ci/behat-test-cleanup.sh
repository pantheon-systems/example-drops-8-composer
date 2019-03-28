#!/bin/bash

LAST_GIT_COMMIT_MESSAGE=$(git log -1 --pretty=%B)

# Never run behat tests if "[skip-behat]" is in the last commit message
if [[ ${LAST_GIT_COMMIT_MESSAGE} == *"[skip-behat]"* ]]
then
  echo -e "\nSkipping Behat tests because the latest commit message demands it"
  exit 0
fi

if [[ ${CURRENT_BRANCH} != "master" && -z ${CI_PR_URL} ]];
then
  echo -e "CI will only deploy to Pantheon if on the master branch or creating a pull requests.\n"
  exit 0;
fi

echo "::::::::::::::::::::::::::::::::::::::::::::::::"
echo "Behat clean up on site: $TERMINUS_SITE.$TERMINUS_ENV"
echo "::::::::::::::::::::::::::::::::::::::::::::::::"
echo

# Clear site cache
terminus -n env:clear-cache $TERMINUS_SITE.$TERMINUS_ENV