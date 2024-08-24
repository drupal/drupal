<?php

declare(strict_types=1);

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Defines an entity view builder for a test entity.
 *
 * @see \Drupal\entity_test\Entity\EntityTestRender
 */
class EntityTestViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $build[$id]['label'] = [
        '#weight' => -100,
        '#plain_text' => $entity->label(),
      ];
      $build[$id]['separator'] = [
        '#weight' => -150,
        '#markup' => ' | ',
      ];
      $build[$id]['view_mode'] = [
        '#weight' => -200,
        '#plain_text' => $view_mode,
      ];
    }
  }

}
