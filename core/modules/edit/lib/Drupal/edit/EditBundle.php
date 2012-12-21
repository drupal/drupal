<?php

/**
 * @file
 * Contains \Drupal\edit\EditBundle.
 */

namespace Drupal\edit;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Edit dependency injection container.
 */
class EditBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the plugin managers for our plugin types with the dependency injection container.
    $container->register('plugin.manager.edit.processed_text_editor', 'Drupal\edit\Plugin\ProcessedTextEditorManager');

    $container->register('access_check.edit.entity_field', 'Drupal\edit\Access\EditEntityFieldAccessCheck')
      ->addTag('access_check');

    $container->register('edit.editor.selector', 'Drupal\edit\EditorSelector')
      ->addArgument(new Reference('plugin.manager.edit.processed_text_editor'));

    $container->register('edit.metadata.generator', 'Drupal\edit\MetadataGenerator')
      ->addArgument(new Reference('access_check.edit.entity_field'))
      ->addArgument(new Reference('edit.editor.selector'));
  }

}
