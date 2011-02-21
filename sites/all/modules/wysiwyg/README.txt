/* $Id: README.txt,v 1.8 2009/09/26 05:37:56 sun Exp $ */

-- SUMMARY --

Wysiwyg API allows to users of your site to use WYSIWYG/rich-text, and other
client-side editors for editing contents.  This module depends on third-party
editor libraries, most often based on JavaScript.

For a full description visit the project page:
  http://drupal.org/project/wysiwyg
Bug reports, feature suggestions and latest developments:
  http://drupal.org/project/issues/wysiwyg


-- REQUIREMENTS --

* None.


-- INSTALLATION --

* Install as usual, see http://drupal.org/node/70151 for further information.

* Go to Administer > Configuration and modules > Content authoring > Wysiwyg,
  and follow the displayed installation instructions to download and install one
  of the supported editors.


-- CONFIGURATION --

* Go to Administer > Configuration and modules > Content authoring > Text
  formats and

  - either configure the Full HTML format, assign it to trusted roles, and
    disable "HTML filter", "Line break converter", and (optionally) "URL filter".

  - or add a new text format, assign it to trusted roles, and ensure that above
    mentioned input filters are disabled.

* Setup editor profiles in Administer > Configuration and modules > Content
  authoring > Wysiwyg.


-- CONTACT --

Current maintainers:
* Daniel F. Kudwien (sun) - http://drupal.org/user/54136
* Henrik Danielsson (TwoD) - http://drupal.org/user/244227

This project has been sponsored by:
* UNLEASHED MIND
  Specialized in consulting and planning of Drupal powered sites, UNLEASHED
  MIND offers installation, development, theming, customization, and hosting
  to get you started. Visit http://www.unleashedmind.com for more information.

