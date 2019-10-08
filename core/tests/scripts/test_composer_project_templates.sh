#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# @todo: convert to a build test after #2984031 is in.

#/ Usage:       ./drupal_project_templates.sh
#/ Description: Container command to check default composer templates.
#/ Options:
#/   --help: Display this help message
usage() { grep '^#/' "$0" | cut -c4- ; exit 0 ; }
expr "$*" : ".*--help" > /dev/null && usage

info()    { echo "[INFO]    $*" ; }
fatal()   { echo "[FATAL]   $*" ; exit 1 ; }

assertScaffold() {
  if [ -f $1/autoload.php ]
  then
    info "autoload.php file found."
  else
    fatal "No autoload.php file found."
  fi
  if [ -f $1/core/authorize.php ]
  then
    info "authorize.php file found."
  else
    fatal "No authorize.php file found."
  fi
}

info "Starting script"

SOURCE_DIR=$(realpath $(dirname $0))/../../..

info "Installing recommended project composer template"
composer --working-dir="${SOURCE_DIR}/composer/Template/RecommendedProject" config repositories.scaffold path '../../Plugin/Scaffold'
composer --working-dir="${SOURCE_DIR}/composer/Template/RecommendedProject" install --no-suggest --no-progress --no-interaction
info "Recommended project composer template installed successfully."
assertScaffold "${SOURCE_DIR}/composer/Template/RecommendedProject/web"

info "Installing legacy project composer template"
composer --working-dir="${SOURCE_DIR}/composer/Template/LegacyProject" config repositories.scaffold path '../../Plugin/Scaffold'
composer --working-dir="${SOURCE_DIR}/composer/Template/LegacyProject" install --no-suggest --no-progress --no-interaction
info "Legacy project composer template installed successfully."
assertScaffold "${SOURCE_DIR}/composer/Template/LegacyProject"

info "Script complete!"
