#!/bin/bash
#
# This script performs code quality checks.
#
# @internal
#   This script is not covered by Drupal core's backwards compatibility promise.
#   It exists only for core development purposes.
#
# The script makes the following checks:
# - Spell checking.
# - File modes.
# - No changes to core/node_modules directory.
# - PHPCS checks PHP and YAML files.
# - PHPStan checks PHP files.
# - ESLint checks JavaScript and YAML files.
# - Stylelint checks CSS files.
# - Checks .pcss.css and .css files are equivalent.

# cSpell:disable

# Searches an array.
contains_element() {
  local e
  for e in ${@:2}; do [[ "$e" == "$1" ]] && return 0; done
  return 1
}

CACHED=0
DRUPALCI=0
BRANCH=""
while test $# -gt 0; do
  case "$1" in
    -h|--help)
      echo "Drupal code quality checks"
      echo " "
      echo "options:"
      echo "-h, --help                show brief help"
      echo "--branch BRANCH           creates list of files to check by comparing against a branch"
      echo "--cached                  checks staged files"
      echo "--drupalci                a special mode for DrupalCI"
      echo " "
      echo "Example usage: sh ./core/scripts/dev/commit-code-check.sh --branch 9.2.x"
      exit 0
      ;;
    --branch)
      BRANCH="$2"
      if [[ "$BRANCH" == "" ]]; then
        printf "The --branch option requires a value. For example: --branch 9.2.x\n"
        exit;
      fi
      shift 2
      ;;
    --cached)
      CACHED=1
      shift
      ;;
    --drupalci)
      DRUPALCI=1
      shift
      ;;
    *)
      break
      ;;
  esac
done

# Set up variables to make colored output simple. Color output is disabled on
# DrupalCI because it is breaks reporting.
# @todo https://www.drupal.org/project/drupalci_testbot/issues/3181869
if [[ "$DRUPALCI" == "1" ]]; then
  red=""
  green=""
  reset=""
  DRUPAL_VERSION=$(php -r "include 'vendor/autoload.php'; print preg_replace('#\.[0-9]+-dev#', '.x', \Drupal::VERSION);")
  GIT="sudo -u www-data git"
else
  red=$(tput setaf 1 && tput bold)
  green=$(tput setaf 2)
  reset=$(tput sgr0)
  GIT="git"
fi

# Gets list of files to check.
if [[ "$BRANCH" != "" ]]; then
  FILES=$($GIT diff --name-only $BRANCH HEAD);
elif [[ "$CACHED" == "0" ]]; then
  # For DrupalCI patch testing or when running without --cached or --branch,
  # list of all changes in the working directory.
  FILES=$($GIT ls-files --other --modified --exclude-standard --exclude=vendor)
else
  # Check staged files only.
  if $GIT rev-parse --verify HEAD >/dev/null 2>&1
  then
    AGAINST=HEAD
  else
    # Initial commit: diff against an empty tree object
    AGAINST=4b825dc642cb6eb9a060e54bf8d69288fbee4904
  fi
  FILES=$($GIT diff --cached --name-only $AGAINST);
fi

if [[ "$FILES" == "" ]] && [[ "$DRUPALCI" == "1" ]]; then
  # If the FILES is empty we might be testing a merge request on DrupalCI. We
  # need to diff against the Drupal branch or tag related to the Drupal version.
  printf "Creating list of files to check by comparing branch to %s\n" "$DRUPAL_VERSION"
  # On DrupalCI there's a merge commit so we can compare to HEAD~1.
  FILES=$($GIT diff --name-only HEAD~1 HEAD);
fi

TOP_LEVEL=$($GIT rev-parse --show-toplevel)

# This variable will be set to one when the file core/phpcs.xml.dist is changed.
PHPCS_XML_DIST_FILE_CHANGED=0

# This variable will be set to one when the files core/phpstan-baseline.neon or
# core/phpstan.neon.dist are changed.
PHPSTAN_DIST_FILE_CHANGED=0

