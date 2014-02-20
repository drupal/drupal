<?php

/**
 * @file
 * Contains \Drupal\block\BlockViewBuilder.
 */

namespace Drupal\block;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a Block view builder.
 */
class BlockViewBuilder extends EntityViewBuilder {

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
    foreach ($entities as $key => $entity) {
      $entity_id = $entity->id();
      $plugin = $entity->getPlugin();
      $plugin_id = $plugin->getPluginId();
      $base_id = $plugin->getBasePluginId();
      $derivative_id = $plugin->getDerivativeId();

      if ($content = $plugin->build()) {
        $configuration = $plugin->getConfiguration();

        // Create the render array for the block as a whole.
        // @see template_preprocess_block().
        $build[$key] = array(
          '#theme' => 'block',
          '#attributes' => array(),
          '#contextual_links' => array(
            'block' => array(
              'route_parameters' => array('block' => $entity_id),
            ),
          ),
          '#configuration' => $configuration,
          '#plugin_id' => $plugin_id,
          '#base_plugin_id' => $base_id,
          '#derivative_plugin_id' => $derivative_id,
        );
        $build[$key]['#configuration']['label'] = String::checkPlain($configuration['label']);

        // Place the $content returned by the block plugin into a 'content'
        // child element, as a way to allow the plugin to have complete control
        // of its properties and rendering (e.g., its own #theme) without
        // conflicting with the properties used above, or alternate ones used
        // by alternate block rendering approaches in contrib (e.g., Panels).
        // However, the use of a child element is an implementation detail of
        // this particular block rendering approach. Semantically, the content
        // returned by the plugin "is the" block, and in particular,
        // #attributes and #contextual_links is information about the *entire*
        // block. Therefore, we must move these properties from $content and
        // merge them into the top-level element.
        foreach (array('#attributes', '#contextual_links') as $property) {
          if (isset($content[$property])) {
            $build[$key][$property] += $content[$property];
            unset($content[$property]);
          }
        }
        $build[$key]['content'] = $content;
      }
      else {
        $build[$key] = array();
      }

      $this->moduleHandler()->alter(array('block_view', "block_view_$base_id"), $build[$key], $plugin);

      // @todo Remove after fixing http://drupal.org/node/1989568.
      $build[$key]['#block'] = $entity;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) { }

}
