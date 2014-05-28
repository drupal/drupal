<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\BrokenHandlerTrait.
 */

namespace Drupal\views\Plugin\views;

/**
 * A Trait for Views broken handlers.
 */
trait BrokenHandlerTrait {

  /**
   * Returns this handlers name in the UI.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::defineOptions().
   */
  public function adminLabel($short = FALSE) {
    $args = array(
      '@module' => $this->definition['original_configuration']['provider'],
    );
    return $this->isOptional() ? t('Optional handler is missing (Module: @module) …', $args) : t('Broken/missing handler (Module: @module) …', $args);
  }

  /**
   * The option definition for this handler.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::defineOptions().
   */
  public function defineOptions() {
    return array();
  }

  /**
   * Ensure the main table for this handler is in the query. This is used
   * a lot.
   *
   * @see \Drupal\views\Plugin\views\HandlerBase::ensureMyTable().
   */
  public function ensureMyTable() {
    // No table to ensure.
  }

  /**
   * Modify the views query.
   */
  public function query($group_by = FALSE) {
    /* No query to run */
  }

  /**
   * Provides a form to edit options for this plugin.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::defineOptions().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    if ($this->isOptional()) {
      $description_top = t('The handler for this item is optional. The following details are available:');
    }
    else {
      $description_top = t('The handler for this item is broken or missing. The following details are available:');
    }

    $items = array(
      t('Module: @module', array('@module' => $this->definition['original_configuration']['provider'])),
      t('Table: @table', array('@table' => $this->definition['original_configuration']['table'])),
      t('Field: @field', array('@field' => $this->definition['original_configuration']['field'])),
    );

    $description_bottom = t('Enabling the appropriate module will may solve this issue. Otherwise, check to see if there is a module update available.');

    $form['description'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('form-item', 'description'),
      ),
      'description_top' => array(
        '#markup' => '<p>' . $description_top . '</p>',
      ),
      'detail_list' => array(
        '#theme' => 'item_list',
        '#items' => $items,
      ),
      'description_bottom' => array(
        '#markup' => '<p>' . $description_bottom . '</p>',
      ),
    );
  }

  /**
   * Determines if the handler is considered 'broken'.
   *
   * This means it's a placeholder used when a handler can't be found.
   *
   * @see \Drupal\views\Plugin\views\HandlerBase::broken().
   */
  public function broken() {
    return TRUE;
  }

}