# This variable will be set to one when one of the eslint config file is
# changed:
#  - core/.eslintrc.passing.json
#  - core/.eslintrc.json
#  - core/.eslintrc.jquery.json
ESLINT_CONFIG_PASSING_FILE_CHANGED=0

# This variable will be set to one when the stylelint config file is changed.
# changed:
#  - core/.stylelintignore
#  - core/.stylelintrc.json
STYLELINT_CONFIG_FILE_CHANGED=0

# This variable will be set when a Drupal-specific CKEditor 5 plugin has changed
# it is used to make sure the compiled JS is valid.
CKEDITOR5_PLUGINS_CHANGED=0

# Build up a list of absolute file names.
ABS_FILES=
for FILE in $FILES; do
  if [ -f "$TOP_LEVEL/$FILE" ]; then
    ABS_FILES="$ABS_FILES $TOP_LEVEL/$FILE"
  fi

  if [[ $FILE == "core/phpcs.xml.dist" ]]; then
    PHPCS_XML_DIST_FILE_CHANGED=1;
  fi;

  if [[ $FILE == "core/phpstan-baseline.neon" || $FILE == "core/phpstan.neon.dist" ]]; then
    PHPSTAN_DIST_FILE_CHANGED=1;
  fi;

  if [[ $FILE == "core/.eslintrc.json" || $FILE == "core/.eslintrc.passing.json" || $FILE == "core/.eslintrc.jquery.json" ]]; then
    ESLINT_CONFIG_PASSING_FILE_CHANGED=1;
  fi;

  if [[ $FILE == "core/.stylelintignore" || $FILE == "core/.stylelintrc.json" ]]; then
    STYLELINT_CONFIG_FILE_CHANGED=1;
  fi;

  # If JavaScript packages change, then rerun all JavaScript style checks.
  if [[ $FILE == "core/package.json" || $FILE == "core/yarn.lock" ]]; then
    ESLINT_CONFIG_PASSING_FILE_CHANGED=1;
    STYLELINT_CONFIG_FILE_CHANGED=1;
  fi;

  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.js$ ]] && [[ $FILE =~ ^core/modules/ckeditor5/js/build || $FILE =~ ^core/modules/ckeditor5/js/ckeditor5_plugins ]]; then
    CKEDITOR5_PLUGINS_CHANGED=1;
  fi;
done

# Exit early if there are no files.
if [[ "$ABS_FILES" == "" ]]; then
  printf "There are no files to check. If you have staged a commit use the --cached option.\n"
  exit;
fi;

# This script assumes that composer install and yarn install have already been
# run and all dependencies are updated.
FINAL_STATUS=0

DEPENDENCIES_NEED_INSTALLING=0
# Ensure PHP development dependencies are installed.
# @todo https://github.com/composer/composer/issues/4497 Improve this to
#  determine if dependencies in the lock file match the installed versions.
#  Using composer install --dry-run is not valid because it would depend on
#  user-facing strings in Composer.
if ! [[ -f 'vendor/bin/phpcs' ]]; then
  printf "Drupal's PHP development dependencies are not installed. Run 'composer install' from the root directory.\n"
  DEPENDENCIES_NEED_INSTALLING=1;
fi

cd "$TOP_LEVEL/core"

# Ensure JavaScript development dependencies are installed.
yarn check -s 2>/dev/null
if [ "$?" -ne "0" ]; then
  printf "Drupal's JavaScript development dependencies are not installed or cannot be resolved. Run 'yarn install' inside the core directory, or 'yarn check -s' to list other errors.\n"
  DEPENDENCIES_NEED_INSTALLING=1;
fi

if [ $DEPENDENCIES_NEED_INSTALLING -ne 0 ]; then
  exit 1;
fi

