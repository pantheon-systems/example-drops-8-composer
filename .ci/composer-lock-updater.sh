#!/bin/bash

# Exit on errors
set -e

# Install CLU
echo "export PATH='$HOME/.composer/vendor/bin:$PATH'" >> $BASH_ENV
source $BASH_ENV
composer global require danielbachhuber/composer-lock-updater

# Install HUB
cd $HOME
wget -O hub.tgz https://github.com/github/hub/releases/download/v2.2.9/hub-linux-amd64-2.2.9.tgz
tar -zxvf hub.tgz
echo "export PATH='$PATH:$PWD/hub-linux-amd64-2.2.9/bin/'" >> $BASH_ENV
source $BASH_ENV

# Run composer lock updater
cd $CIRCLE_WORKING_DIRECTORY
clu https://${GITHUB_TOKEN}:x-oauth-basic@github.com/${CI_PROJECT_USERNAME}/${CI_PROJECT_REPONAME}.git