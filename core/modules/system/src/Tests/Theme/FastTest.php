<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\FastTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests autocompletion not loading registry.
 */
class FastTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Theme fast initialization',
      'description' => 'Test that autocompletion does not load the registry.',
      'group' => 'Theme'
    );
  }

  function setUp() {
    parent::setUp();
    $this->account = $this->drupalCreateUser(array('access user profiles'));
  }

  /**
   * Tests access to user autocompletion and verify the correct results.
   */
  function testUserAutocomplete() {
    $this->drupalLogin($this->account);
    $this->drupalGet('user/autocomplete', array('query' => array('q' => $this->account->getUsername())));
    $this->assertRaw($this->account->getUsername());
    $this->assertNoText('registry initialized', 'The registry was not initialized');
  }
}
