<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermRenderController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;

/**
 * Render controller for taxonomy terms.
 */
class TermRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    parent::buildContent($entities, $view_mode, $langcode);

    foreach ($entities as $entity) {
      // Add the description if enabled.
      $entity_view_mode = $entity->content['#view_mode'];
      $display = field_extra_fields_get_display($entity, $entity_view_mode);
      if (!empty($entity->description) && !empty($display['description'])) {
        $entity->content['description'] = array(
          '#markup' => check_markup($entity->description, $entity->format, '', TRUE),
          '#weight' => $display['description']['weight'],
          '#prefix' => '<div class="taxonomy-term-description">',
          '#suffix' => '</div>',
        );
      }
    }
  }

  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $return = parent::getBuildDefaults($entity, $view_mode, $langcode);

    // TODO: rename "term" to "taxonomy_term" in theme_taxonomy_term().
    $return['#term'] = $return["#{$this->entityType}"];
    unset($return["#{$this->entityType}"]);

    return $return;
  }

  protected function alterBuild(array &$build, EntityInterface $entity, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $entity, $view_mode, $langcode);
    $build['#attached']['css'][] = drupal_get_path('module', 'taxonomy') . '/taxonomy.css';
  }
}
