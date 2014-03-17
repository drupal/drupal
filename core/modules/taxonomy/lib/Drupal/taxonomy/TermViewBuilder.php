<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermViewBuilder.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for taxonomy terms.
 */
class TermViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $entity) {
      // Add the description if enabled.
      // @todo Remove this when base fields are able to use formatters.
      // https://drupal.org/node/2144919
      $display = $displays[$entity->bundle()];
      if ($entity->getDescription() && $display->getComponent('description')) {
        $entity->content['description'] = array(
          '#markup' => $entity->description->processed,
          '#prefix' => '<div class="taxonomy-term-description">',
          '#suffix' => '</div>',
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $entity, $display, $view_mode, $langcode);
    $build['#attached']['css'][] = drupal_get_path('module', 'taxonomy') . '/css/taxonomy.module.css';
    $build['#contextual_links']['taxonomy_term'] = array(
      'route_parameters' => array('taxonomy_term' => $entity->id()),
      'metadata' => array('changed' => $entity->getChangedTime()),
    );
  }

}
