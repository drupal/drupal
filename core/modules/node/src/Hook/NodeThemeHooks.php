<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the node module.
 */
class NodeThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for node field templates.
   */
  #[Hook('preprocess_field__node')]
  public function preprocessFieldNode(&$variables): void {
    // Set a variable 'is_inline' in cases where inline markup is required,
    // without any block elements such as <div>.
    if ($variables['element']['#is_page_title'] ?? FALSE) {
      // Page title is always inline because it will be displayed inside <h1>.
      $variables['is_inline'] = TRUE;
    }
    elseif (in_array($variables['field_name'], ['created', 'uid', 'title'], TRUE)) {
      // Display created, uid and title fields inline because they will be
      // displayed inline by node.html.twig. Skip this if the field
      // display is configurable and skipping has been enabled.
      // @todo Delete as part of https://www.drupal.org/node/3015623

      /** @var \Drupal\node\NodeInterface $node */
      $node = $variables['element']['#object'];
      $skip_custom_preprocessing = $node->getEntityType()->get('enable_base_field_custom_preprocess_skipping');
      $variables['is_inline'] = !$skip_custom_preprocessing || !$node->getFieldDefinition($variables['field_name'])->isDisplayConfigurable('view');
    }
  }

}
