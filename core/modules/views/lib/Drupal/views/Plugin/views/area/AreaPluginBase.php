<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\AreaPluginBase.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Plugin\views\HandlerBase;

/**
 * @defgroup views_area_handlers Views area handlers
 * @{
 * Handlers to tell Views what can display in header, footer
 * and empty text in a view.
 */

/**
 * Base class for area handlers.
 *
 * @ingroup views_area_handlers
 */
abstract class AreaPluginBase extends HandlerBase {

  /**
   * Overrides Drupal\views\Plugin\views\HandlerBase::init().
   *
   * Make sure that no result area handlers are set to be shown when the result
   * is empty.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (isset($this->handler_type) && ($this->handler_type == 'empty')) {
      $this->options['empty'] = TRUE;
    }
  }

  /**
   * Get this area's label.
   */
  public function label() {
    if (!isset($this->options['label'])) {
      return $this->adminLabel();
    }
    return $this->options['label'];
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $this->definition['field'] = !empty($this->definition['field']) ? $this->definition['field'] : '';
    $label = !empty($this->definition['label']) ? $this->definition['label'] : $this->definition['field'];
    $options['label'] = array('default' => $label, 'translatable' => TRUE);
    $options['empty'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide extra data to the administration form
   */
  public function adminSummary() {
    return $this->label();
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => isset($this->options['label']) ? $this->options['label'] : '',
      '#description' => t('The label for this area that will be displayed only administratively.'),
    );

    if ($form_state['type'] != 'empty') {
      $form['empty'] = array(
        '#type' => 'checkbox',
        '#title' => t('Display even if view has no result'),
        '#default_value' => isset($this->options['empty']) ? $this->options['empty'] : 0,
      );
    }
  }

  /**
   * Form helper function to add tokenization form elements.
   */
  public function tokenForm(&$form, &$form_state) {
    $form['tokenize'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use replacement tokens from the first row'),
      '#default_value' => $this->options['tokenize'],
    );

    // Get a list of the available fields and arguments for token replacement.
    $options = array();
    foreach ($this->view->display_handler->getHandlers('field') as $field => $handler) {
      $options[t('Fields')]["[$field]"] = $handler->adminLabel();
    }

    $count = 0; // This lets us prepare the key as we want it printed.
    foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
      $options[t('Arguments')]['%' . ++$count] = t('@argument title', array('@argument' => $handler->adminLabel()));
      $options[t('Arguments')]['!' . $count] = t('@argument input', array('@argument' => $handler->adminLabel()));
    }

    if (!empty($options)) {
      $form['tokens'] = array(
        '#type' => 'details',
        '#title' => t('Replacement patterns'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#id' => 'edit-options-token-help',
        '#states' => array(
          'visible' => array(
            ':input[name="options[tokenize]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['tokens']['help'] = array(
        '#markup' => '<p>' . t('The following tokens are available. If you would like to have the characters \'[\' and \']\' please use the html entity codes \'%5B\' or  \'%5D\' or they will get replaced with empty space.') . '</p>',
      );
      foreach (array_keys($options) as $type) {
        if (!empty($options[$type])) {
          $items = array();
          foreach ($options[$type] as $key => $value) {
            $items[] = $key . ' == ' . $value;
          }
          $form['tokens']['tokens'] = array(
            '#theme' => 'item_list',
            '#items' => $items,
          );
        }
      }
    }

    $this->globalTokenForm($form, $form_state);
  }

  /**
   * Don't run a query
   */
  public function query() { }

  /**
   * Performs any operations needed before full rendering.
   *
   * @param array $results
   *  The results of the view.
   */
  public function preRender(array $results) {
  }

  /**
   * Render the area
   */
  function render($empty = FALSE) {
    return '';
  }

  /**
   * Area handlers shouldn't have groupby.
   */
  public function usesGroupBy() {
    return FALSE;
  }

}

/**
 * @}
 */
