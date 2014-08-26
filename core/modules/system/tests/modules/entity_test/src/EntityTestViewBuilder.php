<?php

/**
 * @file
 * Contains \Drupal\entity_test\EntityTestViewBuilder.
 */

namespace Drupal\entity_test;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityInterface;
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
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $build = parent::getBuildDefaults($entity, $view_mode, $langcode);
    unset($build['#theme']);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildComponents($build, $entities, $displays, $view_mode, $langcode);

    foreach ($entities as $id => $entity) {
      $build[$id]['label'] = array(
        '#weight' => -100,
        '#markup' => String::checkPlain($entity->label()),
      );
      $build[$id]['separator'] = array(
        '#weight' => -150,
        '#markup' => ' | ',
      );
      $build[$id]['view_mode'] = array(
        '#weight' => -200,
        '#markup' => String::checkPlain($view_mode),
      );
    }
  }

}
