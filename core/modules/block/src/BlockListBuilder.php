<?php

/**
 * @file
 * Contains \Drupal\block\BlockListBuilder.
 */

namespace Drupal\block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
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
   * The regions containing the blocks.
   *
   * @var array
   */
  protected $regions;

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
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new BlockListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, BlockManagerInterface $block_manager) {
    parent::__construct($entity_type, $storage);

    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // If no theme was specified, use the current theme.
    if (!$this->theme) {
      $this->theme = \Drupal::theme()->getActiveTheme()->getName();
    }

    // Store the region list.
    $this->regions = system_region_list($this->theme, REGIONS_VISIBLE);

    // Load only blocks for this theme, and sort them.
    // @todo Move the functionality of _block_rehash() out of the listing page.
    $entities = _block_rehash($this->theme);

    // Sort the blocks using \Drupal\block\Entity\Block::sort().
    uasort($entities, array($this->entityType->getClass(), 'sort'));
    return $entities;
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
    // If no theme was specified, use the current theme.
    $this->theme = $theme ?: \Drupal::theme()->getActiveTheme()->getName();

    return \Drupal::formBuilder()->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_admin_display_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * Form constructor for the main block administration form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $placement = FALSE;
    if ($this->request->query->has('block-placement')) {
      $placement = $this->request->query->get('block-placement');
      $form['#attached']['drupalSettings']['blockPlacement'] = $placement;
    }
    $entities = $this->load();
    $form['#theme'] = array('block_list');
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'block/drupal.block';
    $form['#attached']['library'][] = 'block/drupal.block.admin';
    $form['#attributes']['class'][] = 'clearfix';

    // Add a last region for disabled blocks.
    $block_regions_with_disabled = $this->regions + array(BlockInterface::BLOCK_REGION_NONE => BlockInterface::BLOCK_REGION_NONE);
    $form['block_regions'] = array(
      '#type' => 'value',
      '#value' => $block_regions_with_disabled,
    );

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get an unique weight.
    $weight_delta = round(count($entities) / 2);

    // Build the form tree.
    $form['edited_theme'] = array(
      '#type' => 'value',
      '#value' => $this->theme,
    );
    $form['blocks'] = array(
      '#type' => 'table',
      '#header' => array(
        t('Block'),
        t('Category'),
        t('Region'),
        t('Weight'),
        t('Operations'),
      ),
      '#attributes' => array(
        'id' => 'blocks',
      ),
    );

    // Build blocks first for each region.
    foreach ($entities as $entity_id => $entity) {
      $definition = $entity->getPlugin()->getPluginDefinition();
      $blocks[$entity->getRegion()][$entity_id] = array(
        'label' => $entity->label(),
        'entity_id' => $entity_id,
        'weight' => $entity->getWeight(),
        'entity' => $entity,
        'category' => $definition['category'],
      );
    }

    // Loop over each region and build blocks.
    foreach ($block_regions_with_disabled as $region => $title) {
      $form['blocks']['#tabledrag'][] = array(
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region,
        'hidden' => FALSE,
      );
      $form['blocks']['#tabledrag'][] = array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region,
      );

      $form['blocks']['region-' . $region] = array(
        '#attributes' => array(
          'class' => array('region-title', 'region-title-' . $region),
          'no_striping' => TRUE,
        ),
      );
      $form['blocks']['region-' . $region]['title'] = array(
        '#markup' => $region != BlockInterface::BLOCK_REGION_NONE ? $title : t('Disabled', array(), array('context' => 'Plural')),
        '#wrapper_attributes' => array(
          'colspan' => 5,
        ),
      );

      $form['blocks']['region-' . $region . '-message'] = array(
        '#attributes' => array(
          'class' => array(
            'region-message',
            'region-' . $region . '-message',
            empty($blocks[$region]) ? 'region-empty' : 'region-populated',
          ),
        ),
      );
      $form['blocks']['region-' . $region . '-message']['message'] = array(
        '#markup' => '<em>' . t('No blocks in this region') . '</em>',
        '#wrapper_attributes' => array(
          'colspan' => 5,
        ),
      );

      if (isset($blocks[$region])) {
        foreach ($blocks[$region] as $info) {
          $entity_id = $info['entity_id'];

          $form['blocks'][$entity_id] = array(
            '#attributes' => array(
              'class' => array('draggable'),
            ),
          );
          if ($placement && $placement == Html::getClass($entity_id)) {
            $form['blocks'][$entity_id]['#attributes']['class'][] = 'color-warning';
            $form['blocks'][$entity_id]['#attributes']['class'][] = 'js-block-placed';
          }
          $form['blocks'][$entity_id]['info'] = array(
            '#markup' => SafeMarkup::checkPlain($info['label']),
            '#wrapper_attributes' => array(
              'class' => array('block'),
            ),
          );
          $form['blocks'][$entity_id]['type'] = array(
            '#markup' => $info['category'],
          );
          $form['blocks'][$entity_id]['region-theme']['region'] = array(
            '#type' => 'select',
            '#default_value' => $region,
            '#empty_value' => BlockInterface::BLOCK_REGION_NONE,
            '#title' => t('Region for @block block', array('@block' => $info['label'])),
            '#title_display' => 'invisible',
            '#options' => $this->regions,
            '#attributes' => array(
              'class' => array('block-region-select', 'block-region-' . $region),
            ),
            '#parents' => array('blocks', $entity_id, 'region'),
          );
          $form['blocks'][$entity_id]['region-theme']['theme'] = array(
            '#type' => 'hidden',
            '#value' => $this->theme,
            '#parents' => array('blocks', $entity_id, 'theme'),
          );
          $form['blocks'][$entity_id]['weight'] = array(
            '#type' => 'weight',
            '#default_value' => $info['weight'],
            '#delta' => $weight_delta,
            '#title' => t('Weight for @block block', array('@block' => $info['label'])),
            '#title_display' => 'invisible',
            '#attributes' => array(
              'class' => array('block-weight', 'block-weight-' . $region),
            ),
          );
          $form['blocks'][$entity_id]['operations'] = $this->buildOperations($info['entity']);
        }
      }
    }

    // Do not allow disabling the main system content block when it is present.
    if (isset($form['blocks']['system_main']['region'])) {
      $form['blocks']['system_main']['region']['#required'] = TRUE;
    }

    $form['actions'] = array(
      '#tree' => FALSE,
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save blocks'),
      '#button_type' => 'primary',
    );

    $form['place_blocks']['title'] = array(
      '#type' => 'container',
      '#markup' => '<h3>' . t('Place blocks') . '</h3>',
      '#attributes' => array(
        'class' => array(
          'entity-meta__header',
        ),
      ),
    );

    $form['place_blocks']['filter'] = array(
      '#type' => 'search',
      '#title' => t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => t('Filter by block name'),
      '#attributes' => array(
        'class' => array('block-filter-text'),
        'data-element' => '.entity-meta',
        'title' => t('Enter a part of the block name to filter by.'),
      ),
    );

    $form['place_blocks']['list']['#type'] = 'container';
    $form['place_blocks']['list']['#attributes']['class'][] = 'entity-meta';

    // Only add blocks which work without any available context.
    $definitions = $this->blockManager->getDefinitionsForContexts();
    $sorted_definitions = $this->blockManager->getSortedDefinitions($definitions);
    foreach ($sorted_definitions as $plugin_id => $plugin_definition) {
      $category = SafeMarkup::checkPlain($plugin_definition['category']);
      $category_key = 'category-' . $category;
      if (!isset($form['place_blocks']['list'][$category_key])) {
        $form['place_blocks']['list'][$category_key] = array(
          '#type' => 'details',
          '#title' => $category,
          '#open' => TRUE,
          'content' => array(
            '#theme' => 'links',
            '#links' => array(),
            '#attributes' => array(
              'class' => array(
                'block-list',
              ),
            ),
          ),
        );
      }
      $form['place_blocks']['list'][$category_key]['content']['#links'][$plugin_id] = array(
        'title' => $plugin_definition['admin_label'],
        'url' => Url::fromRoute('block.admin_add', [
          'plugin_id' => $plugin_id,
          'theme' => $this->theme
        ]),
        'attributes' => array(
          'class' => array('use-ajax', 'block-filter-text-source'),
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(array(
            'width' => 700,
          )),
        ),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Configure');
    }

    return $operations;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   *
   * Form submission handler for the main block administration form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entities = $this->storage->loadMultiple(array_keys($form_state->getValue('blocks')));
    foreach ($entities as $entity_id => $entity) {
      $entity_values = $form_state->getValue(array('blocks', $entity_id));
      $entity->setWeight($entity_values['weight']);
      $entity->setRegion($entity_values['region']);
      if ($entity->getRegion() == BlockInterface::BLOCK_REGION_NONE) {
        $entity->disable();
      }
      else {
        $entity->enable();
      }
      $entity->save();
    }
    drupal_set_message(t('The block settings have been updated.'));

    // Remove any previously set block placement.
    $this->request->query->remove('block-placement');
  }

}
