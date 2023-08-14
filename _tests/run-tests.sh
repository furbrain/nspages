#!/bin/bash -e

if [ -L "$0" ] && [ -x "$(which readlink)" ]; then
	THIS_FILE="$(readlink -mn "$0")"
else
	THIS_FILE="$0"
fi
THIS_DIR="$(dirname "$THIS_FILE")"

pushd "$THIS_DIR"
./internal/dl_geckodriver.sh

./internal/dl_dw.sh
sudo ./internal/installTestEnvironment.sh

# Fix for the issue described on https://github.com/mozilla/geckodriver/issues/2010
# (needed to run on Ubuntu 2022-04 since Firefox is installed from Snap)
export TMPDIR="$HOME/temp-firefox-profile-for-nspages-tests"
echo "Using temporary dir $TMPDIR for firefox profile (you can delete it after the tests)"
mkdir -p $TMPDIR

# "grep -v" to discard some useless and noisy log
mvn test 2>&1 | grep -v "console.warn: LoginRecipes:"
popd
