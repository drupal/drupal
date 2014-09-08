<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\Display.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewStorageInterface;

/**
 * Provides a form for editing the Views display.
 */
class Display extends ViewsFormBase {

  /**
   * Constucts a new Display object.
   */
  public function __construct($type = NULL) {
    $this->setType($type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'display';
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove this and switch all usage of $form_state['section'] to
   *   $form_state['type'].
   */
  public function getFormState(ViewStorageInterface $view, $display_id, $js) {
    $form_state = parent::getFormState($view, $display_id, $js);
    $form_state['section'] = $this->type;
    return $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(ViewStorageInterface $view, $display_id, $js, $type = NULL) {
    $this->setType($type);
    return parent::getForm($view, $display_id, $js);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_edit_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = $form_state['view'];
    $display_id = $form_state['display_id'];

    $executable = $view->getExecutable();
    $executable->setDisplay($display_id);

    // Get form from the handler.
    $form['options'] = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll'), 'data-drupal-views-scroll' => TRUE),
    );
    $executable->display_handler->buildOptionsForm($form['options'], $form_state);

    // The handler options form sets $form['#title'], which we need on the entire
    // $form instead of just the ['options'] section.
    $form['#title'] = $form['options']['#title'];
    unset($form['options']['#title']);

    // Move the override dropdown out of the scrollable section of the form.
    if (isset($form['options']['override'])) {
      $form['override'] = $form['options']['override'];
      unset($form['options']['override']);
    }

    $name = NULL;
    if (isset($form_state['update_name'])) {
      $name = $form_state['update_name'];
    }

    $view->getStandardButtons($form, $form_state, 'views_ui_edit_display_form', $name);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state['view']->getExecutable()->displayHandlers->get($form_state['display_id'])->validateOptionsForm($form['options'], $form_state);

    if ($form_state->getErrors()) {
      $form_state['rerender'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state['view']->getExecutable()->displayHandlers->get($form_state['display_id'])->submitOptionsForm($form['options'], $form_state);

    $form_state['view']->cacheSet();
  }

}
