<?php

/**
 * @file
 * Contains \Drupal\config_test\SchemaListenerController.
 */

namespace Drupal\config_test;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for testing \Drupal\Core\Config\Testing\ConfigSchemaChecker.
 */
class SchemaListenerController extends ControllerBase {

  /**
   * Constructs the SchemaListenerController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Tests the WebTestBase tests can use strict schema checking.
   */
  public function test() {
    try {
      $this->configFactory->getEditable('config_schema_test.schemaless')->set('foo', 'bar')->save();
    }
    catch (SchemaIncompleteException $e) {
      return [
        '#markup' => $e->getMessage(),
      ];
    }
  }

}
