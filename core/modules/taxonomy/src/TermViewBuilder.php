<?php

namespace Drupal\taxonomy;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for taxonomy terms.
 */
class TermViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    parent::alterBuild($build, $entity, $display, $view_mode);
    $build['#contextual_links']['taxonomy_term'] = [
      'route_parameters' => ['taxonomy_term' => $entity->id()],
      'metadata' => ['changed' => $entity->getChangedTime()],
    ];
  }

}
