<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for Package Manager Functional tests.
 */
abstract class PackageManagerTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Allow package_manager to be installed.
    $settings['settings']['testing_package_manager'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

}
