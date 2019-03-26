#!/bin/bash

# Variables
BUILD_DIR=$(pwd)
GITHUB_API_URL="https://api.github.com/repos/$CIRCLE_PROJECT_USERNAME/$CIRCLE_PROJECT_REPONAME"

# Check if we are NOT on the master branch and this is a PR
if [[ ${CURRENT_BRANCH} != "master" && -z ${CI_PR_URL} ]];
then
  echo -e "\nVisual regression tests will only run if we are not on the master branch and making a pull request"
  exit 0;
fi

echo -e "\nProcessing pull request #$PR_NUMBER"

COMPOSER_LOCK_COMMIT=$(git log -1 --format=format:%H --full-diff composer.lock)
LATEST_COMMIT=$(git rev-parse HEAD)

if [ $COMPOSER_LOCK_COMMIT != $LATEST_COMMIT ];
then
    echo -e "\nVisual regression tests will only run when composer.lock has changed"
    exit 0;
fi

# Stash site URLs
MULTIDEV_SITE_URL="https://$TERMINUS_ENV-$TERMINUS_SITE.pantheonsite.io/"
LIVE_SITE_URL="https://live-$TERMINUS_SITE.pantheonsite.io/"

# Ping the multidev environment to wake it from sleep
echo -e "\nPinging the ${TERMINUS_ENV} multidev environment to wake it from sleep..."
curl -I "$MULTIDEV_SITE_URL" >/dev/null

# Ping the live environment to wake it from sleep
echo -e "\nPinging the live environment to wake it from sleep..."
curl -I "$LIVE_SITE_URL" >/dev/null

# Check for custom backstop.json
if [ ! -f backstop.json ]; then
	# Create Backstop config file with dynamic URLs
	echo -e "\nCreating backstop.js config file..."
	cat backstop.json.default | jq ".scenarios[0].url = \"$LIVE_SITE_URL\" | .scenarios[0].referenceUrl = \"$MULTIDEV_SITE_URL\" " > backstop.json
fi

# Backstop visual regression
echo -e "\nRunning backstop reference..."

echo -e "\nRunning backstop reference on ${LIVE_SITE_URL}..."
backstop reference

echo -e "\nRunning backstop test on ${MULTIDEV_SITE_URL}..."
VISUAL_REGRESSION_RESULTS=$(backstop test || echo 'true')

echo "${VISUAL_REGRESSION_RESULTS}"

# Rsync files to ARTIFACTS_FULL_DIR
echo -e "\nRsyincing backstop_data files to $ARTIFACTS_FULL_DIR..."
rsync -rlvz backstop_data $ARTIFACTS_FULL_DIR

DIFF_IMAGE=$(find ./backstop_data -type f -name "*.png" | grep diff | grep desktop | head -n 1)

if [ ! -f $DIFF_IMAGE ]; then
	echo -e "\nDiff image file $DIFF_IMAGE not found!"
fi

DIFF_IMAGE_URL="$ARTIFACTS_DIR_URL/$DIFF_IMAGE"

DIFF_REPORT="$ARTIFACTS_FULL_DIR/backstop_data/html_report/index.html"

if [ ! -f $DIFF_REPORT ]; then
	echo -e "\nDiff report file $DIFF_REPORT not found!"
	exit 1
fi

DIFF_REPORT_URL="$ARTIFACTS_DIR_URL/backstop_data/html_report/index.html"

REPORT_LINK="[![Visual report]($DIFF_IMAGE_URL)]($DIFF_REPORT_URL)"

if [[ ${VISUAL_REGRESSION_RESULTS} == *"Mismatch errors found"* ]]
then
	# visual regression failed
	echo -e "\nVisual regression test failed!"
	PR_MESSAGE="Visual regression test failed! $REPORT_LINK"
else
	# visual regression passed
	REPORT_LINK="\n\nView the [visual regression test report]($DIFF_REPORT_URL)"
	echo -e "\n\nVisual regression test passed!"
	PR_MESSAGE="Visual regression test passed! $REPORT_LINK"
fi

# Post the image back to the pull request on GitHub
echo -e "\nPosting visual regression results back to PR #$PR_NUMBER "
curl -i -u "$CIRCLE_PROJECT_USERNAME:$GITHUB_TOKEN" -d "{\"body\": \"$PR_MESSAGE\"}" $GITHUB_API_URL/issues/$PR_NUMBER/comments

if [[ ${VISUAL_REGRESSION_RESULTS} == *"Mismatch errors found"* ]]
then
    exit 1
fi