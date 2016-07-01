#!/bin/bash

set -e
# remember path prefix as called, source functions
PFX=$(dirname $0)
cd $PFX
source "functions.inc.sh"
set +e

RES_BASE="results"

# first clean out *ALL* results, so we notice immediately if not all tests
# were run when this script terminates:
rm -rf "$RES_BASE"

# by default all tests will be run, only if the special variable "RUN_TESTS" is
# set, we limit the tests to the ones specified there, e.g. usable like this:
# > RUN_TESTS="test-001__* test-002__*" ./run_tests.sh
if [ -z "$RUN_TESTS" ] ; then
    RUN_TESTS=test-*__*.sh
fi

for TEST in $RUN_TESTS ; do
    set -e
    # parse the "short" test name (basically the number):
    SHORT=$(echo $TEST | sed 's,__.*,,')
    RES="$RES_BASE/$SHORT"
    rm -rf $RES
    mkdir -p $RES
    set +e
    echo "++++++++++++++++++++ Running $SHORT ($TEST) ++++++++++++++++++++"
    check_spooldirs_clean
    STDOUT="$RES/stdout"
    STDERR="$RES/stderr"
    EXITVAL="$RES/exitval"
    bash $TEST >$STDOUT 2>$STDERR
    RET=$?
    echo $RET > $EXITVAL
    # generate the "stripped" version of stdout / stderr (without UID hashes):
    cat $STDOUT | sed -s 's/[0-9a-f]\{40\}/UID_STRIPPED/g' > ${STDOUT}.stripped
    cat $STDERR | sed -s 's/[0-9a-f]\{40\}/UID_STRIPPED/g' > ${STDERR}.stripped
    echo "Test '$SHORT' finished (exit code: $RET, results in '$PFX/$RES')."
    echo
done
