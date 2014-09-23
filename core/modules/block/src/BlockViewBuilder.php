<?php

/**
 * @file
 * Contains \Drupal\block\BlockViewBuilder.
 */

namespace Drupal\block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Cache\Cache;
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
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
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
    /** @var \Drupal\block\BlockInterface[] $entities */
    $build = array();
    foreach ($entities as  $entity) {
      $entity_id = $entity->id();
      $plugin = $entity->getPlugin();
      $plugin_id = $plugin->getPluginId();
      $base_id = $plugin->getBaseId();
      $derivative_id = $plugin->getDerivativeId();
      $configuration = $plugin->getConfiguration();

      // Create the render array for the block as a whole.
      // @see template_preprocess_block().
      $build[$entity_id] = array(
        '#theme' => 'block',
        '#attributes' => array(),
        // All blocks get a "Configure block" contextual link.
        '#contextual_links' => array(
          'block' => array(
            'route_parameters' => array('block' => $entity->id()),
          ),
        ),
        '#weight' => $entity->get('weight'),
        '#configuration' => $configuration,
        '#plugin_id' => $plugin_id,
        '#base_plugin_id' => $base_id,
        '#derivative_plugin_id' => $derivative_id,
        '#id' => $entity->id(),
        // Add the entity so that it can be used in the #pre_render method.
        '#block' => $entity,
      );
      $build[$entity_id]['#configuration']['label'] = String::checkPlain($configuration['label']);

      // Set cache tags; these always need to be set, whether the block is
      // cacheable or not, so that the page cache is correctly informed.
      $build[$entity_id]['#cache']['tags'] = NestedArray::mergeDeepArray(array(
        $this->getCacheTag(), // Block view builder cache tag.
        $entity->getCacheTag(), // Block entity cache tag.
        $entity->getListCacheTags(), // Block entity list cache tags.
        $plugin->getCacheTags(), // Block plugin cache tags.
      ));

      if ($plugin->isCacheable()) {
        $build[$entity_id]['#pre_render'][] = array($this, 'buildBlock');
        // Generic cache keys, with the block plugin's custom keys appended
        // (usually cache context keys like 'cache_context.user.roles').
        $default_cache_keys = array(
          'entity_view',
          'block',
          $entity->id(),
          $this->languageManager->getCurrentLanguage()->getId(),
          // Blocks are always rendered in a "per theme" cache context.
          'cache_context.theme',
        );
        $max_age = $plugin->getCacheMaxAge();
        $build[$entity_id]['#cache'] += array(
          'keys' => array_merge($default_cache_keys, $plugin->getCacheKeys()),
          'expire' => ($max_age === Cache::PERMANENT) ? Cache::PERMANENT : REQUEST_TIME + $max_age,
        );
      }
      else {
        $build[$entity_id] = $this->buildBlock($build[$entity_id]);
      }

      // Don't run in ::buildBlock() to ensure cache keys can be altered. If an
      // alter hook wants to modify the block contents, it can append another
      // #pre_render hook.
      $this->moduleHandler()->alter(array('block_view', "block_view_$base_id"), $build[$entity_id], $plugin);
    }
    return $build;
  }

  /**
   * #pre_render callback for building a block.
   *
   * Renders the content using the provided block plugin, and then:
   * - if there is no content, aborts rendering, and makes sure the block won't
   *   be rendered.
   * - if there is content, moves the contextual links from the block content to
   *   the block itself.
   */
  public function buildBlock($build) {
    $content = $build['#block']->getPlugin()->build();
    // Remove the block entity from the render array, to ensure that blocks
    // can be rendered without the block config entity.
    unset($build['#block']);
    if (!empty($content)) {
      // Place the $content returned by the block plugin into a 'content' child
      // element, as a way to allow the plugin to have complete control of its
      // properties and rendering (e.g., its own #theme) without conflicting
      // with the properties used above, or alternate ones used by alternate
      // block rendering approaches in contrib (e.g., Panels). However, the use
      // of a child element is an implementation detail of this particular block
      // rendering approach. Semantically, the content returned by the plugin
      // "is the" block, and in particular, #attributes and #contextual_links is
      // information about the *entire* block. Therefore, we must move these
      // properties from $content and merge them into the top-level element.
      foreach (array('#attributes', '#contextual_links') as $property) {
        if (isset($content[$property])) {
          $build[$property] += $content[$property];
          unset($content[$property]);
        }
      }
      $build['content'] = $content;
    }
    else {
      // Abort rendering: render as the empty string and ensure this block is
      // render cached, so we can avoid the work of having to repeatedly
      // determine whether the block is empty. E.g. modifying or adding entities
      // could cause the block to no longer be empty.
      $build = array(
        '#markup' => '',
        '#cache' => $build['#cache'],
      );
    }
    return $build;
   }

}
