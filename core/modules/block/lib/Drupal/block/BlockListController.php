<?php

/**
 * @file
 * Contains \Drupal\block\BlockListController.
 */

namespace Drupal\block;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\block\Plugin\Core\Entity\Block;
use Drupal\Core\Form\FormInterface;

/**
 * Defines the block list controller.
 */
class BlockListController extends ConfigEntityListController implements FormInterface {

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
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityListController::load().
   */
  public function load() {
    // If no theme was specified, use the current theme.
    if (!$this->theme) {
      $this->theme = $GLOBALS['theme'];
    }

    // Store the region list.
    $this->regions = system_region_list($this->theme, REGIONS_VISIBLE);

    // Load only blocks for this theme, and sort them.
    // @todo Move the functionality of _block_rehash() out of the listing page.
    $entities = _block_rehash($this->theme);
    uasort($entities, 'static::sort');
    return $entities;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::render().
   */
  public function render($theme = NULL) {
    // If no theme was specified, use the current theme.
    $this->theme = $theme ?: $GLOBALS['theme_key'];

    return drupal_get_form($this);
  }

  /**
   * Sorts active blocks by region then weight; sorts inactive blocks by name.
   */
  protected function sort(Block $a, Block $b) {
    static $regions;
    // We need the region list to correctly order by region.
    if (!isset($regions)) {
      $regions = array_flip(array_keys($this->regions));
      $regions[BLOCK_REGION_NONE] = count($regions);
    }

    // Separate enabled from disabled.
    $status = $b->get('status') - $a->get('status');
    if ($status) {
      return $status;
    }
    // Sort by region (in the order defined by theme .info.yml file).
    $aregion = $a->get('region');
    $bregion = $b->get('region');
    if ((!empty($aregion) && !empty($bregion)) && ($place = ($regions[$aregion] - $regions[$bregion]))) {
      return $place;
    }
    // Sort by weight, unless disabled.
    if ($a->get('region') != BLOCK_REGION_NONE) {
      $weight = $a->get('weight') - $b->get('weight');
      if ($weight) {
        return $weight;
      }
    }
    // Sort by label.
    return strcmp($a->label(), $b->label());
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'block_admin_display_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * Form constructor for the main block administration form.
   */
  public function buildForm(array $form, array &$form_state) {
    $entities = $this->load();
    $form['#attached']['css'][] = drupal_get_path('module', 'block') . '/css/block.admin.css';
    $form['#attached']['library'][] = array('system', 'drupal.tableheader');
    $form['#attached']['library'][] = array('block', 'drupal.block');

    // Add a last region for disabled blocks.
    $block_regions_with_disabled = $this->regions + array(BLOCK_REGION_NONE => BLOCK_REGION_NONE);

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
      $info = $entity->getPlugin()->getDefinition();
      $info['entity_id'] = $entity_id;
      $blocks[$entity->get('region')][] = $info;
    }

    // Loop over each region and build blocks.
    foreach ($block_regions_with_disabled as $region => $title) {
      $form['blocks']['#tabledrag'][] = array(
        'match',
        'sibling',
        'block-region-select',
        'block-region-' . $region,
        NULL,
        FALSE,
      );
      $form['blocks']['#tabledrag'][] = array(
        'order',
        'sibling',
        'block-weight',
        'block-weight-' . $region,
      );

      $form['blocks'][$region] = array(
        '#attributes' => array(
          'class' => array('region-title', 'region-title-' . $region, 'odd'),
          'no_striping' => TRUE,
        ),
      );
      $form['blocks'][$region]['title'] = array(
        '#markup' => $region != BLOCK_REGION_NONE ? $title : t('Disabled'),
        '#wrapper_attributes' => array(
          'colspan' => 5,
        ),
      );

      $form['blocks'][$region . '-message'] = array(
        '#attributes' => array(
          'class' => array(
            'region-message',
            'region-' . $region . '-message',
            empty($blocks[$region]) ? 'region-empty' : 'region-populated',
          ),
        ),
      );
      $form['blocks'][$region . '-message']['message'] = array(
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

          $form['blocks'][$entity_id]['info'] = array(
            '#markup' => check_plain($info['admin_label']),
            '#wrapper_attributes' => array(
              'class' => array('block'),
            ),
          );
          $form['blocks'][$entity_id]['region-theme']['region'] = array(
            '#type' => 'select',
            '#default_value' => $region,
            '#empty_value' => BLOCK_REGION_NONE,
            '#title_display' => 'invisible',
            '#title' => t('Region for @block block', array('@block' => $info['admin_label'])),
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
            '#default_value' => $entity->get('weight'),
            '#delta' => $weight_delta,
            '#title_display' => 'invisible',
            '#title' => t('Weight for @block block', array('@block' => $info['admin_label'])),
            '#attributes' => array(
              'class' => array('block-weight', 'block-weight-' . $region),
            ),
          );
          $links['configure'] = array(
            'title' => t('configure'),
            'href' => 'admin/structure/block/manage/' . $entity_id . '/configure',
          );
          $links['delete'] = array(
            'title' => t('delete'),
            'href' => 'admin/structure/block/manage/' . $entity_id . '/delete',
          );
          $form['blocks'][$entity_id]['operations'] = array(
            '#type' => 'operations',
            '#links' => $links,
          );
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
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    // No validation.
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   *
   * Form submission handler for the main block administration form.
   */
  public function submitForm(array &$form, array &$form_state) {
    $entities = entity_load_multiple('block', array_keys($form_state['values']['blocks']));
    foreach ($entities as $entity_id => $entity) {
      $entity->set('weight', $form_state['values']['blocks'][$entity_id]['weight']);
      $entity->set('region', $form_state['values']['blocks'][$entity_id]['region']);
      if ($entity->get('region') == BLOCK_REGION_NONE) {
        $entity->disable();
      }
      else {
        $entity->enable();
      }
      $entity->save();
    }
    drupal_set_message(t('The block settings have been updated.'));
    cache_invalidate_tags(array('content' => TRUE));
  }

}