# Check all files for spelling in one go for better performance.
yarn run -s spellcheck --no-must-find-files --root $TOP_LEVEL $ABS_FILES
if [ "$?" -ne "0" ]; then
  # If there are failures set the status to a number other than 0.
  FINAL_STATUS=1
  printf "\nCSpell: ${red}failed${reset}\n"
else
  printf "\nCSpell: ${green}passed${reset}\n"
fi
cd "$TOP_LEVEL"

# Add a separator line to make the output easier to read.
printf "\n"
printf -- '-%.0s' {1..100}
printf "\n"

# Run PHPStan on all files on DrupalCI or when phpstan files are changed.
# APCu is disabled to ensure that the composer classmap is not corrupted.
if [[ $PHPSTAN_DIST_FILE_CHANGED == "1" ]] || [[ "$DRUPALCI" == "1" ]]; then
  printf "\nRunning PHPStan on *all* files.\n"
  php -d apc.enabled=0 -d apc.enable_cli=0 vendor/bin/phpstan analyze --no-progress --configuration="$TOP_LEVEL/core/phpstan.neon.dist"
else
  # Only run PHPStan on changed files locally.
  printf "\nRunning PHPStan on changed files.\n"
  php -d apc.enabled=0 -d apc.enable_cli=0 vendor/bin/phpstan analyze --no-progress --configuration="$TOP_LEVEL/core/phpstan-partial.neon" $ABS_FILES
fi

if [ "$?" -ne "0" ]; then
  # If there are failures set the status to a number other than 0.
  FINAL_STATUS=1
  printf "\nPHPStan: ${red}failed${reset}\n"
else
  printf "\nPHPStan: ${green}passed${reset}\n"
fi

# Add a separator line to make the output easier to read.
printf "\n"
printf -- '-%.0s' {1..100}
printf "\n"

# Run PHPCS on all files on DrupalCI or when phpcs files are changed.
if [[ $PHPCS_XML_DIST_FILE_CHANGED == "1" ]] || [[ "$DRUPALCI" == "1" ]]; then
  # Test all files with phpcs rules.
  vendor/bin/phpcs -ps --parallel=$(nproc) --standard="$TOP_LEVEL/core/phpcs.xml.dist"
  PHPCS=$?
  if [ "$PHPCS" -ne "0" ]; then
    # If there are failures set the status to a number other than 0.
    FINAL_STATUS=1
    printf "\nPHPCS: ${red}failed${reset}\n"
  else
    printf "\nPHPCS: ${green}passed${reset}\n"
  fi
  # Add a separator line to make the output easier to read.
  printf "\n"
  printf -- '-%.0s' {1..100}
  printf "\n"
fi

# When the eslint config has been changed, then eslint must check all files.
if [[ $ESLINT_CONFIG_PASSING_FILE_CHANGED == "1" ]]; then
  cd "$TOP_LEVEL/core"
  yarn run -s lint:core-js-passing "$TOP_LEVEL/core"
  CORRECTJS=$?
  if [ "$CORRECTJS" -ne "0" ]; then
    # If there are failures set the status to a number other than 0.
    FINAL_STATUS=1
    printf "\neslint: ${red}failed${reset}\n"
  else
    printf "\neslint: ${green}passed${reset}\n"
  fi
  cd $TOP_LEVEL
  # Add a separator line to make the output easier to read.
  printf "\n"
  printf -- '-%.0s' {1..100}
  printf "\n"
fi

# When the stylelint config has been changed, then stylelint must check all files.
if [[ $STYLELINT_CONFIG_FILE_CHANGED == "1" ]]; then
  cd "$TOP_LEVEL/core"
  yarn run -s lint:css
  if [ "$?" -ne "0" ]; then
    # If there are failures set the status to a number other than 0.
    FINAL_STATUS=1
    printf "\nstylelint: ${red}failed${reset}\n"
  else
    printf "\nstylelint: ${green}passed${reset}\n"
  fi
  cd $TOP_LEVEL
  # Add a separator line to make the output easier to read.
  printf "\n"
  printf -- '-%.0s' {1..100}
  printf "\n"
