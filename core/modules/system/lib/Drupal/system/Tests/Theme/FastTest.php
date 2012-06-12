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
  public static function getInfo() {
    return array(
      'name' => 'Theme fast initialization',
      'description' => 'Test that autocompletion does not load the registry.',
      'group' => 'Theme'
    );
  }

  function setUp() {
    parent::setUp('theme_test');
    $this->account = $this->drupalCreateUser(array('access user profiles'));
  }

  /**
   * Tests access to user autocompletion and verify the correct results.
   */
  function testUserAutocomplete() {
    $this->drupalLogin($this->account);
    $this->drupalGet('user/autocomplete/' . $this->account->name);
    $this->assertText('registry not initialized', t('The registry was not initialized'));
  }
}
