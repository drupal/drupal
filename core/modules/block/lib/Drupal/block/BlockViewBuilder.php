<?php

/**
 * @file
 * Contains \Drupal\block\BlockViewBuilder.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a Block view builder.
 */
class BlockViewBuilder implements EntityViewBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = $this->viewMultiple(array($entity), $view_mode, $langcode);
    return reset($build);
  }

  /**
   * {@inheritdoc}
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
