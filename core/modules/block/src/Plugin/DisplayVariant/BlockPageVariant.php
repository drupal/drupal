<?php

/**
 * @file
 * Contains \Drupal\block\Plugin\DisplayVariant\BlockPageVariant.
 */

namespace Drupal\block\Plugin\DisplayVariant;

use Drupal\block\BlockRepositoryInterface;
use Drupal\block\Event\BlockContextEvent;
use Drupal\block\Event\BlockEvents;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Block\MessagesBlockPluginInterface;
use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Display\VariantBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param string[] $block_list_cache_tags
   *   The Block entity type list cache tags.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockRepositoryInterface $block_repository, EntityViewBuilderInterface $block_view_builder, EventDispatcherInterface $dispatcher, array $block_list_cache_tags) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockRepository = $block_repository;
    $this->blockViewBuilder = $block_view_builder;
    $this->dispatcher = $dispatcher;
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
      $container->get('entity.manager')->getViewBuilder('block'),
      $container->get('event_dispatcher'),
      $container->get('entity.manager')->getDefinition('block')->getListCacheTags()
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
    // Track whether blocks showing the main content and messages are displayed.
    $main_content_block_displayed = FALSE;
    $messages_block_displayed = FALSE;

    $build = [
      '#cache' => [
        'tags' => $this->blockListCacheTags,
      ],
    ];
    $contexts = $this->getActiveBlockContexts();
    // Load all region content assigned via blocks.
    foreach ($this->blockRepository->getVisibleBlocksPerRegion($contexts) as $region => $blocks) {
      /** @var $blocks \Drupal\block\BlockInterface[] */
      foreach ($blocks as $key => $block) {
        $block_plugin = $block->getPlugin();
        if ($block_plugin instanceof MainContentBlockPluginInterface) {
          $block_plugin->setMainContent($this->mainContent);
          $main_content_block_displayed = TRUE;
        }
        elseif ($block_plugin instanceof MessagesBlockPluginInterface) {
          $messages_block_displayed = TRUE;
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

    // If no block displays status messages, still render them.
    if (!$messages_block_displayed) {
      $build['content']['messages'] = [
        '#weight' => -1000,
        '#type' => 'status_messages',
      ];
    }

    return $build;
  }

  /**
   * Returns an array of context objects to set on the blocks.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of contexts to set on the blocks.
   */
  protected function getActiveBlockContexts() {
    return $this->dispatcher->dispatch(BlockEvents::ACTIVE_CONTEXT, new BlockContextEvent())->getContexts();
  }

}
