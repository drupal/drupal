<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\exposed_form\InputRequired.
 */

namespace Drupal\views\Plugin\views\exposed_form;

use Drupal\views\Views;

/**
 * Exposed form plugin that provides an exposed form with required input.
 *
 * @ingroup views_exposed_form_plugins
 *
 * @ViewsExposedForm(
 *   id = "input_required",
 *   title = @Translation("Input required"),
 *   help = @Translation("An exposed form that only renders a view if the form contains user input.")
 * )
 */
class InputRequired extends ExposedFormPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['text_input_required'] = array('default' => 'Select any filter and click on Apply to see results', 'translatable' => TRUE);
    $options['text_input_required_format'] = array('default' => NULL);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['text_input_required'] = array(
      '#type' => 'text_format',
      '#title' => t('Text on demand'),
      '#description' => t('Text to display instead of results until the user selects and applies an exposed filter.'),
      '#default_value' => $this->options['text_input_required'],
      '#format' => isset($this->options['text_input_required_format']) ? $this->options['text_input_required_format'] : filter_default_format(),
      '#editor' => FALSE,
    );
  }

  public function submitOptionsForm(&$form, &$form_state) {
    $form_state['values']['exposed_form_options']['text_input_required_format'] = $form_state['values']['exposed_form_options']['text_input_required']['format'];
    $form_state['values']['exposed_form_options']['text_input_required'] = $form_state['values']['exposed_form_options']['text_input_required']['value'];
    parent::submitOptionsForm($form, $form_state);
  }

  protected function exposedFilterApplied() {
    static $cache = NULL;
    if (!isset($cache)) {
      $view = $this->view;
      if (is_array($view->filter) && count($view->filter)) {
        foreach ($view->filter as $filter) {
          if ($filter->isExposed()) {
            $identifier = $filter->options['expose']['identifier'];
            if (isset($view->exposed_input[$identifier])) {
              $cache = TRUE;
              return $cache;
            }
          }
        }
      }
      $cache = FALSE;
    }

    return $cache;
  }

  public function preRender($values) {
    if (!$this->exposedFilterApplied()) {
      $options = array(
        'id' => 'area',
        'table' => 'views',
        'field' => 'area',
        'label' => '',
        'relationship' => 'none',
        'group_type' => 'group',
        'content' => $this->options['text_input_required'],
        'format' => $this->options['text_input_required_format'],
      );
      $handler = Views::handlerManager('area')->getHandler($options);
      $handler->init($this->view, $options);
      $this->displayHandler->handlers['empty'] = array(
        'area' => $handler,
      );
      $this->displayHandler->setOption('empty', array('text' => $options));
    }
  }

  public function query() {
    if (!$this->exposedFilterApplied()) {
      // We return with no query; this will force the empty text.
      $this->view->built = TRUE;
      $this->view->executed = TRUE;
      $this->view->result = array();
    }
    else {
      parent::query();
    }
  }

}
