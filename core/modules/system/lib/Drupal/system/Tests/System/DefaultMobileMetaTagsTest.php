<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\DefaultMobileMetaTagsTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Component\Utility\String;
use Drupal\simpletest\WebTestBase;

class DefaultMobileMetaTagsTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Default mobile meta tags',
      'description' => 'Confirm that the default mobile meta tags appear as expected.',
      'group' => 'System'
    );
  }

  function setUp() {
    parent::setUp();
    $this->default_metatags = array(
      'MobileOptimized' => '<meta name="MobileOptimized" content="width" />',
      'HandheldFriendly' => '<meta name="HandheldFriendly" content="true" />',
      'viewport' => '<meta name="viewport" content="width=device-width" />',
    );
  }

  /**
   * Verifies that the default mobile meta tags are added.
   */
  public function testDefaultMetaTagsExist() {
    $this->drupalGet('');
    foreach ($this->default_metatags as $name => $metatag) {
      $this->assertRaw($metatag, String::format('Default Mobile meta tag "@name" displayed properly.', array('@name' => $name)), 'System');
    }
  }

  /**
   * Verifies that the default mobile meta tags can be removed.
   */
  public function testRemovingDefaultMetaTags() {
    \Drupal::moduleHandler()->install(array('system_module_test'));
    $this->drupalGet('');
    foreach ($this->default_metatags as $name => $metatag) {
      $this->assertNoRaw($metatag, String::format('Default Mobile meta tag "@name" removed properly.', array('@name' => $name)), 'System');
    }
  }
}
