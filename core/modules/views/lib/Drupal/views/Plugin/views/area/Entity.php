<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\area\Entity.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Provides an area handler which renders an entity in a certain view mode.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("entity")
 */
class Entity extends TokenizeAreaPluginBase {

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
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Per default we enable tokenize, as this is the most common use case for
    // this handler.
    $options['tokenize']['default'] = TRUE;

    $options['entity_id'] = array('default' => '');
    $options['view_mode'] = array('default' => 'default');
    $options['bypass_access'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
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

    $form['bypass_access'] = array(
      '#type' => 'checkbox',
      '#title' => t('Bypass access checks'),
      '#description' => t('If enabled, access permissions for rendering the entity are not checked.'),
      '#default_value' => !empty($this->options['bypass_access']),
    );
  }

  /**
   * Return the main options, which are shown in the summary title.
   *
   * @return array
   *   All view modes of the entity type.
   */
  protected function buildViewModeOptions() {
    $options = array('default' => t('Default'));
    $view_modes = entity_get_view_modes($this->entityType);
    foreach ($view_modes as $mode => $settings) {
      $options[$mode] = $settings['label'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $entity_id = $this->tokenizeValue($this->options['entity_id']);
      $entity = entity_load($this->entityType, $entity_id);
      if ($entity && (!empty($this->options['bypass_access']) || $entity->access('view'))) {
        return entity_view($entity, $this->options['view_mode']);
      }
    }

    return array();
  }

}
