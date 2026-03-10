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

MEMORY_UNLIMITED=0
CACHED=0
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
      echo "--memory-unlimited        bypass PHP memory limit for PHPStan and PHPCS"
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
    --memory-unlimited)
      MEMORY_UNLIMITED=1
      shift
      ;;
    *)
      break
      ;;
  esac
done

memory_limit=""
phpcs_memory_limit=""

if [[ "$MEMORY_UNLIMITED" == "1" ]]; then
  memory_limit="--memory-limit=-1"
  phpcs_memory_limit="-d memory_limit=-1"
fi

# Set up variables to make colored output simple.
red=$(tput setaf 1 && tput bold)
blue=$(tput setaf 4 && tput bold)
green=$(tput setaf 2)
reset=$(tput sgr0)
GIT="git"

# Gets list of files to check.
if [[ "$BRANCH" != "" ]]; then
  FILES=$($GIT diff --name-only $BRANCH HEAD);
elif [[ "$CACHED" == "0" ]]; then
  # List of all changes in the working directory.
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

TOP_LEVEL=$($GIT rev-parse --show-toplevel)

# This variable will be set to one when the file core/phpcs.xml.dist is changed.
PHPCS_XML_DIST_FILE_CHANGED=0

# This variable will be set to one when the files core/.phpstan-baseline.php or
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

# This variable will be set to one when JavaScript packages files are changed.
# changed:
#  - core/package.json
#  - core/yarn.lock
JAVASCRIPT_PACKAGES_CHANGED=0

# This variable will be set when a Drupal-specific CKEditor 5 plugin has changed
# it is used to make sure the compiled JS is valid.
CKEDITOR5_PLUGINS_CHANGED=0

# This variable will be set to one when either of the core dictionaries or the
# .cspell.json config has changed.
CSPELL_DICTIONARY_FILE_CHANGED=0

# Build up a list of absolute file names.
ABS_FILES=
for FILE in $FILES; do
  if [ -f "$TOP_LEVEL/$FILE" ]; then
    ABS_FILES="$ABS_FILES $TOP_LEVEL/$FILE"
  fi

  if [[ $FILE == "core/phpcs.xml.dist" ]]; then
    PHPCS_XML_DIST_FILE_CHANGED=1;
  fi;

  if [[ $FILE == "core/.phpstan-baseline.php" || $FILE == "core/phpstan.neon.dist" ]]; then
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
    JAVASCRIPT_PACKAGES_CHANGED=1;
  fi;

  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.js$ ]] && [[ $FILE =~ ^core/modules/ckeditor5/js/build || $FILE =~ ^core/modules/ckeditor5/js/ckeditor5_plugins ]]; then
    CKEDITOR5_PLUGINS_CHANGED=1;
  fi;

  if [[ $FILE == "core/misc/cspell/dictionary.txt" || $FILE == "core/misc/cspell/drupal-dictionary.txt" || $FILE == "core/.cspell.json" ]]; then
    CSPELL_DICTIONARY_FILE_CHANGED=1;
  fi

  if [[ $FILE == "core/MAINTAINERS.txt" ]]; then
    MAINTAINERS_TXT_CHANGED=1;
  fi

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
yarn --version
yarn >/dev/null

# Check all files for spelling in one go for better performance.
if [[ $CSPELL_DICTIONARY_FILE_CHANGED == "1" ]] ; then
  printf "\nRunning spellcheck on *all* files.\n"
  yarn run spellcheck:core --no-must-find-files --no-progress
else
  # Check all files for spelling in one go for better performance. We pipe the
  # list files in so we obey the globs set on the spellcheck:core command in
  # core/package.json.
  echo "${ABS_FILES}" | tr ' ' '\n' | yarn run spellcheck:core --no-must-find-files --file-list stdin
fi

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

# Run PHPStan on all files when phpstan files are changed.
# APCu is disabled to ensure that the composer classmap is not corrupted.
if [[ $PHPSTAN_DIST_FILE_CHANGED == "1" ]]; then
  printf "\nRunning PHPStan on *all* files.\n"
  php -d apc.enabled=0 -d apc.enable_cli=0 vendor/bin/phpstan analyze --no-progress --configuration="$TOP_LEVEL/core/phpstan.neon.dist" $memory_limit
else
  # Only run PHPStan on changed files locally.
  printf "\nRunning PHPStan on changed files.\n"
  php -d apc.enabled=0 -d apc.enable_cli=0 vendor/bin/phpstan analyze --no-progress --configuration="$TOP_LEVEL/core/phpstan-partial.neon" $ABS_FILES $memory_limit
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

# Run PHPCS on all files when phpcs files are changed.
if [[ $PHPCS_XML_DIST_FILE_CHANGED == "1" ]]; then
  # Test all files with phpcs rules.
  vendor/bin/phpcs $phpcs_memory_limit -ps --parallel="$( (nproc || sysctl -n hw.logicalcpu || echo 4) 2>/dev/null)" --standard="$TOP_LEVEL/core/phpcs.xml.dist"
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
  yarn run lint:core-js-passing "$TOP_LEVEL/core"
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
  yarn run lint:css
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

