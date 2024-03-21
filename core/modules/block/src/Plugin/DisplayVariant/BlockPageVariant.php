<?php

namespace Drupal\block\Plugin\DisplayVariant;

use Drupal\block\BlockRepositoryInterface;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Block\MessagesBlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Display\Attribute\PageDisplayVariant;
use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Display\VariantBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a page display variant that decorates the main content with blocks.
 *
 * To ensure essential information is displayed, each essential part of a page
 * has a corresponding block plugin interface, so that BlockPageVariant can
 * automatically provide a fallback in case no block for each of these
 * interfaces is placed.
 *
 * @see \Drupal\Core\Block\MainContentBlockPluginInterface
 * @see \Drupal\Core\Block\MessagesBlockPluginInterface
 */
#[PageDisplayVariant(
  id: 'block_page',
  admin_label: new TranslatableMarkup('Page with blocks')
)]
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
   * The Block entity type list cache tags.
   *
   * @var string[]
   */
  protected $blockListCacheTags;

  /**
   * The render array representing the main page content.
   *
   * @var array
   */
  protected $mainContent = [];

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

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
   * @param string[] $block_list_cache_tags
   *   The Block entity type list cache tags.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockRepositoryInterface $block_repository, EntityViewBuilderInterface $block_view_builder, array $block_list_cache_tags) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockRepository = $block_repository;
    $this->blockViewBuilder = $block_view_builder;
    $this->blockListCacheTags = $block_list_cache_tags;
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
      $container->get('entity_type.manager')->getViewBuilder('block'),
      $container->get('entity_type.manager')->getDefinition('block')->getListCacheTags()
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
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Track whether blocks showing the main content and messages are displayed.
    $main_content_block_displayed = FALSE;
    $messages_block_displayed = FALSE;

    $build = [
      '#cache' => [
        'tags' => $this->blockListCacheTags,
      ],
    ];
    // Load all region content assigned via blocks.
    $cacheable_metadata_list = [];
    foreach ($this->blockRepository->getVisibleBlocksPerRegion($cacheable_metadata_list) as $region => $blocks) {
      /** @var \Drupal\block\BlockInterface[] $blocks */
      foreach ($blocks as $key => $block) {
        $block_plugin = $block->getPlugin();
        if ($block_plugin instanceof MainContentBlockPluginInterface) {
          $block_plugin->setMainContent($this->mainContent);
          $main_content_block_displayed = TRUE;
        }
        elseif ($block_plugin instanceof TitleBlockPluginInterface) {
          $block_plugin->setTitle($this->title);
        }
        elseif ($block_plugin instanceof MessagesBlockPluginInterface) {
          $messages_block_displayed = TRUE;
        }
        $build[$region][$key] = $this->blockViewBuilder->view($block);

        // The main content block cannot be cached: it is a placeholder for the
        // render array returned by the controller. It should be rendered as-is,
        // with other placed blocks "decorating" it. Analogous reasoning for the
        // title block.
        if ($block_plugin instanceof MainContentBlockPluginInterface || $block_plugin instanceof TitleBlockPluginInterface) {
          unset($build[$region][$key]['#cache']['keys']);
        }
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

    // If no block displays status messages, still render them.
    if (!$messages_block_displayed) {
      $build['content']['messages'] = [
        '#weight' => -1000,
        '#type' => 'status_messages',
        '#include_fallback' => TRUE,
      ];
    }

    // If any render arrays are manually placed, render arrays and blocks must
    // be sorted.
    if (!$main_content_block_displayed || !$messages_block_displayed) {
      unset($build['content']['#sorted']);
    }

    // The access results' cacheability is currently added to the top level of the
    // render array. This is done to prevent issues with empty regions being
    // displayed.
    // This would need to be changed to allow caching of block regions, as each
    // region must then have the relevant cacheable metadata.
    $merged_cacheable_metadata = CacheableMetadata::createFromRenderArray($build);
    foreach ($cacheable_metadata_list as $cacheable_metadata) {
      $merged_cacheable_metadata = $merged_cacheable_metadata->merge($cacheable_metadata);
    }
    $merged_cacheable_metadata->applyTo($build);

    return $build;
  }

}
