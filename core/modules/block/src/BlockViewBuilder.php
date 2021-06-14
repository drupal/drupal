<?php

namespace Drupal\block;

use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\block\Entity\Block;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a Block view builder.
 */
class BlockViewBuilder extends EntityViewBuilder implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = $this->viewMultiple([$entity], $view_mode, $langcode);
    return reset($build);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\block\BlockInterface[] $entities */
    $build = [];
    foreach ($entities as $entity) {
      $entity_id = $entity->id();
      $plugin = $entity->getPlugin();

      $cache_tags = Cache::mergeTags($this->getCacheTags(), $entity->getCacheTags());
      $cache_tags = Cache::mergeTags($cache_tags, $plugin->getCacheTags());

      // Create the render array for the block as a whole.
      // @see template_preprocess_block().
      $build[$entity_id] = [
        '#cache' => [
          'keys' => ['entity_view', 'block', $entity->id()],
          'contexts' => Cache::mergeContexts(
            $entity->getCacheContexts(),
            $plugin->getCacheContexts()
          ),
          'tags' => $cache_tags,
          'max-age' => $plugin->getCacheMaxAge(),
        ],
        '#weight' => $entity->getWeight(),
      ];

      // Allow altering of cacheability metadata or setting #create_placeholder.
      $this->moduleHandler->alter(['block_build', "block_build_" . $plugin->getBaseId()], $build[$entity_id], $plugin);

      if ($plugin instanceof MainContentBlockPluginInterface || $plugin instanceof TitleBlockPluginInterface) {
        // Immediately build a #pre_render-able block, since this block cannot
        // be built lazily.
        $build[$entity_id] += static::buildPreRenderableBlock($entity, $this->moduleHandler());
      }
      else {
        // Assign a #lazy_builder callback, which will generate a #pre_render-
        // able block lazily (when necessary).
        $build[$entity_id] += [
          '#lazy_builder' => [static::class . '::lazyBuilder', [$entity_id, $view_mode, $langcode]],
        ];
      }
    }

    return $build;
  }

  /**
   * Builds a #pre_render-able block render array.
   *
   * @param \Drupal\block\BlockInterface $entity
   *   A block config entity.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   *
   * @return array
   *   A render array with a #pre_render callback to render the block.
   */
  protected static function buildPreRenderableBlock(BlockInterface $entity, ModuleHandlerInterface $module_handler) {
    $plugin = $entity->getPlugin();
    $plugin_id = $plugin->getPluginId();
    $base_id = $plugin->getBaseId();
    $derivative_id = $plugin->getDerivativeId();
    $configuration = $plugin->getConfiguration();

    // Inject runtime contexts.
    if ($plugin instanceof ContextAwarePluginInterface) {
      $contexts = \Drupal::service('context.repository')->getRuntimeContexts($plugin->getContextMapping());
      \Drupal::service('context.handler')->applyContextMapping($plugin, $contexts);
    }

    // Create the render array for the block as a whole.
    // @see template_preprocess_block().
    $build = [
      '#theme' => 'block',
      '#attributes' => [],
      // All blocks get a "Configure block" contextual link.
      '#contextual_links' => [
        'block' => [
          'route_parameters' => ['block' => $entity->id()],
        ],
      ],
      '#weight' => $entity->getWeight(),
      '#configuration' => $configuration,
      '#plugin_id' => $plugin_id,
      '#base_plugin_id' => $base_id,
      '#derivative_plugin_id' => $derivative_id,
      '#id' => $entity->id(),
      '#pre_render' => [
        static::class . '::preRender',
      ],
      // Add the entity so that it can be used in the #pre_render method.
      '#block' => $entity,
    ];

    // If an alter hook wants to modify the block contents, it can append
    // another #pre_render hook.
    $module_handler->alter(['block_view', "block_view_$base_id"], $build, $plugin);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender', 'lazyBuilder'];
  }

  /**
   * #lazy_builder callback; builds a #pre_render-able block.
   *
   * @param $entity_id
   *   A block config entity ID.
   * @param $view_mode
   *   The view mode the block is being viewed in.
   *
   * @return array
   *   A render array with a #pre_render callback to render the block.
   */
  public static function lazyBuilder($entity_id, $view_mode) {
    return static::buildPreRenderableBlock(Block::load($entity_id), \Drupal::service('module_handler'));
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
  public static function preRender($build) {
    $content = $build['#block']->getPlugin()->build();
    // Remove the block entity from the render array, to ensure that blocks
    // can be rendered without the block config entity.
    unset($build['#block']);
    if ($content !== NULL && !Element::isEmpty($content)) {
      // Place the $content returned by the block plugin into a 'content' child
      // element, as a way to allow the plugin to have complete control of its
      // properties and rendering (for instance, its own #theme) without
      // conflicting with the properties used above, or alternate ones used by
      // alternate block rendering approaches in contrib (for instance, Panels).
      // However, the use of a child element is an implementation detail of this
      // particular block rendering approach. Semantically, the content returned
      // by the plugin "is the" block, and in particular, #attributes and
      // #contextual_links is information about the *entire* block. Therefore,
      // we must move these properties from $content and merge them into the
      // top-level element.
      foreach (['#attributes', '#contextual_links'] as $property) {
        if (isset($content[$property])) {
          $build[$property] += $content[$property];
          unset($content[$property]);
        }
      }
      $build['content'] = $content;
    }
    // Either the block's content is completely empty, or it consists only of
    // cacheability metadata.
    else {
      // Abort rendering: render as the empty string and ensure this block is
      // render cached, so we can avoid the work of having to repeatedly
      // determine whether the block is empty. For instance, modifying or adding
      // entities could cause the block to no longer be empty.
      $build = [
        '#markup' => '',
        '#cache' => $build['#cache'],
      ];
      // If $content is not empty, then it contains cacheability metadata, and
      // we must merge it with the existing cacheability metadata. This allows
      // blocks to be empty, yet still bubble cacheability metadata, to indicate
      // why they are empty.
      if (!empty($content)) {
        CacheableMetadata::createFromRenderArray($build)
          ->merge(CacheableMetadata::createFromRenderArray($content))
          ->applyTo($build);
      }
    }
    return $build;
  }

}
