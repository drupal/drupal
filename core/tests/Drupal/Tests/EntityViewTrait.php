<?php

namespace Drupal\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element;

/**
 * Provides helper methods to deal with building entity views in tests.
 */
trait EntityViewTrait {

  /**
   * Builds the renderable view of an entity.
   *
   * Entities postpone the composition of their renderable arrays to #pre_render
   * functions in order to maximize cache efficacy. This means that the full
   * renderable array for an entity is constructed in drupal_render(). Some
   * tests require the complete renderable array for an entity outside of the
   * drupal_render process in order to verify the presence of specific values.
   * This method isolates the steps in the render process that produce an
   * entity's renderable array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to prepare a renderable array for.
   * @param string $view_mode
   *   (optional) The view mode that should be used to build the entity.
   * @param null $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   * @param bool $reset
   *   (optional) Whether to clear the cache for this entity.
   * @return array
   *
   * @see drupal_render()
   */
  protected function buildEntityView(EntityInterface $entity, $view_mode = 'full', $langcode = NULL, $reset = FALSE) {
    $ensure_fully_built = function(&$elements) use (&$ensure_fully_built) {
      // If the default values for this element have not been loaded yet, populate
      // them.
      if (isset($elements['#type']) && empty($elements['#defaults_loaded'])) {
        $elements += \Drupal::service('element_info')->getInfo($elements['#type']);
      }

      // Make any final changes to the element before it is rendered. This means
      // that the $element or the children can be altered or corrected before the
      // element is rendered into the final text.
      if (isset($elements['#pre_render'])) {
        foreach ($elements['#pre_render'] as $callable) {
          $elements = call_user_func($callable, $elements);
        }
      }

      // And recurse.
      $children = Element::children($elements, TRUE);
      foreach ($children as $key) {
        $ensure_fully_built($elements[$key]);
      }
    };

    $render_controller = $this->container->get('entity.manager')->getViewBuilder($entity->getEntityTypeId());
    if ($reset) {
      $render_controller->resetCache([$entity->id()]);
    }
    $build = $render_controller->view($entity, $view_mode, $langcode);
    $ensure_fully_built($build);

    return $build;
  }

}
