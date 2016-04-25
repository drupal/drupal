<?php

namespace Drupal\Core\Test;

use Drupal\Core\DrupalKernel;

/**
 * Kernel to mock requests to test simpletest.
 */
class TestKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  public function __construct($environment, $class_loader, $allow_dumping = TRUE) {
    // Include our bootstrap file.
    require_once __DIR__ . '/../../../../includes/bootstrap.inc';

    // Exit if we should be in a test environment but aren't.
    if (!drupal_valid_test_ua()) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
      exit;
    }

    parent::__construct($environment, $class_loader, $allow_dumping);
  }

}
