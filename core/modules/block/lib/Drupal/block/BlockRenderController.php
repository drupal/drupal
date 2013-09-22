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
      $plugin = $entity->getPlugin();
      $plugin_id = $plugin->getPluginId();

      if ($content = $plugin->build()) {
        $configuration = $plugin->getConfiguration();
        $build[$entity_id] = array(
          '#theme' => 'block',
          'content' => $content,
          '#configuration' => $configuration,
          '#plugin_id' => $plugin_id,
        );
        $build[$entity_id]['#configuration']['label'] = check_plain($configuration['label']);
      }
      else {
        $build[$entity_id] = array();
      }

      list($base_id) = explode(':', $plugin_id);
      drupal_alter(array('block_view', "block_view_$base_id"), $build[$entity_id], $plugin);

      // @todo Remove after fixing http://drupal.org/node/1989568.
      $build[$entity_id]['#block'] = $entity;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) { }

}
