<?php

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for aggregator feed items.
 */
class ItemViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      // By default, the description field is exposed as a pseudo-field
      // rendered in this function. However it can optionally be rendered
      // directly using a field formatter. Skip rendering here if a field
      // formatter type is set.
      $component = $display->getComponent('description');
      if ($component && !isset($component['type'])) {
        $build[$id]['description'] = [
          '#markup' => $entity->getDescription(),
          '#allowed_tags' => _aggregator_allowed_tags(),
          '#prefix' => '<div class="item-description">',
          '#suffix' => '</div>',
        ];
      }
    }
  }

}
