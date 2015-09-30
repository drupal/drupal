1.6.1 / 2015-02-04
==================

Bug fixes:

* Added a check for empty path in `WebAssert::cleanUrl()`

Driver testsuite:

* Added an extra test to ensure the right behavior for traversal

Misc:

* Changed the description in the composer.json
* Switched the repository structure to use PSR-4
* Updated URLs for the move to the new Github organization

1.6.0 / 2014-09-26
==================

  * [BC break] Changed the named selector to prefer exact matches over partial matches
  * [BC break] Changed `NodeElement::getValue` for checkboxes to return the value rather than the checked state (use `isChecked` for that)
  * Fixed the XPath prefixing when searching inside an existing element
  * Refactored the driver testsuite entirely and expand it to cover drivers entirely (covering many more cases for consistency)
  * Changed `NodeElement::setValue` to support any fields rather than only input elements
  * Removed the wrapping of any driver-level exception in a MinkException on invalid usage as it was making the code too complex
  * Fixed the matching of the input type in the named selector to be case insensitive according to the HTML spec
  * Introduced `Behat\Mink\Selector\Xpath\Escaper` to allow reusing the XPath escaping
  * Deprecated `Element::getSession`. Code needing the session should get it from outside rather than the element
  * Changed ElementNotFoundException to extend from ExpectationException
  * Added `Element::getOuterHtml` to get the HTML code of the element including itself
  * Fixed the name selectors to match on the `placeholder` only for textual inputs
  * Enforced consistent behavior for drivers on 4xx and 5xx response to return the response rather than throwing an exception
  * Added `Element::waitFor` to allow retrying some code until it succeeds or the timeout is reached
  * Added `Element::isValid` to check whether an element still exists in the page
  * Made `Session::executeScript` compatible across drivers by ensuring they all support the same syntaxes for the JS expression
  * Made `Session::evaluateScript` compatible across drivers by ensuring they all support the same syntaxes for the JS expression
  * Removed `hasClass` from `DocumentElement` (instead of triggering a fatal error)
  * Added testing on HHVM to ensure consistency
  * Fixed `NodeElement::getTagName` to ensure that the tag name is lowercase for all drivers
  * Fixed `Element::hasAttribute` to ensure it supports attributes with an empty value
  * Fixed the `field` selector to avoid matching inputs with the type `submit` or `reset`
  * Changed the button XPath selection to accept `reset` buttons as well
  * Changed `Session::wait` to return the condition value rather than nothing
  * Added `Session::getWindowName` and `Session::getWindowNames` to get the name of the current and of all windows
  * Added `Session::maximizeWindow` to maximize the window
  * Added `NodeElement::isSelected` to check whether an `<option>` is selected
  * Added `NodeElement::submitForm` to allow submitting a form without using a button
  * Added assertions about the value of an attribute
  * Added the anchor in the assertion on the URL in `WebAssert`

1.5.0 / 2013-04-14
==================

  * Add `CoreDriver` to simplify future drivers improvements
  * Add `Mink::isSessionStarted()` method
  * Fix multibite string `preg_replace` bugs
  * Fix handling of whitespaces in `WebAssert::pageText...()` methods

1.4.3 / 2013-03-02
==================

  * Bump dependencies constraints

1.4.2 / 2013-02-13
==================

  * Fix wrong test case to ensure that core drivers work as expected

1.4.1 / 2013-02-10
==================

  * Update dependencies
  * Add ElementException to element actions
  * Rel attribute support for named selectors
  * Add hasClass() helper to traversable elements
  * Add getScreenshot() method to session
  * Name attr support in named selector for button
  * Fix for bunch of bugs

1.4.0 / 2012-05-40
==================

  * New `Session::selectWindow()` and `Session::selectIFrame()` methods
  * New built-in `WebAssert` class
  * Fixed DocBlocks (autocompletion in any IDE now should just work)
  * Moved Behat-related code into `Behat\MinkExtension`
  * Removed PHPUnit test case class
  * Updated composer dependencies to not require custom repository anymore
  * All drivers moved into separate packages

1.3.3 / 2012-03-23
==================

  * Prevent exceptions in `__toString()`
  * Added couple of useful step definitions for Behat
  * Fixed issues #168, #211, #212, #208
  * Lot of small bug fixes and improvements
  * Fixed dependencies and composer installation routine

