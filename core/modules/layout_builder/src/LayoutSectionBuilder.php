<?php

namespace Drupal\layout_builder;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Builds the UI for layout sections.
 *
 * @internal
 */
class LayoutSectionBuilder {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Constructs a LayoutSectionFormatter object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layoutPluginManager
   *   The layout plugin manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   THe block plugin manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(AccountInterface $account, LayoutPluginManagerInterface $layoutPluginManager, BlockManagerInterface $blockManager, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository) {
    $this->account = $account;
    $this->layoutPluginManager = $layoutPluginManager;
    $this->blockManager = $blockManager;
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
  }

  /**
   * Builds the render array for the layout section.
   *
   * @param \Drupal\Core\Layout\LayoutInterface $layout
   *   The ID of the layout.
   * @param array $section
   *   An array of configuration, keyed first by region and then by block UUID.
   *
   * @return array
   *   The render array for a given section.
   */
  public function buildSectionFromLayout(LayoutInterface $layout, array $section) {
    $cacheability = CacheableMetadata::createFromRenderArray([]);

    $regions = [];
    $weight = 0;
    foreach ($section as $region => $blocks) {
      if (!is_array($blocks)) {
        throw new \InvalidArgumentException(sprintf('The "%s" region in the "%s" layout has invalid configuration', $region, $layout->getPluginId()));
      }

      foreach ($blocks as $uuid => $configuration) {
        if (!is_array($configuration) || !isset($configuration['block'])) {
          throw new \InvalidArgumentException(sprintf('The block with UUID of "%s" has invalid configuration', $uuid));
        }

        if ($block_output = $this->buildBlock($uuid, $configuration['block'], $cacheability)) {
          $block_output['#weight'] = $weight++;
          $regions[$region][$uuid] = $block_output;
        }
      }
    }

    $result = $layout->build($regions);
    $cacheability->applyTo($result);
    return $result;
  }

  /**
   * Builds the render array for the layout section.
   *
   * @param string $layout_id
   *   The ID of the layout.
   * @param array $layout_settings
   *   The configuration for the layout.
   * @param array $section
   *   An array of configuration, keyed first by region and then by block UUID.
   *
   * @return array
   *   The render array for a given section.
   */
  public function buildSection($layout_id, array $layout_settings, array $section) {
    $layout = $this->layoutPluginManager->createInstance($layout_id, $layout_settings);
    return $this->buildSectionFromLayout($layout, $section);
  }

  /**
   * Builds the render array for a given block.
   *
   * @param string $uuid
   *   The UUID of this block instance.
   * @param array $configuration
   *   An array of configuration relevant to the block instance. Must contain
   *   the plugin ID with the key 'id'.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   The cacheability metadata.
   *
   * @return array|null
   *   The render array representing this block, if accessible. NULL otherwise.
   */
  protected function buildBlock($uuid, array $configuration, CacheableMetadata $cacheability) {
    $block = $this->getBlock($uuid, $configuration);

    $access = $block->access($this->account, TRUE);
    $cacheability->addCacheableDependency($access);

    $block_output = NULL;
    if ($access->isAllowed()) {
      $block_output = [
        '#theme' => 'block',
        '#configuration' => $block->getConfiguration(),
        '#plugin_id' => $block->getPluginId(),
        '#base_plugin_id' => $block->getBaseId(),
        '#derivative_plugin_id' => $block->getDerivativeId(),
        'content' => $block->build(),
      ];
      $cacheability->addCacheableDependency($block);
    }
    return $block_output;
  }

  /**
   * Gets a block instance.
   *
   * @param string $uuid
   *   The UUID of this block instance.
   * @param array $configuration
   *   An array of configuration relevant to the block instance. Must contain
   *   the plugin ID with the key 'id'.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   *   The block instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when the configuration parameter does not contain 'id'.
   */
  protected function getBlock($uuid, array $configuration) {
    if (!isset($configuration['id'])) {
      throw new PluginException(sprintf('No plugin ID specified for block with "%s" UUID', $uuid));
    }

    $block = $this->blockManager->createInstance($configuration['id'], $configuration);
    if ($block instanceof ContextAwarePluginInterface) {
      $contexts = $this->contextRepository->getRuntimeContexts(array_values($block->getContextMapping()));
      $this->contextHandler->applyContextMapping($block, $contexts);
    }
    return $block;
  }

}
