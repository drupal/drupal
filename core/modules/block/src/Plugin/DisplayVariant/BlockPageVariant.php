<?php

/**
 * @file
 * Contains \Drupal\block\Plugin\DisplayVariant\BlockPageVariant.
 */

namespace Drupal\block\Plugin\DisplayVariant;

use Drupal\block\BlockRepositoryInterface;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Display\VariantBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a page display variant that decorates the main content with blocks.
 *
 * @PageDisplayVariant(
 *   id = "block_page",
 *   admin_label = @Translation("Page with blocks")
 * )
 */
class BlockPageVariant extends VariantBase implements PageVariantInterface, ContainerFactoryPluginInterface {

  /**
   * The block repository.
   *
   * @var \Drupal\block\BlockRepositoryInterface
   */
  protected $blockRepository;

  /**
   * The block view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $blockViewBuilder;

  /**
   * The current theme.
   *
   * @var string
   */
  protected $theme;

  /**
   * The render array representing the main page content.
   *
   * @var array
   */
  protected $mainContent = [];

  /**
   * Constructs a new BlockPageVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\block\BlockRepositoryInterface $block_repository
   *   The block repository.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $block_view_builder
   *   The block view builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockRepositoryInterface $block_repository, EntityViewBuilderInterface $block_view_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockRepository = $block_repository;
    $this->blockViewBuilder = $block_view_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('block.repository'),
      $container->get('entity.manager')->getViewBuilder('block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setMainContent(array $main_content) {
    $this->mainContent = $main_content;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Track whether a block that shows the main content is displayed or not.
    $main_content_block_displayed = FALSE;

    $build = array();
    // Load all region content assigned via blocks.
    foreach ($this->blockRepository->getVisibleBlocksPerRegion() as $region => $blocks) {
      /** @var $blocks \Drupal\block\BlockInterface[] */
      foreach ($blocks as $key => $block) {
        $block_plugin = $block->getPlugin();
        if ($block_plugin instanceof MainContentBlockPluginInterface) {
          $block_plugin->setMainContent($this->mainContent);
          $main_content_block_displayed = TRUE;
        }
        $build[$region][$key] = $this->blockViewBuilder->view($block);
      }
      if (!empty($build[$region])) {
        // \Drupal\block\BlockRepositoryInterface::getVisibleBlocksPerRegion()
        // returns the blocks in sorted order.
        $build[$region]['#sorted'] = TRUE;
      }
    }

    // If no block that shows the main content is displayed, still show the main
    // content. Otherwise the end user will see all displayed blocks, but not
    // the main content they came for.
    if (!$main_content_block_displayed) {
      $build['content']['system_main'] = $this->mainContent;
    }

    return $build;
  }

}
