<?php

namespace Drupal\layout_builder;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Builds the UI for layout sections.
 *
 * @internal
 *
 * @todo Remove in https://www.drupal.org/project/drupal/issues/2928450.
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
   * @param \Drupal\layout_builder\SectionComponent[] $components
   *   An array of components.
   *
   * @return array
   *   The render array for a given section.
   */
  public function buildSectionFromLayout(LayoutInterface $layout, array $components) {
    $regions = [];
    foreach ($components as $component) {
      if ($output = $component->toRenderArray()) {
        $regions[$component->getRegion()][$component->getUuid()] = $output;
      }
    }

    return $layout->build($regions);
  }

  /**
   * Builds the render array for the layout section.
   *
   * @param string $layout_id
   *   The ID of the layout.
   * @param array $layout_settings
   *   The configuration for the layout.
   * @param \Drupal\layout_builder\SectionComponent[] $components
   *   An array of components.
   *
   * @return array
   *   The render array for a given section.
   */
  public function buildSection($layout_id, array $layout_settings, array $components) {
    $layout = $this->layoutPluginManager->createInstance($layout_id, $layout_settings);
    return $this->buildSectionFromLayout($layout, $components);
  }

}
