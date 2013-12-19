<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\sort\SortPluginBase.
 */

namespace Drupal\views\Plugin\views\sort;

use Drupal\views\Plugin\views\HandlerBase;

/**
 * @defgroup views_sort_handlers Views sort handlers
 * @{
 * Handlers to tell Views how to sort queries.
 */

/**
 * Base sort handler that has no options and performs a simple sort.
 *
 * @ingroup views_sort_handlers
 */
abstract class SortPluginBase extends HandlerBase {

  /**
   * Determine if a sort can be exposed.
   */
  public function canExpose() { return TRUE; }

  /**
   * Called to add the sort to a query.
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $this->query->addOrderBy($this->tableAlias, $this->realField, $this->options['order']);
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['order'] = array('default' => 'ASC');
    $options['exposed'] = array('default' => FALSE, 'bool' => TRUE);
    $options['expose'] = array(
      'contains' => array(
        'label' => array('default' => '', 'translatable' => TRUE),
      ),
    );
    return $options;
  }

  /**
   * Display whether or not the sort order is ascending or descending
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return t('Exposed');
    }
    switch ($this->options['order']) {
      case 'ASC':
      case 'asc':
      default:
        return t('asc');
        break;
      case 'DESC';
      case 'desc';
        return t('desc');
        break;
    }
  }

  /**
   * Basic options for all sort criteria
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($this->canExpose()) {
      $this->showExposeButton($form, $form_state);
    }
    $form['op_val_start'] = array('#value' => '<div class="clearfix">');
    $this->showSortForm($form, $form_state);
    $form['op_val_end'] = array('#value' => '</div>');
    if ($this->canExpose()) {
      $this->showExposeForm($form, $form_state);
    }
  }

  /**
   * Shortcut to display the expose/hide button.
   */
  public function showExposeButton(&$form, &$form_state) {
    $form['expose_button'] = array(
      '#prefix' => '<div class="views-expose clearfix">',
      '#suffix' => '</div>',
      // Should always come first
      '#weight' => -1000,
    );

    // Add a checkbox for JS users, which will have behavior attached to it
    // so it can replace the button.
    $form['expose_button']['checkbox'] = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('js-only')),
    );
    $form['expose_button']['checkbox']['checkbox'] = array(
      '#title' => t('Expose this sort to visitors, to allow them to change it'),
      '#type' => 'checkbox',
    );

    // Then add the button itself.
    if (empty($this->options['exposed'])) {
      $form['expose_button']['markup'] = array(
        '#markup' => '<div class="description exposed-description" style="float: left; margin-right:10px">' . t('This sort is not exposed. Expose it to allow the users to change it.') . '</div>',
      );
      $form['expose_button']['button'] = array(
        '#limit_validation_errors' => array(),
        '#type' => 'submit',
        '#value' => t('Expose sort'),
        '#submit' => array(array($this, 'displayExposedForm')),
      );
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 0;
    }
    else {
      $form['expose_button']['markup'] = array(
        '#markup' => '<div class="description exposed-description">' . t('This sort is exposed. If you hide it, users will not be able to change it.') . '</div>',
      );
      $form['expose_button']['button'] = array(
        '#limit_validation_errors' => array(),
        '#type' => 'submit',
        '#value' => t('Hide sort'),
        '#submit' => array(array($this, 'displayExposedForm')),
      );
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 1;
    }
  }

  /**
   * Simple validate handler
   */
  public function validateOptionsForm(&$form, &$form_state) {
    $this->sortValidate($form, $form_state);
    if (!empty($this->options['exposed'])) {
      $this->validateExposeForm($form, $form_state);
    }

  }

  /**
   * Simple submit handler
   */
  public function submitOptionsForm(&$form, &$form_state) {
    unset($form_state['values']['expose_button']); // don't store this.
    $this->sortSubmit($form, $form_state);
    if (!empty($this->options['exposed'])) {
      $this->submitExposeForm($form, $form_state);
    }
  }

  /**
   * Shortcut to display the value form.
   */
  protected function showSortForm(&$form, &$form_state) {
    $options = $this->sortOptions();
    if (!empty($options)) {
      $form['order'] = array(
        '#title' => t('Order'),
        '#type' => 'radios',
        '#options' => $options,
        '#default_value' => $this->options['order'],
      );
    }
  }

  protected function sortValidate(&$form, &$form_state) { }

  public function sortSubmit(&$form, &$form_state) { }

  /**
   * Provide a list of options for the default sort form.
   * Should be overridden by classes that don't override sort_form
   */
  protected function sortOptions() {
    return array(
      'ASC' => t('Sort ascending'),
      'DESC' => t('Sort descending'),
    );
  }

  public function buildExposeForm(&$form, &$form_state) {
    // #flatten will move everything from $form['expose'][$key] to $form[$key]
    // prior to rendering. That's why the preRender for it needs to run first,
    // so that when the next preRender (the one for fieldsets) runs, it gets
    // the flattened data.
    array_unshift($form['#pre_render'], array(get_class($this), 'preRenderFlattenData'));
    $form['expose']['#flatten'] = TRUE;

    $form['expose']['label'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['label'],
      '#title' => t('Label'),
      '#required' => TRUE,
      '#size' => 40,
      '#weight' => -1,
   );
  }

  /**
   * Provide default options for exposed sorts.
   */
  public function defaultExposeOptions() {
    $this->options['expose'] = array(
      'order' => $this->options['order'],
      'label' => $this->definition['title'],
    );
  }

}

/**
 * @}
 */
