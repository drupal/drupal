<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Confirm that the default mobile meta tags appear as expected.
 *
 * @group system
 */
class DefaultMobileMetaTagsTest extends BrowserTestBase {

  /**
   * Array of default meta tags to insert into the page.
   *
   * @var array
   */
  protected $defaultMetaTags;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    $this->defaultMetaTags = [
      'viewport' => '<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
    ];
  }

  /**
   * Verifies that the default mobile meta tags are added.
   */
  public function testDefaultMetaTagsExist() {
    $this->drupalGet('');
    foreach ($this->defaultMetaTags as $name => $metatag) {
      $this->assertRaw($metatag);
    }
  }

  /**
   * Verifies that the default mobile meta tags can be removed.
   */
  public function testRemovingDefaultMetaTags() {
    \Drupal::service('module_installer')->install(['system_module_test']);
    $this->drupalGet('');
    foreach ($this->defaultMetaTags as $name => $metatag) {
      $this->assertNoRaw($metatag);
    }
  }

}
