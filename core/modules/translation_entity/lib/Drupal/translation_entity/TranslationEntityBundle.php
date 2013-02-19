<?php

/**
 * @file
 * Contains \Drupal\translation_entity\TranslationEntityBundle.
 */

namespace Drupal\translation_entity;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers locale module's services to the container.
 */
class TranslationEntityBundle extends Bundle {

  /**
   * Implements \Symfony\Component\HttpKernel\Bundle\BundleInterface::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('translation_entity.synchronizer', 'Drupal\translation_entity\FieldTranslationSynchronizer');
  }

}
