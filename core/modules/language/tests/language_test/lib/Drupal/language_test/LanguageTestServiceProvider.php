<?php

/**
 * @file
 * Contains \Drupal\language_test\LanguageTestServiceProvider.
 */

namespace Drupal\language_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Defines the LanguageTest service provider.
 */
class LanguageTestServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {

  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('language_manager');
    $definition->setClass('Drupal\language_test\LanguageTestManager');
  }

}

