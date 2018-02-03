<?php

namespace Drupal\FunctionalJavascriptTests\Tests;

use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;

/**
 * Tests for the JSWebAssert class using webdriver.
 *
 * @group javascript
 */
class JSWebWithWebDriverAssertTest extends JSWebAssertTest {

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

}
