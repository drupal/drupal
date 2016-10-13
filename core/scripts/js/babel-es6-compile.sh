#!/bin/bash

# Compile *.es6.js files to ES5.
#
# @internal This file is part of the core javascript build process and is only
# meant to be used in that context.

for js in `find ./{misc,modules,themes} -name '*.es6.js'`;
do
  ./node_modules/.bin/babel ${js} --out-file ${js%???????}.js --source-maps --no-comments
done
