<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\Broken.
 */

namespace Drupal\views\Plugin\views\argument;

/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("broken")
 */
class Broken extends ArgumentPluginBase {

  public function adminLabel($short = FALSE) {
    $args = array(
      '@module' => $this->definition['original_configuration']['provider'],
    );
    return $this->isOptional() ? t('Optional handler is missing (Module: @module) …', $args) : t('Broken/missing handler (Module: @module) …', $args);
  }

  public function defineOptions() { return array(); }
  public function ensureMyTable() { /* No table to ensure! */ }
  public function query($group_by = FALSE) { /* No query to run */ }

  /**
   * {@inheritdoc}
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
   * Determine if the handler is considered 'broken'
   */
  public function broken() { return TRUE; }

}
