<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\DefaultMobileMetaTagsTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Confirm that the default mobile meta tags appear as expected.
 *
 * @group system
 */
class DefaultMobileMetaTagsTest extends WebTestBase {
  /**
   * Array of default meta tags to insert into the page.
   *
   * @var array
   */
  protected $defaultMetaTags;

  protected function setUp() {
    parent::setUp();
    $this->defaultMetaTags = array(
      'viewport' => '<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
    );
  }

  /**
   * Verifies that the default mobile meta tags are added.
   */
  public function testDefaultMetaTagsExist() {
    $this->drupalGet('');
    foreach ($this->defaultMetaTags as $name => $metatag) {
      $this->assertRaw($metatag, SafeMarkup::format('Default Mobile meta tag "@name" displayed properly.', array('@name' => $name)), 'System');
    }
  }

  /**
   * Verifies that the default mobile meta tags can be removed.
   */
  public function testRemovingDefaultMetaTags() {
    \Drupal::service('module_installer')->install(array('system_module_test'));
    $this->drupalGet('');
    foreach ($this->defaultMetaTags as $name => $metatag) {
      $this->assertNoRaw($metatag, SafeMarkup::format('Default Mobile meta tag "@name" removed properly.', array('@name' => $name)), 'System');
    }
  }
}
