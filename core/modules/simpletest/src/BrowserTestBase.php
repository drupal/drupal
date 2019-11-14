<?php

namespace Drupal\simpletest;

@trigger_error(__NAMESPACE__ . '\BrowserTestBase is deprecated in Drupal 8.1.x, will be removed before Drupal 9.0. Use Drupal\Tests\BrowserTestBase instead.', E_USER_DEPRECATED);

use Drupal\Tests\BrowserTestBase as BaseBrowserTestBase;

/**
 * Provides a test case for functional Drupal tests.
 *
 * Tests extending BrowserTestBase must exist in the
 * Drupal\Tests\yourmodule\Functional namespace and live in the
 * modules/yourmodule/tests/src/Functional directory.
 *
 * @ingroup testing
 *
 * @see \Drupal\simpletest\WebTestBase
 * @see \Drupal\Tests\BrowserTestBase
 *
 * @deprecated in drupal:8.1.0 and is removed from drupal:9.0.0.
 *   Use Drupal\Tests\BrowserTestBase instead.
 */
abstract class BrowserTestBase extends BaseBrowserTestBase {
}
