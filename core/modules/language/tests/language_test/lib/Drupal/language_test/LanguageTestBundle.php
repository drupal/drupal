<?php

/**
 * @file
 * Contains \Drupal\language_test\LanguageTestBundle.
 */

namespace Drupal\language_test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Defines the LanguageTest bundle.
 */
class LanguageTestBundle extends Bundle {

  /**
   * Overrides \Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('language_manager');
    $definition->setClass('Drupal\language_test\LanguageTestManager');
  }

}