1.3.2 / 2011-12-21
==================

  * Fixed webdriver registration in MinkContext

1.3.1 / 2011-12-21
==================

  * Fixed Composer package

1.3.0 / 2011-12-21
==================

  * Brand new Selenium2Driver (webdriver session)
  * Multiselect bugfixes
  * ZombieDriver back in the business
  * Composer now manages dependencies
  * Some MinkContext steps got fixes
  * Lots of bug fixes and cleanup

1.2.0 / 2011-11-04
==================

  * Brand new SeleniumDriver (thanks @alexandresalome)
  * Multiselect support (multiple options selection), including new Behat steps
  * Ability to select option by it's text (in addition to value)
  * ZombieDriver updates
  * Use SuiteHooks to populate parameters (no need to call parent __construct anymore)
  * Updated Goutte and all vendors
  * Lot of bugfixes and new tests

1.1.1 / 2011-08-12
==================

  * Fixed Zombie.js server termination on Linux
  * Fixed base_url usage for external URLs

1.1.0 / 2011-08-08
==================

  * Added Zombie.js driver (thanks @b00giZm)
  * Added pt translation (thanks Daniel Gomes)
  * Refactored MinkContext and MinkTestCase

1.0.3 / 2011-08-02
==================

  * File uploads for empty fields fixed (GoutteDriver)
  * Lazy sessions restart
  * `show_tmp_dir` option in MinkContext
  * Updated to stable Symfony2 components
  * SahiClient connection limit bumped to 60 seconds
  * Dutch language support

1.0.2 / 2011-07-22
==================

  * ElementHtmlException fixed (thanks @Stof)

1.0.1 / 2011-07-21
==================

  * Fixed buggy assertions in MinkContext

1.0.0 / 2011-07-20
==================

  * Added missing tests for almost everything
  * Hude speedup for SahiDriver
  * Support for Behat 2.0 contexts
  * Bundled PHPUnit TestCase
  * Deep element traversing
  * Correct behavior of getText() method
  * New getHtml() method
  * Basic HTTP auth support
  * Soft and hard session resetting
  * Cookies management
  * Browser history interactions (reload(), back(), forward())
  * Weaverryan'd exception messages
  * Huge amount of bugfixes and small additions

0.3.2 / 2011-06-20
==================

  * Fixed file uploads in Goutte driver
  * Fixed setting of long texts into fields
  * Added getPlainText() (returns text without tags and whitespaces) method to the element's API
  * Start_url is now optional parameter
  * Default session (if needed) name now need to be always specified by hands with setDefaultSessionName()
  * default_driver => default_session
  * Updated Symfony Components

0.3.1 / 2011-05-17
==================

  * Small SahiClient update (it generates SID now if no provided)
  * setActiveSessionName => setDefaultSessionName method rename

0.3.0 / 2011-05-17
==================

  * Rewritten from scratch Mink drivers handler. Now it's sessions handler. And Mink now
    sessions-centric tool. See examples in readme. Much cleaner API now.

0.2.4 / 2011-05-12
==================

  * Fixed wrong url locator function
  * Fixed wrong regex in `should see` step
  * Fixed delimiters use in `should see` step
  * Added url-match step for checking urls against regex

0.2.3 / 2011-05-01
==================

  * Updated SahiClient with new version, which is faster and cleaner with it's exceptions

0.2.2 / 2011-05-01
==================

  * Ability to use already started browser as SahiDriver aim
  * Added japanese translation for bundled steps (thanks @hidenorigoto)
  * 10 seconds limit for browser connection in SahiDriver

0.2.1 / 2011-04-21
==================

  * Fixed some bundled step definitions

0.2.0 / 2011-04-21
==================

  * Additional step definitions
  * Support for extended drivers configuration through behat.yml environment parameters
  * Lots of new named selectors
  * Bug fixes
  * Small improvements

0.1.2 / 2011-04-08
==================

  * Fixed Sahi url escaping

0.1.1 / 2011-04-06
==================

  * Fixed should/should_not steps
  * Added spanish translation
  * Fixed forms to use <base> element
  * Fixed small UnsupportedByDriverException issue

0.1.0 / 2011-04-04
==================

  * Initial release
