<?php

namespace Drupal\Tests\book\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Config\PreExistingConfigException;

/**
 * Test installation of Book module.
 *
 * @group book
 */
class BookInstallTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test Book install with pre-existing content type.
   *
   * Tests that Book module can be installed if content type with machine name
   * 'book' already exists.
   */
  public function testBookInstallWithPreexistingContentType() {
    // Create a 'book' content type.
    $this->drupalCreateContentType(['type' => 'book']);

    // Install the Book module.
    try {
      $this->container->get('module_installer')->install(['book']);
    }
    catch (PreExistingConfigException $e) {
      $this->fail("Expected exception thrown trying to install Book module: " . $e->getMessage());
    }
  }

}
