#!/bin/bash

BASELINE=${CI_MERGE_REQUEST_TARGET_BRANCH_SHA:-$CI_MERGE_REQUEST_DIFF_BASE_SHA}

echo "ℹ️ Changes from ${BASELINE}"
git diff ${BASELINE} --name-only
echo "If this list contains more files than what you changed, then you need to rebase your branch."

echo "1️⃣ Reverting non test changes"
if [[ $(git diff ${BASELINE} --diff-filter=DM --name-only|grep -Ev "*/tests/*"|grep -v .gitlab-ci|grep -v scripts/run-tests.sh) ]]; then
git diff ${BASELINE} --diff-filter=DM --name-only|grep -Ev "*/tests/*"|grep -v .gitlab-ci|grep -v scripts/run-tests.sh|while read file;do
  echo "↩️ Reverting $file";
  git checkout ${BASELINE} -- $file;
done
fi
if [[ $(git diff ${BASELINE} --diff-filter=A --name-only|grep -Ev "*/tests/*"|grep -v .gitlab-ci|grep -v scripts/run-tests.sh) ]]; then
git diff ${BASELINE} --diff-filter=A --name-only|grep -Ev "*/tests/*"|grep -v .gitlab-ci|grep -v scripts/run-tests.sh|while read file;do
  echo "🗑️️ Deleting $file";
  git rm $file;
done
fi

echo "2️⃣ Running test changes for this branch"
EXIT_CODE=0
if [[ $(git diff ${BASELINE} --name-only|grep -E "Test.php$") ]]; then
for test in `git diff ${BASELINE} --name-only|grep -E "Test.php$"`; do
  sudo SIMPLETEST_BASE_URL="$SIMPLETEST_BASE_URL" SIMPLETEST_DB="$SIMPLETEST_DB" MINK_DRIVER_ARGS_WEBDRIVER="$MINK_DRIVER_ARGS_WEBDRIVER" -u www-data ./vendor/bin/phpunit -c core $test --log-junit=./sites/default/files/simpletest/phpunit-`echo $test|sed 's/\//_/g' `.xml || EXIT_CODE=$?;
done;
fi

echo "Exiting with EXIT_CODE=$EXIT_CODE"
exit $EXIT_CODE
