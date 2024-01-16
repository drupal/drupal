#!/bin/bash

export TARGET_BRANCH=${CI_MERGE_REQUEST_TARGET_BRANCH_NAME}${CI_COMMIT_BRANCH}
git fetch -vn --depth=50 origin "+refs/heads/$TARGET_BRANCH:refs/heads/$TARGET_BRANCH"

echo "ℹ️ Changes from ${TARGET_BRANCH}"
git diff ${CI_MERGE_REQUEST_DIFF_BASE_SHA} --name-only
echo "If this list contains more files than what you changed, then you need to rebase your branch."

echo "1️⃣ Reverting non test changes"
if [[ $(git diff ${CI_MERGE_REQUEST_DIFF_BASE_SHA} --diff-filter=DM --name-only|grep -Ev "*/tests/*"|grep -v .gitlab-ci|grep -v scripts/run-tests.sh) ]]; then
git diff ${CI_MERGE_REQUEST_DIFF_BASE_SHA} --diff-filter=DM --name-only|grep -Ev "*/tests/*"|grep -v .gitlab-ci|grep -v scripts/run-tests.sh|while read file;do
  echo "↩️ Reverting $file";
  git checkout refs/heads/${TARGET_BRANCH} -- $file;
done
fi
if [[ $(git diff ${CI_MERGE_REQUEST_DIFF_BASE_SHA} --diff-filter=A --name-only|grep -Ev "*/tests/*"|grep -v .gitlab-ci|grep -v scripts/run-tests.sh) ]]; then
git diff ${CI_MERGE_REQUEST_DIFF_BASE_SHA} --diff-filter=A --name-only|grep -Ev "*/tests/*"|grep -v .gitlab-ci|grep -v scripts/run-tests.sh|while read file;do
  echo "🗑️️ Deleting $file";
  git rm $file;
done
fi

echo "2️⃣ Running test changes for this branch"
if [[ $(git diff ${CI_MERGE_REQUEST_DIFF_BASE_SHA} --name-only|grep -E "Test.php$") ]]; then
for test in `git diff ${CI_MERGE_REQUEST_DIFF_BASE_SHA} --name-only|grep -E "Test.php$"`; do
  sudo SIMPLETEST_BASE_URL="$SIMPLETEST_BASE_URL" SIMPLETEST_DB="$SIMPLETEST_DB" MINK_DRIVER_ARGS_WEBDRIVER="$MINK_DRIVER_ARGS_WEBDRIVER" -u www-data ./vendor/bin/phpunit -c core $test --log-junit=./sites/default/files/simpletest/phpunit-`echo $test|sed 's/\//_/g' `.xml;
done;
fi
