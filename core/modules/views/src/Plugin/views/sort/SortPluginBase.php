<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\sort\SortPluginBase.
 */

namespace Drupal\views\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\CacheablePluginInterface;
use Drupal\views\Plugin\views\HandlerBase;

/**
 * @defgroup views_sort_handlers Views sort handler plugins
 * @{
 * Plugins that handle sorting for Views.
 *
 * Sort handlers extend \Drupal\views\Plugin\views\sort:SortPluginBase. They
 * must be annotated with \Drupal\views\Annotation\ViewsSort annotation, and
 * they must be in plugin directory Plugin\views\sort.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * Base sort handler that has no options and performs a simple sort.
 */
abstract class SortPluginBase extends HandlerBase implements CacheablePluginInterface {

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
    $options['exposed'] = array('default' => FALSE);
    $options['expose'] = array(
      'contains' => array(
        'label' => array('default' => ''),
      ),
    );
    return $options;
  }

  /**
   * Display whether or not the sort order is ascending or descending
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('Exposed');
    }
    switch ($this->options['order']) {
      case 'ASC':
      case 'asc':
      default:
        return $this->t('asc');
        break;
      case 'DESC';
      case 'desc';
        return $this->t('desc');
        break;
    }
  }

  /**
   * Basic options for all sort criteria
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
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
  public function showExposeButton(&$form, FormStateInterface $form_state) {
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
      '#title' => $this->t('Expose this sort to visitors, to allow them to change it'),
      '#type' => 'checkbox',
    );

    // Then add the button itself.
    if (empty($this->options['exposed'])) {
      $form['expose_button']['markup'] = array(
        '#markup' => '<div class="description exposed-description" style="float: left; margin-right:10px">' . $this->t('This sort is not exposed. Expose it to allow the users to change it.') . '</div>',
      );
      $form['expose_button']['button'] = array(
        '#limit_validation_errors' => array(),
        '#type' => 'submit',
        '#value' => $this->t('Expose sort'),
        '#submit' => array(array($this, 'displayExposedForm')),
        '#attributes' => array('class' => array('use-ajax-submit')),
      );
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 0;
    }
    else {
      $form['expose_button']['markup'] = array(
        '#markup' => '<div class="description exposed-description">' . $this->t('This sort is exposed. If you hide it, users will not be able to change it.') . '</div>',
      );
      $form['expose_button']['button'] = array(
        '#limit_validation_errors' => array(),
        '#type' => 'submit',
        '#value' => $this->t('Hide sort'),
        '#submit' => array(array($this, 'displayExposedForm')),
        '#attributes' => array('class' => array('use-ajax-submit')),
      );
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 1;
    }
  }

  /**
   * Simple validate handler
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $this->sortValidate($form, $form_state);
    if (!empty($this->options['exposed'])) {
      $this->validateExposeForm($form, $form_state);
    }

  }

  /**
   * Simple submit handler
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Do not store this values.
    $form_state->unsetValue('expose_button');

    $this->sortSubmit($form, $form_state);
    if (!empty($this->options['exposed'])) {
      $this->submitExposeForm($form, $form_state);
    }
  }

  /**
   * Shortcut to display the value form.
   */
  protected function showSortForm(&$form, FormStateInterface $form_state) {
    $options = $this->sortOptions();
    if (!empty($options)) {
      $form['order'] = array(
        '#title' => $this->t('Order'),
        '#type' => 'radios',
        '#options' => $options,
        '#default_value' => $this->options['order'],
      );
    }
  }

  protected function sortValidate(&$form, FormStateInterface $form_state) { }

  public function sortSubmit(&$form, FormStateInterface $form_state) { }

  /**
   * Provide a list of options for the default sort form.
   * Should be overridden by classes that don't override sort_form
   */
  protected function sortOptions() {
    return array(
      'ASC' => $this->t('Sort ascending'),
      'DESC' => $this->t('Sort descending'),
    );
  }

  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    // #flatten will move everything from $form['expose'][$key] to $form[$key]
    // prior to rendering. That's why the preRender for it needs to run first,
    // so that when the next preRender (the one for fieldsets) runs, it gets
    // the flattened data.
    array_unshift($form['#pre_render'], array(get_class($this), 'preRenderFlattenData'));
    $form['expose']['#flatten'] = TRUE;

    $form['expose']['label'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['label'],
      '#title' => $this->t('Label'),
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
      'label' => $this->definition['title'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    // The result of a sort does not depend on outside information, so by
    // default it is cacheable.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = [];
    // Exposed sorts use GET parameters, so it depends on the current URL.
    if ($this->isExposed()) {
      $cache_contexts[] = 'url.query_args:sort_by';
    }
    return $cache_contexts;
  }

}

/**
 * @}
 */
