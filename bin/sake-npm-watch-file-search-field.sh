#!/bin/bash

source ~/.nvm/nvm.sh
nvm use node

echo '------------------------------'
echo ' run build'
echo '------------------------------'
vendor/bin/webpack install vendor/sunnysideup/file-search-field/client
vendor/bin/webpack build vendor/sunnysideup/file-search-field/client --no-jquery
echo '------------------------------'
