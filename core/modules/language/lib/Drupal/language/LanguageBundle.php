<?php

/**
 * @file
 * Definition of Drupal\language\LanguageBundle.
 */

namespace Drupal\language;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Reference;

/**
 * language dependency injection container.
 */
class LanguageBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the language-based path processor.
    $container->register('path_processor_language', 'Drupal\language\HttpKernel\PathProcessorLanguage')
      ->addArgument(new Reference('module_handler'))
      ->addTag('path_processor_inbound', array('priority' => 300));
  }

}
