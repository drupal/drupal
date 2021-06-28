<?php

namespace Drupal\layout_builder\Controller;

use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to choose a new block.
 *
 * @internal
 *   Controller classes are internal.
 */
class ChooseBlockController implements ContainerInjectionInterface {

  use AjaxHelperTrait;
  use LayoutBuilderContextTrait;
  use LayoutBuilderHighlightTrait;
  use StringTranslationTrait;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * ChooseBlockController constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(BlockManagerInterface $block_manager, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->blockManager = $block_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Provides the UI for choosing a new block.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   * @param string $region
   *   The region the block is going in.
   *
   * @return array
   *   A render array.
   */
  public function build(SectionStorageInterface $section_storage, int $delta, $region) {
    if ($this->entityTypeManager->hasDefinition('block_content_type') && $types = $this->entityTypeManager->getStorage('block_content_type')->loadMultiple()) {
      if (count($types) === 1) {
        $type = reset($types);
        $plugin_id = 'inline_block:' . $type->id();
        if ($this->blockManager->hasDefinition($plugin_id)) {
          $url = Url::fromRoute('layout_builder.add_block', [
            'section_storage_type' => $section_storage->getStorageType(),
            'section_storage' => $section_storage->getStorageId(),
            'delta' => $delta,
            'region' => $region,
            'plugin_id' => $plugin_id,
          ]);
        }
      }
      else {
        $url = Url::fromRoute('layout_builder.choose_inline_block', [
          'section_storage_type' => $section_storage->getStorageType(),
          'section_storage' => $section_storage->getStorageId(),
          'delta' => $delta,
          'region' => $region,
        ]);
      }
      if (isset($url)) {
        $build['add_block'] = [
          '#type' => 'link',
          '#url' => $url,
          '#title' => $this->t('Create @entity_type', [
            '@entity_type' => $this->entityTypeManager->getDefinition('block_content')->getSingularLabel(),
          ]),
          '#attributes' => $this->getAjaxAttributes(),
          '#access' => $this->currentUser->hasPermission('create and edit custom blocks'),
        ];
        $build['add_block']['#attributes']['class'][] = 'inline-block-create-button';
      }
    }

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter by block name'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by block name'),
      '#attributes' => [
        'class' => ['js-layout-builder-filter'],
        'title' => $this->t('Enter a part of the block name to filter by.'),
      ],
    ];

    $block_categories['#type'] = 'container';
    $block_categories['#attributes']['class'][] = 'block-categories';
    $block_categories['#attributes']['class'][] = 'js-layout-builder-categories';
    $block_categories['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockAddHighlightId($delta, $region);

    $definitions = $this->blockManager->getFilteredDefinitions('layout_builder', $this->getPopulatedContexts($section_storage), [
      'section_storage' => $section_storage,
      'delta' => $delta,
      'region' => $region,
    ]);
    $grouped_definitions = $this->blockManager->getGroupedDefinitions($definitions);
    foreach ($grouped_definitions as $category => $blocks) {
      $block_categories[$category]['#type'] = 'details';
      $block_categories[$category]['#attributes']['class'][] = 'js-layout-builder-category';
      $block_categories[$category]['#open'] = TRUE;
      $block_categories[$category]['#title'] = $category;
      $block_categories[$category]['links'] = $this->getBlockLinks($section_storage, $delta, $region, $blocks);
    }
    $build['block_categories'] = $block_categories;
    return $build;
  }

  /**
   * Provides the UI for choosing a new inline block.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   * @param string $region
   *   The region the block is going in.
   *
   * @return array
   *   A render array.
   */
  public function inlineBlockList(SectionStorageInterface $section_storage, int $delta, $region) {
    $definitions = $this->blockManager->getFilteredDefinitions('layout_builder', $this->getPopulatedContexts($section_storage), [
      'section_storage' => $section_storage,
      'region' => $region,
      'list' => 'inline_blocks',
    ]);
    $blocks = $this->blockManager->getGroupedDefinitions($definitions);
    $build = [];
    $inline_blocks_category = (string) $this->t('Inline blocks');
    if (isset($blocks[$inline_blocks_category])) {
      $build['links'] = $this->getBlockLinks($section_storage, $delta, $region, $blocks[$inline_blocks_category]);
      $build['links']['#attributes']['class'][] = 'inline-block-list';
      foreach ($build['links']['#links'] as &$link) {
        $link['attributes']['class'][] = 'inline-block-list__item';
      }
      $build['back_button'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('layout_builder.choose_block',
          [
            'section_storage_type' => $section_storage->getStorageType(),
            'section_storage' => $section_storage->getStorageId(),
            'delta' => $delta,
            'region' => $region,
          ]
        ),
        '#title' => $this->t('Back'),
        '#attributes' => $this->getAjaxAttributes(),
      ];
    }
    $build['links']['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockAddHighlightId($delta, $region);
    return $build;
  }

  /**
   * Gets a render array of block links.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   * @param string $region
   *   The region the block is going in.
   * @param array $blocks
   *   The information for each block.
   *
   * @return array
   *   The block links render array.
   */
  protected function getBlockLinks(SectionStorageInterface $section_storage, int $delta, $region, array $blocks) {
    $links = [];
    foreach ($blocks as $block_id => $block) {
      $attributes = $this->getAjaxAttributes();
      $attributes['class'][] = 'js-layout-builder-block-link';
      $link = [
        'title' => $block['admin_label'],
        'url' => Url::fromRoute('layout_builder.add_block',
          [
            'section_storage_type' => $section_storage->getStorageType(),
            'section_storage' => $section_storage->getStorageId(),
            'delta' => $delta,
            'region' => $region,
            'plugin_id' => $block_id,
          ]
        ),
        'attributes' => $attributes,
      ];

      $links[] = $link;
    }
    return [
      '#theme' => 'links',
      '#links' => $links,
    ];
  }

  /**
   * Get dialog attributes if an ajax request.
   *
   * @return array
   *   The attributes array.
   */
  protected function getAjaxAttributes() {
    if ($this->isAjax()) {
      return [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'dialog',
        'data-dialog-renderer' => 'off_canvas',
      ];
    }
    return [];
  }

}
