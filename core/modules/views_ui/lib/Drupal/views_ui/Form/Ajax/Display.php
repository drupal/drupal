<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\Display.
 */

namespace Drupal\views_ui\Form\Ajax;

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
   * Implements \Drupal\views_ui\Form\Ajax\ViewsFormInterface::getFormKey().
   */
  public function getFormKey() {
    return 'display';
  }

  /**
   * Overrides \Drupal\views_ui\Form\Ajax\ViewsFormBase::getFormState().
   *
   * @todo Remove this and switch all usage of $form_state['section'] to
   *   $form_state['type'].
   */
  public function getFormState(ViewStorageInterface $view, $display_id, $js) {
    return array(
      'section' => $this->type,
    ) + parent::getFormState($view, $display_id, $js);
  }

  /**
   * Overrides \Drupal\views_ui\Form\Ajax\ViewsFormBase::getForm().
   */
  public function getForm(ViewStorageInterface $view, $display_id, $js, $type = NULL) {
    $this->setType($type);
    return parent::getForm($view, $display_id, $js);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'views_ui_edit_display_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $view = &$form_state['view'];
    $display_id = $form_state['display_id'];
    $section = $form_state['section'];

    $executable = $view->get('executable');
    $executable->setDisplay($display_id);
    $display = &$executable->display[$display_id];

    // Get form from the handler.
    $form['options'] = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
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
   * Overrides \Drupal\views_ui\Form\Ajax\ViewsFormBase::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    $form_state['view']->get('executable')->displayHandlers->get($form_state['display_id'])->validateOptionsForm($form['options'], $form_state);

    if (form_get_errors()) {
      $form_state['rerender'] = TRUE;
    }
  }

  /**
   * Overrides \Drupal\views_ui\Form\Ajax\ViewsFormBase::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['view']->get('executable')->displayHandlers->get($form_state['display_id'])->submitOptionsForm($form['options'], $form_state);

    $form_state['view']->cacheSet();
  }

}
