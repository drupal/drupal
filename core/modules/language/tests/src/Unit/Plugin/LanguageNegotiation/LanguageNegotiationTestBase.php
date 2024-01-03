<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Unit\Plugin\LanguageNegotiation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Base class used for testing the various LanguageNegotiation plugins.
 *
 * @group language
 */
abstract class LanguageNegotiationTestBase extends UnitTestCase {

  /**
   * Returns the plugin class to use for creating the language negotiation plugin.
   *
   * @return string
   *   The plugin class name.
   */
  abstract protected function getPluginClass(): string;

  /**
   * Creates a @LanguageNegotiation plugin using the factory ::create method.
   *
   * @return \Drupal\language\LanguageNegotiationMethodInterface
   */
  protected function createLanguageNegotiationPlugin(array $configuration = [], $plugin_definition = NULL) {
    $class = $this->getPluginClass();
    $this->assertTrue(in_array(ContainerFactoryPluginInterface::class, class_implements($class)));
    return $class::create(\Drupal::getContainer(), $configuration, $class::METHOD_ID, $plugin_definition);
  }

}
