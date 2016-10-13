#!/bin/bash

# Rename *.js files in *.es6.js. Only need to be run once.
# Should be removed after *.es6.js files are committed to core.
#
# @internal This file is part of the core javascript build process and is only
# meant to be used in that context.

for js in `find ./{misc,modules,themes} -name '*.js'`;
do
   mv ${js} ${js%???}.es6.js;
done