# When JavaScript packages change, then rerun all JavaScript style checks.
if [[ "$JAVASCRIPT_PACKAGES_CHANGED" == "1" ]]; then
  cd "$TOP_LEVEL/core"
  yarn run build:css --check
  CORRECTCSS=$?
  if [ "$CORRECTCSS" -ne "0" ]; then
    FINAL_STATUS=1
    printf "\n${red}ERROR: The compiled CSS from the PCSS files"
    printf "\n       does not match the current CSS files. Some added"
    printf "\n       or updated JavaScript package made changes."
    printf "\n       Recompile the CSS with: yarn run build:css${reset}\n\n"
  fi
  cd $TOP_LEVEL
  # Add a separator line to make the output easier to read.
  printf "\n"
  printf -- '-%.0s' {1..100}
  printf "\n"
fi

# Build file type lists for batch checks.
PHP_FILES=""
JS_FILES=""
CSS_FILES=""
for FILE in $FILES; do
  if [[ -f "$TOP_LEVEL/$FILE" ]]; then
    if [[ $FILE =~ \.(inc|install|module|php|profile|test|theme|yml)$ ]]; then
      PHP_FILES="$PHP_FILES $TOP_LEVEL/$FILE"
    fi
    if [[ $FILE =~ \.(yml|js)$ ]]; then
      JS_FILES="$JS_FILES $TOP_LEVEL/$FILE"
    fi
    if [[ $FILE =~ \.css$ ]]; then
      BASENAME=${FILE%.css}
      if [[ $FILE =~ \.pcss\.css$ ]] || [[ ! -f "$TOP_LEVEL/$BASENAME.pcss.css" ]]; then
        CSS_FILES="$CSS_FILES $TOP_LEVEL/$FILE"
      fi
    fi
  fi
done

# Run PHPCS on changed PHP and YAML files.
if [[ "$PHP_FILES" != "" ]] && [[ $PHPCS_XML_DIST_FILE_CHANGED == "0" ]]; then
  vendor/bin/phpcs $phpcs_memory_limit --standard="$TOP_LEVEL/core/phpcs.xml.dist" $PHP_FILES
  if [ "$?" -ne "0" ]; then
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

# Run ESLint on changed YAML and JavaScript files.
if [[ "$JS_FILES" != "" ]] && [[ $ESLINT_CONFIG_PASSING_FILE_CHANGED == "0" ]]; then
  cd "$TOP_LEVEL/core"
  node ./node_modules/eslint/bin/eslint.js --quiet --resolve-plugins-relative-to . --config=.eslintrc.passing.json $JS_FILES
  if [ "$?" -ne "0" ]; then
    FINAL_STATUS=1
    printf "\nESLint: ${red}failed${reset}\n"
  else
    printf "\nESLint: ${green}passed${reset}\n"
  fi
  cd "$TOP_LEVEL"
  # Add a separator line to make the output easier to read.
  printf "\n"
  printf -- '-%.0s' {1..100}
  printf "\n"
fi

# Run Stylelint on changed CSS files.
if [[ "$CSS_FILES" != "" ]] && [[ $STYLELINT_CONFIG_FILE_CHANGED == "0" ]] && [[ -f "core/node_modules/.bin/stylelint" ]]; then
  cd "$TOP_LEVEL/core"
  node_modules/.bin/stylelint --allow-empty-input $CSS_FILES
  if [ "$?" -ne "0" ]; then
    FINAL_STATUS=1
    printf "\nStylelint: ${red}failed${reset}\n"
  else
    printf "\nStylelint: ${green}passed${reset}\n"
  fi
  cd "$TOP_LEVEL"
  # Add a separator line to make the output easier to read.
  printf "\n"
  printf -- '-%.0s' {1..100}
  printf "\n"
fi

for FILE in $FILES; do
  # Ensure the file still exists (i.e. is not being deleted).
  if [ -a $FILE ]; then
    if [ ${FILE: -3} != ".sh" ]; then
      if [ -x $FILE ]; then
        printf "${red}check failed:${reset} file $FILE should not be executable\n"
        FINAL_STATUS=1
      fi
    fi
  fi

  # Don't commit changes to vendor.
  if [[ "$FILE" =~ ^vendor/ ]]; then
    printf "${red}check failed:${reset} file in vendor directory being committed ($FILE)\n"
    FINAL_STATUS=1
  fi

  # Don't commit changes to core/node_modules.
  if [[ "$FILE" =~ ^core/node_modules/ ]]; then
    printf "${red}check failed:${reset} file in core/node_modules directory being committed ($FILE)\n"
    FINAL_STATUS=1
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
        FINAL_STATUS=1
        printf "\n${red}ERROR: The compiled CSS from"
        printf "\n       ${BASENAME}.pcss.css"
        printf "\n       does not match its CSS file. Recompile the CSS with:"
        printf "\n       yarn run build:css${reset}\n\n"
      fi
      cd $TOP_LEVEL
    fi
  fi
done

if [[ "$MAINTAINERS_TXT_CHANGED" == "1" ]]; then
  printf "\n${blue}INFO: MAINTAINERS.TXT changed"
  printf "\n      Make sure follow up changes are made to documentation, Slack channel, email group etc."
  printf "\n      See https://www.drupal.org/about/core/policies/maintainers/add-or-remove-a-subsystem-or-topic-maintainer.${reset}\n\n"

  # Add a separator line to make the output easier to read.
  printf "\n"
  printf -- '-%.0s' {1..100}
  printf "\n"
fi

exit $FINAL_STATUS
