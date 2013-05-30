<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\area\Entity.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Provides an area handler which renders an entity in a certain view mode.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("entity")
 */
class Entity extends AreaPluginBase {

  /**
   * Stores the entity type of the result entities.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Overrides \Drupal\views\Plugin\views\area\AreaPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->entityType = $this->definition['entity_type'];
  }

  /**
   * Overrides \Drupal\views\Plugin\views\area\AreaPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['entity_id'] = array('default' => '');
    $options['view_mode'] = array('default' => '');
    $options['tokenize'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\area\AreaPluginBase::buildOptionsForm().
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

    $form['entity_id'] = array(
      '#title' => t('ID'),
      '#type' => 'textfield',
      '#default_value' => $this->options['entity_id'],
    );

    // Add tokenization form elements.
    $this->tokenForm($form, $form_state);
  }

  /**
   * Return the main options, which are shown in the summary title.
   *
   * @return array
   *   All view modes of the entity type.
   */
  protected function buildViewModeOptions() {
    $options = array();
    $view_modes = entity_get_view_modes($this->entityType);
    foreach ($view_modes as $mode => $settings) {
      $options[$mode] = $settings['label'];
    }

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\area\AreaPluginBase::render().
   */
  function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $entity_id = $this->options['entity_id'];
      if ($this->options['tokenize']) {
        $entity_id = $this->view->style_plugin->tokenizeValue($entity_id, 0);
      }
      $entity_id = $this->globalTokenReplace($entity_id);
      if ($entity = entity_load($this->entityType, $entity_id)) {
        return entity_view($entity, $this->options['view_mode']);
      }
    }

    return array();
  }

}
