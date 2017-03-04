<?php

namespace Drupal\block;

use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class to build a listing of block entities.
 *
 * @see \Drupal\block\Entity\Block
 */
class BlockListBuilder extends ConfigEntityListBuilder implements FormInterface {

  /**
   * The theme containing the blocks.
   *
   * @var string
   */
  protected $theme;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  protected $limit = FALSE;

  /**
   * Constructs a new BlockListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ThemeManagerInterface $theme_manager, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);

    $this->themeManager = $theme_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('theme.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param string|null $theme
   *   (optional) The theme to display the blocks for. If NULL, the current
   *   theme will be used.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The block list as a renderable array.
   */
  public function render($theme = NULL, Request $request = NULL) {
    $this->request = $request;
    $this->theme = $theme;

    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_admin_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'block/drupal.block';
    $form['#attached']['library'][] = 'block/drupal.block.admin';
    $form['#attributes']['class'][] = 'clearfix';

    // Build the form tree.
    $form['blocks'] = $this->buildBlocksForm();

    $form['actions'] = [
      '#tree' => FALSE,
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save blocks'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Builds the main "Blocks" portion of the form.
   *
   * @return array
   */
  protected function buildBlocksForm() {
    // Build blocks first for each region.
    $blocks = [];
    $entities = $this->load();
    /** @var \Drupal\block\BlockInterface[] $entities */
    foreach ($entities as $entity_id => $entity) {
      $definition = $entity->getPlugin()->getPluginDefinition();
      $blocks[$entity->getRegion()][$entity_id] = [
        'label' => $entity->label(),
        'entity_id' => $entity_id,
        'weight' => $entity->getWeight(),
        'entity' => $entity,
        'category' => $definition['category'],
        'status' => $entity->status(),
      ];
    }

    $form = [
      '#type' => 'table',
      '#header' => [
        $this->t('Block'),
        $this->t('Category'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'id' => 'blocks',
      ],
    ];

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get an unique weight.
    $weight_delta = round(count($entities) / 2);

    $placement = FALSE;
    if ($this->request->query->has('block-placement')) {
      $placement = $this->request->query->get('block-placement');
      $form['#attached']['drupalSettings']['blockPlacement'] = $placement;
    }

    // Loop over each region and build blocks.
    $regions = $this->systemRegionList($this->getThemeName(), REGIONS_VISIBLE);
    foreach ($regions as $region => $title) {
      $form['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region,
        'hidden' => FALSE,
      ];
      $form['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region,
      ];

      $form['region-' . $region] = [
        '#attributes' => [
          'class' => ['region-title', 'region-title-' . $region],
          'no_striping' => TRUE,
        ],
      ];
      $form['region-' . $region]['title'] = [
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => ['class' => 'region-title__action'],
          ]
        ],
        '#prefix' => $title,
        '#type' => 'link',
        '#title' => $this->t('Place block <span class="visually-hidden">in the %region region</span>', ['%region' => $title]),
        '#url' => Url::fromRoute('block.admin_library', ['theme' => $this->getThemeName()], ['query' => ['region' => $region]]),
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];

      $form['region-' . $region . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region . '-message',
            empty($blocks[$region]) ? 'region-empty' : 'region-populated',
          ],
        ],
      ];
      $form['region-' . $region . '-message']['message'] = [
        '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
      ];

      if (isset($blocks[$region])) {
        foreach ($blocks[$region] as $info) {
          $entity_id = $info['entity_id'];

          $form[$entity_id] = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
          ];
          $form[$entity_id]['#attributes']['class'][] = $info['status'] ? 'block-enabled' : 'block-disabled';
          if ($placement && $placement == Html::getClass($entity_id)) {
            $form[$entity_id]['#attributes']['class'][] = 'color-success';
            $form[$entity_id]['#attributes']['class'][] = 'js-block-placed';
          }
          $form[$entity_id]['info'] = [
            '#plain_text' => $info['status'] ? $info['label'] : $this->t('@label (disabled)', ['@label' => $info['label']]),
            '#wrapper_attributes' => [
              'class' => ['block'],
            ],
          ];
          $form[$entity_id]['type'] = [
            '#markup' => $info['category'],
          ];
          $form[$entity_id]['region-theme']['region'] = [
            '#type' => 'select',
            '#default_value' => $region,
            '#required' => TRUE,
            '#title' => $this->t('Region for @block block', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#options' => $regions,
            '#attributes' => [
              'class' => ['block-region-select', 'block-region-' . $region],
            ],
            '#parents' => ['blocks', $entity_id, 'region'],
          ];
          $form[$entity_id]['region-theme']['theme'] = [
            '#type' => 'hidden',
            '#value' => $this->getThemeName(),
            '#parents' => ['blocks', $entity_id, 'theme'],
          ];
          $form[$entity_id]['weight'] = [
            '#type' => 'weight',
            '#default_value' => $info['weight'],
            '#delta' => $weight_delta,
            '#title' => $this->t('Weight for @block block', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['block-weight', 'block-weight-' . $region],
            ],
          ];
          $form[$entity_id]['operations'] = $this->buildOperations($info['entity']);
        }
      }
    }

    // Do not allow disabling the main system content block when it is present.
    if (isset($form['system_main']['region'])) {
      $form['system_main']['region']['#required'] = TRUE;
    }
    return $form;
  }

  /**
   * Gets the name of the theme used for this block listing.
   *
   * @return string
   *   The name of the theme.
   */
  protected function getThemeName() {
    // If no theme was specified, use the current theme.
    if (!$this->theme) {
      $this->theme = $this->themeManager->getActiveTheme()->getName();
    }
    return $this->theme;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    return $this->getStorage()->getQuery()
      ->condition('theme', $this->getThemeName())
      ->sort($this->entityType->getKey('id'))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Configure');
    }

    if (isset($operations['delete'])) {
      $operations['delete']['title'] = $this->t('Remove');
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entities = $this->storage->loadMultiple(array_keys($form_state->getValue('blocks')));
    /** @var \Drupal\block\BlockInterface[] $entities */
    foreach ($entities as $entity_id => $entity) {
      $entity_values = $form_state->getValue(['blocks', $entity_id]);
      $entity->setWeight($entity_values['weight']);
      $entity->setRegion($entity_values['region']);
      $entity->save();
    }
    drupal_set_message(t('The block settings have been updated.'));

    // Remove any previously set block placement.
    $this->request->query->remove('block-placement');
  }

  /**
   * Wraps system_region_list().
   */
  protected function systemRegionList($theme, $show = REGIONS_ALL) {
    return system_region_list($theme, $show);
  }

}
