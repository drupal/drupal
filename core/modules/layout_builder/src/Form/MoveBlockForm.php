<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for moving a block.
 *
 * @internal
 *   Form classes are internal.
 */
class MoveBlockForm extends FormBase {

  use AjaxFormHelperTrait;
  use LayoutBuilderContextTrait;
  use LayoutBuilderHighlightTrait;
  use LayoutRebuildTrait;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * The section delta.
   *
   * @var int
   */
  protected $delta;

  /**
   * The region name.
   *
   * @var string
   */
  protected $region;

  /**
   * The component uuid.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The Layout Tempstore.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstore;

  /**
   * Constructs a new MoveBlockForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository) {
    $this->layoutTempstore = $layout_tempstore_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_block_move';
  }

  /**
   * Builds the move block form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The original delta of the section.
   * @param string $region
   *   The original region of the block.
   * @param string $uuid
   *   The UUID of the block being updated.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $region = NULL, $uuid = NULL) {
    $parameters = array_slice(func_get_args(), 2);
    foreach ($parameters as $parameter) {
      if (is_null($parameter)) {
        throw new \InvalidArgumentException('MoveBlockForm requires all parameters.');
      }
    }

    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;
    $this->region = $region;

    $form['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockUpdateHighlightId($uuid);

    $sections = $section_storage->getSections();
    $contexts = $this->getPopulatedContexts($section_storage);
    $region_options = [];
    foreach ($sections as $section_delta => $section) {
      $layout = $section->getLayout($contexts);
      $layout_definition = $layout->getPluginDefinition();
      if (!($section_label = $section->getLayoutSettings()['label'])) {
        $section_label = $this->t('Section: @delta', ['@delta' => $section_delta + 1])->render();
      }
      foreach ($layout_definition->getRegions() as $region_name => $region_info) {
        // Group regions by section.
        $region_options[$section_label]["$section_delta:$region_name"] = $this->t(
          '@section, Region: @region',
          ['@section' => $section_label, '@region' => $region_info['label']]
        );
      }
    }

    // $this->region and $this->delta are where the block is currently placed.
    // $selected_region and $selected_delta are the values from this form
    // specifying where the block should be moved to.
    $selected_region = $this->getSelectedRegion($form_state);
    $selected_delta = $this->getSelectedDelta($form_state);
    $form['region'] = [
      '#type' => 'select',
      '#options' => $region_options,
      '#title' => $this->t('Region'),
      '#default_value' => "$selected_delta:$selected_region",
      '#ajax' => [
        'wrapper' => 'layout-builder-components-table',
        'callback' => '::getComponentsWrapper',
      ],
    ];
    $current_section = $sections[$selected_delta];

    $aria_label = $this->t('Blocks in Section: @section, Region: @region', ['@section' => $selected_delta + 1, '@region' => $selected_region]);

    $form['components_wrapper']['components'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Block label'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
      // Create a wrapping element so that the Ajax update also replaces the
      // 'Show block weights' link.
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'id' => 'layout-builder-components-table',
            'class' => ['layout-builder-components-table'],
            'aria-label' => $aria_label,
          ],
        ],
      ],
    ];

    /** @var \Drupal\layout_builder\SectionComponent[] $components */
    $components = $current_section->getComponentsByRegion($selected_region);

    // If the component is not in this region, add it to the listed components.
    if (!isset($components[$uuid])) {
      $components[$uuid] = $sections[$delta]->getComponent($uuid);
    }
    foreach ($components as $component_uuid => $component) {
      /** @var \Drupal\Core\Block\BlockPluginInterface $plugin */
      $plugin = $component->getPlugin();
      $is_current_block = $component_uuid === $uuid;
      $row_classes = [
        'draggable',
        'layout-builder-components-table__row',
      ];

      $label['#wrapper_attributes']['class'] = ['layout-builder-components-table__block-label'];

      if ($is_current_block) {
        // Highlight the current block.
        $label['#markup'] = $this->t('@label (current)', ['@label' => $plugin->label()]);
        $label['#wrapper_attributes']['class'][] = 'layout-builder-components-table__block-label--current';
        $row_classes[] = 'layout-builder-components-table__row--current';
      }
      else {
        $label['#markup'] = $plugin->label();
      }

      $form['components_wrapper']['components'][$component_uuid] = [
        '#attributes' => ['class' => $row_classes],
        'label' => $label,
        'weight' => [
          '#type' => 'weight',
          '#default_value' => $component->getWeight(),
          '#title' => $this->t('Weight for @block block', ['@block' => $plugin->label()]),
          '#title_display' => 'invisible',
          '#attributes' => [
            'class' => ['table-sort-weight'],
          ],
        ],
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Move'),
      '#button_type' => 'primary',
    ];

    $form['#attributes']['data-add-layout-builder-wrapper'] = 'layout-builder--move-blocks-active';

    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $region = $this->getSelectedRegion($form_state);
    $delta = $this->getSelectedDelta($form_state);
    $original_section = $this->sectionStorage->getSection($this->delta);
    $component = $original_section->getComponent($this->uuid);
    $section = $this->sectionStorage->getSection($delta);
    if ($delta !== $this->delta) {
      // Remove component from old section and add it to the new section.
      $original_section->removeComponent($this->uuid);
      $section->insertComponent(0, $component);
    }
    $component->setRegion($region);
    foreach ($form_state->getValue('components') as $uuid => $component_info) {
      $section->getComponent($uuid)->setWeight($component_info['weight']);
    }
    $this->layoutTempstore->set($this->sectionStorage);
  }

  /**
   * Ajax callback for the region select element.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The components wrapper render array.
   */
  public function getComponentsWrapper(array $form, FormStateInterface $form_state) {
    return $form['components_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return $this->rebuildAndClose($this->sectionStorage);
  }

  /**
   * Gets the selected region.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return string
   *   The current region name.
   */
  protected function getSelectedRegion(FormStateInterface $form_state) {
    if ($form_state->hasValue('region')) {
      return explode(':', $form_state->getValue('region'), 2)[1];
    }
    return $this->region;
  }

  /**
   * Gets the selected delta.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return int
   *   The section delta.
   */
  protected function getSelectedDelta(FormStateInterface $form_state) {
    if ($form_state->hasValue('region')) {
      return (int) explode(':', $form_state->getValue('region'))[0];
    }
    return (int) $this->delta;
  }

  /**
   * Provides a title callback.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The original delta of the section.
   * @param string $uuid
   *   The UUID of the block being updated.
   *
   * @return string
   *   The title for the move block form.
   */
  public function title(SectionStorageInterface $section_storage, $delta, $uuid) {
    $block_label = $section_storage
      ->getSection($delta)
      ->getComponent($uuid)
      ->getPlugin()
      ->label();

    return $this->t('Move the @block_label block', ['@block_label' => $block_label]);
  }

}
