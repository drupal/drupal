#!/bin/bash

MODULE=drupal
FILENAME=drupal

#FILENAME=$MODULE-`date +"%Y%m%d"`

cvs co $MODULE
tar -czf $FILENAME.tgz $MODULE/*
rm -Rf $MODULE
