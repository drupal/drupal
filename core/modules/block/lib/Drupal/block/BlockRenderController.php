<?php

/**
 * @file
 * Contains \Drupal\block\BlockRenderController.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityRenderControllerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a Block render controller.
 */
class BlockRenderController implements EntityRenderControllerInterface {

  /**
   * Implements \Drupal\Core\Entity\EntityRenderControllerInterface::buildContent().
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    return array();
  }

  /**
   * Provides entity-specific defaults to the build process.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the defaults should be provided.
   * @param string $view_mode
   *   The view mode that should be used.
   * @param string $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   *
   * @return array
   *   An array of defaults to add into the entity render array.
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    // @todo \Drupal\block\Tests\BlockTest::testCustomBlock() assuemes that a
    //   block can be rendered without any of its wrappers. To do so, it uses a
    //   custom view mode, and we choose to only add the wrappers on the default
    //   view mode, 'block'.
    if ($view_mode != 'block') {
      return array();
    }

    return array(
      '#block' => $entity,
      '#weight' => $entity->get('weight'),
      '#theme_wrappers' => array('block'),
      '#block_config' => array(
        'id' => $entity->get('plugin'),
        'region' => $entity->get('region'),
        'module' => $entity->get('module'),
        'subject' => $entity->label(),
      ),
    );
  }

  /**
   * Implements Drupal\Core\Entity\EntityRenderControllerInterface::view().
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = $this->viewMultiple(array($entity), $view_mode, $langcode);
    return reset($build);
  }

  /**
   * Implements Drupal\Core\Entity\EntityRenderControllerInterface::viewMultiple().
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    $build = array();
    foreach ($entities as $entity_id => $entity) {
      $build[$entity_id] = $entity->getPlugin()->blockBuild();
      // Allow blocks to be empty, do not add in the defaults.
      if (!empty($build[$entity_id])) {
        $build[$entity_id] = $this->getBuildDefaults($entity, $view_mode, $langcode) + $build[$entity_id];
      }

      // All blocks, even when empty, should be available for altering.
      $id = str_replace(':', '__', $entity->get('plugin'));
      list(, $name) = $entity->id();
      drupal_alter(array('block_view', "block_view_$id", "block_view_$name"), $build[$entity_id], $entity);

    }
    return $build;
  }

}
