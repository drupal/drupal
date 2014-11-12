<?php

/**
 * @file
 * Contains \Drupal\config_test\SchemaListenerController.
 */

namespace Drupal\config_test;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for testing \Drupal\Core\Config\Testing\ConfigSchemaChecker.
 */
class SchemaListenerController extends ControllerBase {

  /**
   * Tests the WebTestBase tests can use strict schema checking.
   */
  public function test() {
    try {
      $this->config('config_schema_test.noschema')->set('foo', 'bar')->save();
    }
    catch (SchemaIncompleteException $e) {
      return [
        '#markup' => $e->getMessage(),
      ];
    }
  }

}
