<?php

/**
 * @file
 * Contains \Drupal\ckeditor\CKEditorBundle.
 */

namespace Drupal\ckeditor;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * CKEditor dependency injection container.
 */
class CKEditorBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('plugin.manager.ckeditor.plugin', 'Drupal\ckeditor\CKEditorPluginManager');
  }

}