fi

# When a Drupal-specific CKEditor 5 plugin changed ensure that it is compiled
# properly. Only check on DrupalCI, since we're concerned about the build being
# run with the expected package versions and making sure the result of the build
# is in sync and conform to expectations.
if [[ "$DRUPALCI" == "1" ]] && [[ $CKEDITOR5_PLUGINS_CHANGED == "1" ]]; then
  cd "$TOP_LEVEL/core"
  yarn run -s check:ckeditor5
  if [ "$?" -ne "0" ]; then
    # If there are failures set the status to a number other than 0.
    FINAL_STATUS=1
    printf "\nDrupal-specific CKEditor 5 plugins: ${red}failed${reset}\n"
  else
    printf "\nDrupal-specific CKEditor 5 plugins: ${green}passed${reset}\n"
  fi
  cd $TOP_LEVEL
  # Add a separator line to make the output easier to read.
  printf "\n"
  printf -- '-%.0s' {1..100}
  printf "\n"
fi

for FILE in $FILES; do
  STATUS=0;
  # Print a line to separate spellcheck output from per file output.
  printf "Checking %s\n" "$FILE"
  printf "\n"

  # Ensure the file still exists (i.e. is not being deleted).
  if [ -a $FILE ]; then
    if [ ${FILE: -3} != ".sh" ]; then
      # Ensure the file has the correct mode.
      STAT="$(stat -f "%A" $FILE 2>/dev/null)"
      if [ $? -ne 0 ]; then
        STAT="$(stat -c "%a" $FILE 2>/dev/null)"
      fi
      if [ "$STAT" -ne "644" ]; then
        printf "${red}check failed:${reset} file $FILE should be 644 not $STAT\n"
        STATUS=1
      fi
    fi
  fi

  # Don't commit changes to vendor.
  if [[ "$FILE" =~ ^vendor/ ]]; then
    printf "${red}check failed:${reset} file in vendor directory being committed ($FILE)\n"
    STATUS=1
  fi

  # Don't commit changes to core/node_modules.
  if [[ "$FILE" =~ ^core/node_modules/ ]]; then
    printf "${red}check failed:${reset} file in core/node_modules directory being committed ($FILE)\n"
    STATUS=1
  fi

  ############################################################################
  ### PHP AND YAML FILES
  ############################################################################
  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.(inc|install|module|php|profile|test|theme|yml)$ ]] && [[ $PHPCS_XML_DIST_FILE_CHANGED == "0" ]] && [[ "$DRUPALCI" == "0" ]]; then
    # Test files with phpcs rules.
    vendor/bin/phpcs "$TOP_LEVEL/$FILE" --standard="$TOP_LEVEL/core/phpcs.xml.dist"
    PHPCS=$?
    if [ "$PHPCS" -ne "0" ]; then
      # If there are failures set the status to a number other than 0.
      STATUS=1
    else
      printf "PHPCS: $FILE ${green}passed${reset}\n"
    fi
  fi

  ############################################################################
  ### YAML FILES
  ############################################################################
  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.yml$ ]]; then
    # Test files with ESLint.
    cd "$TOP_LEVEL/core"
    node ./node_modules/eslint/bin/eslint.js --quiet --resolve-plugins-relative-to . "$TOP_LEVEL/$FILE"
    YAMLLINT=$?
    if [ "$YAMLLINT" -ne "0" ]; then
      # If there are failures set the status to a number other than 0.
      STATUS=1
    else
      printf "ESLint: $FILE ${green}passed${reset}\n"
    fi
    cd $TOP_LEVEL
  fi

  ############################################################################
  ### JAVASCRIPT FILES
  ############################################################################
  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.js$ ]]; then
    cd "$TOP_LEVEL/core"
    # Check the coding standards.
    node ./node_modules/eslint/bin/eslint.js --quiet --config=.eslintrc.passing.json "$TOP_LEVEL/$FILE"
    JSLINT=$?
    if [ "$JSLINT" -ne "0" ]; then
      # No need to write any output the node command will do this for us.
      STATUS=1
    else
      printf "ESLint: $FILE ${green}passed${reset}\n"
    fi
    cd $TOP_LEVEL
  fi

  ############################################################################
  ### CSS FILES
  ############################################################################
  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.css$ ]]; then
    # Work out the root name of the CSS so we can ensure that the PostCSS
    # version has been compiled correctly.
    if [[ $FILE =~ \.pcss\.css$ ]]; then
      BASENAME=${FILE%.pcss.css}
      COMPILE_CHECK=1
    else
      BASENAME=${FILE%.css}
      # We only need to compile check if the .pcss.css file is not also
      # changing. This is because the compile check will occur for the
      # .pcss.css file. This might occur if the compiled stylesheets have
      # changed.
      contains_element "$BASENAME.pcss.css" "${FILES[@]}"
      HASPOSTCSS=$?
      if [ "$HASPOSTCSS" -ne "0" ]; then
        COMPILE_CHECK=1
      else
        COMPILE_CHECK=0
      fi
    fi
    # PostCSS
    if [[ "$COMPILE_CHECK" == "1" ]] && [[ -f "$TOP_LEVEL/$BASENAME.pcss.css" ]]; then
      cd "$TOP_LEVEL/core"
      yarn run build:css --check --file "$TOP_LEVEL/$BASENAME.pcss.css"
      CORRECTCSS=$?
      if [ "$CORRECTCSS" -ne "0" ]; then
        # If the CSS does not match the PCSS, set the status to a number other
        # than 0.
        STATUS=1
        printf "\n${red}ERROR: The compiled CSS from"
        printf "\n       ${BASENAME}.pcss.css"
        printf "\n       does not match its CSS file. Recompile the CSS with:"
        printf "\n       yarn run build:css${reset}\n\n"
      fi
      cd $TOP_LEVEL
    fi
  fi
  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.css$ ]] && [[ -f "core/node_modules/.bin/stylelint" ]]; then
    BASENAME=${FILE%.css}
    # We only need to use stylelint on the .pcss.css file. So if this CSS file
    # has a corresponding .pcss don't do stylelint.
    if [[ $FILE =~ \.pcss\.css$ ]] || [[ ! -f "$TOP_LEVEL/$BASENAME.pcss.css" ]]; then
      cd "$TOP_LEVEL/core"
      node_modules/.bin/stylelint --allow-empty-input "$TOP_LEVEL/$FILE"
      if [ "$?" -ne "0" ]; then
        STATUS=1
      else
        printf "STYLELINT: $FILE ${green}passed${reset}\n"
      fi
      cd $TOP_LEVEL
    fi
  fi

  if [[ "$STATUS" == "1" ]]; then
    FINAL_STATUS=1
    # There is no need to print a failure message. The fail will be described
    # already.
  else
    printf "%s ${green}passed${reset}\n" "$FILE"
  fi

  # Print a line to separate each file's checks.
  printf "\n"
  printf -- '-%.0s' {1..100}
  printf "\n"
done

if [[ "$FINAL_STATUS" == "1" ]] && [[ "$DRUPALCI" == "1" ]]; then
  printf "${red}Drupal code quality checks failed.${reset}\n"
  printf "To reproduce this output locally:\n"
  printf "* Apply the change as a patch\n"
  printf "* Run this command locally: sh ./core/scripts/dev/commit-code-check.sh\n"
  printf "OR:\n"
  printf "* From the merge request branch\n"
  printf "* Run this command locally: sh ./core/scripts/dev/commit-code-check.sh --branch %s\n" "$DRUPAL_VERSION"
fi
exit $FINAL_STATUS
