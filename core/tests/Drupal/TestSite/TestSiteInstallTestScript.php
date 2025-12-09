<?php

declare(strict_types=1);

namespace Drupal\TestSite;

/**
 * Setup file used by TestSiteApplicationTest.
 *
 * @see \Drupal\KernelTests\Scripts\TestSiteApplicationTest
 */
class TestSiteInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    \Drupal::service('module_installer')->install(['test_page_test']);
  }

}
