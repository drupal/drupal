1.2.0 / 2014-09-26
==================

BC break:

* Changed the behavior of `getValue` for checkboxes according to the BC break in Mink 1.6

New features:

* Implemented `getOuterHtml`
* Added the support of manipulating forms without submit buttons
* Added support of any request headers instead of supporting only a few hardcoded ones
* Added support of any BrowserKit client using `filterResponse` when using BrowserKit 2.3+
* Added the support of reset buttons
* Implemented `submitForm`
* Implemented `isSelected`

Bug fixes:

* Fixed the support of options without value attribute in `isSelected` and `getValue`
* Added the support of radio buttons in `isChecked`
* Fixed the submission of empty textarea fields
* Refactored the handling of request headers to ensure they are reset when resetting the driver
* Fixed the handling of buttons to submit only for submit buttons rather than all buttons
* Fixed the code to throw exceptions rather than triggering a fatal error for invalid usages of the driver
* Fixed the removal of cookies
* Fixed the submission of form fields with same name and without id
* Fixed `getAttribute` to return `null` for missing attributes rather than an empty string

Testing:

* Updated the testsuite to use the new Mink 1.6 driver testsuite
* Added testing on HHVM
