// $Id: README.txt,v 1.10.2.4 2011/02/07 16:56:27 hass Exp $

Module: Google Analytics
Author: Alexander Hass <http://drupal.org/user/85918>


Description
===========
Adds the Google Analytics tracking system to your website.

Requirements
============

* Google Analytics user account


Installation
============
* Copy the 'googleanalytics' module directory in to your Drupal
sites/all/modules directory as usual.


Usage
=====
In the settings page enter your Google Analytics account number.

All pages will now have the required JavaScript added to the
HTML footer can confirm this by viewing the page source from
your browser.

New approach to page tracking in 5.x-1.5 and 6.x-1.1
====================================================
With 5.x-1.5 and 6.x-1.1 there are new settings on the settings page at
admin/config/system/googleanalytics. The "Page specific tracking" area now
comes with an interface that copies Drupal's block visibility settings.

The default is set to "Add to every page except the listed pages". By
default the following pages are listed for exclusion:

admin
admin/*
user/*/*
node/add*
node/*/*

These defaults are changeable by the website administrator or any other
user with 'administer google analytics' permission.

Like the blocks visibility settings in Drupal core, there is now a
choice for "Add if the following PHP code returns TRUE." Sample PHP snippets
that can be used in this textarea can be found on the handbook page
"Overview-approach to block visibility" at http://drupal.org/node/64135.

Custom variables
=================
One example for custom variables tracking is the "User roles" tracking. Enter
the below configuration data into the custom variables settings form under
admin/config/system/googleanalytics.

Slot: 1
Name: User roles
Value: [current-user:role-names]
Scope: Visitor

More details about Custom variables can be found in the Google API documentation at
http://code.google.com/intl/en/apis/analytics/docs/tracking/gaTrackingCustomVariables.html

Advanced Settings
=================
You can include additional JavaScript snippets in the custom javascript
code textarea. These can be found on the official Google Analytics pages
and a few examples at http://drupal.org/node/248699. Support is not
provided for any customisations you include.

To speed up page loading you may also cache the Analytics ga.js
file locally.
