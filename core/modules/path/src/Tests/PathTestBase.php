<?php

namespace Drupal\path\Tests;

@trigger_error(__NAMESPACE__ . '\PathTestBase is deprecated for removal before Drupal 9.0.0. Use Drupal\Tests\path\Functional\PathTestBase instead. See https://www.drupal.org/node/2999939', E_USER_DEPRECATED);

use Drupal\simpletest\WebTestBase;

/**
 * Provides a base class for testing the Path module.
 *
 * @deprecated in drupal:8.?.? and is removed from drupal:9.0.0.
 *   Use \Drupal\Tests\path\Functional\PathTestBase instead.
 *
 * @see https://www.drupal.org/node/2999939
 */
abstract class PathTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'path'];

  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
  }

}
