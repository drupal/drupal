<?php

declare(strict_types=1);

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a view builder that overrides ::view() and ::viewMultiple().
 */
class EntityTestViewBuilderOverriddenView extends EntityTestViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = [];
    $build[$entity->id()]['#plain_text'] = $entity->label();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = [];
    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

}
