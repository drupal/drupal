<?php

/**
 * @file
 * Definition of Drupal\system\Plugin\views\row\EntityRow.
 */

namespace Drupal\system\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Generic entity row plugin to provide a common base for all entity types.
 */
class EntityRow extends RowPluginBase {

  /**
   * The table the entity is using for storage.
   *
   * @var string
   */
  public $base_table;

  /**
   * The actual field which is used for the entity id.
   *
   * @var string
   */
  public $base_field;

  /**
   * Stores the entity type of the result entities.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Contains the entity info of the entity type of this row plugin instance.
   *
   * @see entity_get_info
   */
  protected $entityInfo;

  /**
   * Contains an array of render arrays, one for each rendered entity.
   *
   * @var array
   */
  protected $build = array();

  /**
   * Overrides Drupal\views\Plugin\views\PluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->entityType = $this->definition['entity_type'];
    $this->entityInfo = entity_get_info($this->entityType);
    $this->base_table = $this->entityInfo['base_table'];
    $this->base_field = $this->entityInfo['entity_keys']['id'];
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode'] = array('default' => '');

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = $this->buildViewModeOptions();
    $form['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('View mode'),
      '#default_value' => $this->options['view_mode'],
    );
  }

  /**
   * Return the main options, which are shown in the summary title.
   */
  protected function buildViewModeOptions() {
    $options = array();
    if (!empty($this->entityInfo['view_modes'])) {
      foreach ($this->entityInfo['view_modes'] as $mode => $settings) {
        $options[$mode] = $settings['label'];
      }
    }

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\PluginBase::summaryTitle().
   */
  public function summaryTitle() {
    $options = $this->buildViewModeOptions();
    return check_plain($options[$this->options['view_mode']]);
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::pre_render().
   */
  public function pre_render($result) {
    parent::pre_render($result);

    if ($result) {
      // Get all entities which will be used to render in rows.c
      $entities = array();
      foreach ($result as $row) {
        $entity = $row->_entity;
        $entity->view = $this->view;
        $entities[$entity->id()] = $entity;
      }

      // Prepare the render arrays for all rows.
      $this->build = entity_view_multiple($entities, $this->options['view_mode']);
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::render().
   */
  function render($row) {
    $entity_id = $row->{$this->field_alias};
    return $this->build[$entity_id];
  }
}
