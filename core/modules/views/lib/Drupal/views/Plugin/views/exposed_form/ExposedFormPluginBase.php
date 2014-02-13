<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase.
 */

namespace Drupal\views\Plugin\views\exposed_form;

use Drupal\Component\Utility\String;
use Drupal\views\Form\ViewsExposedForm;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_exposed_form_plugins Views exposed form plugins
 * @{
 * Plugins that handle the validation/submission and rendering of exposed forms.
 *
 * If needed, it is possible to use them to add additional form elements.
 */

/**
 * The base plugin to handle exposed filter forms.
 */
abstract class ExposedFormPluginBase extends PluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['submit_button'] = array('default' => 'Apply', 'translatable' => TRUE);
    $options['reset_button'] = array('default' => FALSE, 'bool' => TRUE);
    $options['reset_button_label'] = array('default' => 'Reset', 'translatable' => TRUE);
    $options['exposed_sorts_label'] = array('default' => 'Sort by', 'translatable' => TRUE);
    $options['expose_sort_order'] = array('default' => TRUE, 'bool' => TRUE);
    $options['sort_asc_label'] = array('default' => 'Asc', 'translatable' => TRUE);
    $options['sort_desc_label'] = array('default' => 'Desc', 'translatable' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['submit_button'] = array(
      '#type' => 'textfield',
      '#title' => t('Submit button text'),
      '#default_value' => $this->options['submit_button'],
      '#required' => TRUE,
    );

    $form['reset_button'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include reset button (resets all applied exposed filters).'),
      '#default_value' => $this->options['reset_button'],
    );

    $form['reset_button_label'] = array(
     '#type' => 'textfield',
      '#title' => t('Reset button label'),
      '#description' => t('Text to display in the reset button of the exposed form.'),
      '#default_value' => $this->options['reset_button_label'],
      '#required' => TRUE,
      '#states' => array(
        'invisible' => array(
          'input[name="exposed_form_options[reset_button]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['exposed_sorts_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Exposed sorts label'),
      '#default_value' => $this->options['exposed_sorts_label'],
      '#required' => TRUE,
    );

    $form['expose_sort_order'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow people to choose the sort order'),
      '#description' => t('If sort order is not exposed, the sort criteria settings for each sort will determine its order.'),
      '#default_value' => $this->options['expose_sort_order'],
    );

    $form['sort_asc_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label for ascending sort'),
      '#default_value' => $this->options['sort_asc_label'],
      '#required' => TRUE,
      '#states' => array(
        'visible' => array(
          'input[name="exposed_form_options[expose_sort_order]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['sort_desc_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label for descending sort'),
      '#default_value' => $this->options['sort_desc_label'],
      '#required' => TRUE,
      '#states' => array(
        'visible' => array(
          'input[name="exposed_form_options[expose_sort_order]"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  /**
   * Render the exposed filter form.
   *
   * This actually does more than that; because it's using FAPI, the form will
   * also assign data to the appropriate handlers for use in building the
   * query.
   */
  public function renderExposedForm($block = FALSE) {
    // Deal with any exposed filters we may have, before building.
    $form_state = array(
      'view' => &$this->view,
      'display' => &$this->view->display_handler->display,
      'method' => 'get',
      'rerender' => TRUE,
      'no_redirect' => TRUE,
      'always_process' => TRUE,
    );

    // Some types of displays (eg. attachments) may wish to use the exposed
    // filters of their parent displays instead of showing an additional
    // exposed filter form for the attachment as well as that for the parent.
    if (!$this->view->display_handler->displaysExposed() || (!$block && $this->view->display_handler->getOption('exposed_block'))) {
      unset($form_state['rerender']);
    }

    if (!empty($this->ajax)) {
      $form_state['ajax'] = TRUE;
    }

    $form_state['exposed_form_plugin'] = $this;
    $form = \Drupal::formBuilder()->buildForm('\Drupal\views\Form\ViewsExposedForm', $form_state);

    if (!$this->view->display_handler->displaysExposed() || (!$block && $this->view->display_handler->getOption('exposed_block'))) {
      return array();
    }
    else {
      return $form;
    }
  }

  public function query() {
    $view = $this->view;
    $exposed_data = isset($view->exposed_data) ? $view->exposed_data : array();
    $sort_by = isset($exposed_data['sort_by']) ? $exposed_data['sort_by'] : NULL;
    if (!empty($sort_by)) {
      // Make sure the original order of sorts is preserved
      // (e.g. a sticky sort is often first)
      if (isset($view->sort[$sort_by])) {
        $view->query->orderby = array();
        foreach ($view->sort as $key => $sort) {
          if (!$sort->isExposed()) {
            $sort->query();
          }
          elseif ($key == $sort_by) {
            if (isset($exposed_data['sort_order']) && in_array($exposed_data['sort_order'], array('ASC', 'DESC'))) {
              $sort->options['order'] = $exposed_data['sort_order'];
            }
            $sort->setRelationship();
            $sort->query();
          }
        }
      }
    }
  }

  public function preRender($values) { }

  public function postRender(&$output) { }

  public function preExecute() { }

  public function postExecute() { }

  /**
   * Alters the view exposed form.
   *
   * @param $form
   *   The form build array. Passed by reference.
   * @param $form_state
   *   The form state. Passed by reference.
   */
  public function exposedFormAlter(&$form, &$form_state) {
    if (!empty($this->options['submit_button'])) {
      $form['actions']['submit']['#value'] = $this->options['submit_button'];
    }

    // Check if there is exposed sorts for this view
    $exposed_sorts = array();
    foreach ($this->view->sort as $id => $handler) {
      if ($handler->canExpose() && $handler->isExposed()) {
        $exposed_sorts[$id] = String::checkPlain($handler->options['expose']['label']);
      }
    }

    if (count($exposed_sorts)) {
      $form['sort_by'] = array(
        '#type' => 'select',
        '#options' => $exposed_sorts,
        '#title' => $this->options['exposed_sorts_label'],
      );
      $sort_order = array(
        'ASC' => $this->options['sort_asc_label'],
        'DESC' => $this->options['sort_desc_label'],
      );
      if (isset($form_state['input']['sort_by']) && isset($this->view->sort[$form_state['input']['sort_by']])) {
        $default_sort_order = $this->view->sort[$form_state['input']['sort_by']]->options['order'];
      }
      else {
        $first_sort = reset($this->view->sort);
        $default_sort_order = $first_sort->options['order'];
      }

      if (!isset($form_state['input']['sort_by'])) {
        $keys = array_keys($exposed_sorts);
        $form_state['input']['sort_by'] = array_shift($keys);
      }

      if ($this->options['expose_sort_order']) {
        $form['sort_order'] = array(
          '#type' => 'select',
          '#options' => $sort_order,
          '#title' => t('Order', array(), array('context' => 'Sort order')),
          '#default_value' => $default_sort_order,
        );
      }
      $form['submit']['#weight'] = 10;
    }

    if (!empty($this->options['reset_button'])) {
      $form['actions']['reset'] = array(
        '#value' => $this->options['reset_button_label'],
        '#type' => 'submit',
        '#weight' => 10,
      );

      // Get an array of exposed filters, keyed by identifier option.
      foreach ($this->view->filter as $id => $handler) {
        if ($handler->canExpose() && $handler->isExposed() && !empty($handler->options['expose']['identifier'])) {
          $exposed_filters[$handler->options['expose']['identifier']] = $id;
        }
      }
      $all_exposed = array_merge($exposed_sorts, $exposed_filters);

      // Set the access to FALSE if there is no exposed input.
      if (!array_intersect_key($all_exposed, $this->view->exposed_input)) {
        $form['actions']['reset']['#access'] = FALSE;
      }
    }

    $pager = $this->view->display_handler->getPlugin('pager');
    if ($pager) {
      $pager->exposedFormAlter($form, $form_state);
      $form_state['pager_plugin'] = $pager;
    }
  }

  public function exposedFormValidate(&$form, &$form_state) {
    if (isset($form_state['pager_plugin'])) {
      $form_state['pager_plugin']->exposedFormValidate($form, $form_state);
    }
  }

  /**
  * This function is executed when exposed form is submited.
  *
  * @param $form
  *   Nested array of form elements that comprise the form.
  * @param $form_state
  *   A keyed array containing the current state of the form.
  * @param $exclude
  *   Nested array of keys to exclude of insert into
  *   $view->exposed_raw_input
  */
  public function exposedFormSubmit(&$form, &$form_state, &$exclude) {
    if (!empty($form_state['values']['op']) && $form_state['values']['op'] == $this->options['reset_button_label']) {
      $this->resetForm($form, $form_state);
    }
    if (isset($form_state['pager_plugin'])) {
      $form_state['pager_plugin']->exposedFormSubmit($form, $form_state, $exclude);
      $exclude[] = 'pager_plugin';
    }
  }

  public function resetForm(&$form, &$form_state) {
    // _SESSION is not defined for users who are not logged in.

    // If filters are not overridden, store the 'remember' settings on the
    // default display. If they are, store them on this display. This way,
    // multiple displays in the same view can share the same filters and
    // remember settings.
    $display_id = ($this->view->display_handler->isDefaulted('filters')) ? 'default' : $this->view->current_display;

    if (isset($_SESSION['views'][$this->view->storage->id()][$display_id])) {
      unset($_SESSION['views'][$this->view->storage->id()][$display_id]);
    }

    // Set the form to allow redirect.
    if (empty($this->view->live_preview)) {
      $form_state['no_redirect'] = FALSE;
    }
    else {
      $form_state['rebuild'] = TRUE;
      $this->view->exposed_data = array();
    }

    $form_state['redirect'] = current_path();
    $form_state['values'] = array();
  }

}

/**
 * @}
 */
