<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\ViewsFormBase.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Core\Form\FormBase;
use Drupal\views\ViewStorageInterface;
use Drupal\views\Ajax;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a base class for Views UI AJAX forms.
 */
abstract class ViewsFormBase extends FormBase implements ViewsFormInterface {

  /**
   * The ID of the item this form is manipulating.
   *
   * @var string
   */
  protected $id;

  /**
   * The type of item this form is manipulating.
   *
   * @var string
   */
  protected $type;

  /**
   * Sets the ID for this form.
   *
   * @param string $id
   *   The ID of the item this form is manipulating.
   */
  protected function setID($id) {
    if ($id) {
      $this->id = $id;
    }
  }

  /**
   * Sets the type for this form.
   *
   * @param string $type
   *   The type of the item this form is manipulating.
   */
  protected function setType($type) {
    if ($type) {
      $this->type = $type;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormState(ViewStorageInterface $view, $display_id, $js) {
    // $js may already have been converted to a Boolean.
    $ajax = is_string($js) ? $js === 'ajax' : $js;
    return array(
      'form_id' => $this->getFormId(),
      'form_key' => $this->getFormKey(),
      'ajax' => $ajax,
      'display_id' => $display_id,
      'view' => $view,
      'type' => $this->type,
      'id' => $this->id,
      'no_redirect' => TRUE,
      'build_info' => array(
        'args' => array(),
        'callback_object' => $this,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(ViewStorageInterface $view, $display_id, $js) {
    $form_state = $this->getFormState($view, $display_id, $js);
    $view = $form_state['view'];
    $key = $form_state['form_key'];

    // @todo Remove the need for this.
    \Drupal::moduleHandler()->loadInclude('views_ui', 'inc', 'admin');
    \Drupal::moduleHandler()->loadInclude('views', 'inc', 'includes/ajax');

    // Reset the cache of IDs. Drupal rather aggressively prevents ID
    // duplication but this causes it to remember IDs that are no longer even
    // being used.
    $seen_ids_init = &drupal_static('drupal_html_id:init');
    $seen_ids_init = array();

    // check to see if this is the top form of the stack. If it is, pop
    // it off; if it isn't, the user clicked somewhere else and the stack is
    // now irrelevant.
    if (!empty($view->stack)) {
      $identifier = implode('-', array_filter(array($key, $view->id(), $display_id, $form_state['type'], $form_state['id'])));
      // Retrieve the first form from the stack without changing the integer keys,
      // as they're being used for the "2 of 3" progress indicator.
      reset($view->stack);
      list($key, $top) = each($view->stack);
      unset($view->stack[$key]);

      if (array_shift($top) != $identifier) {
        $view->stack = array();
      }
    }

    // Automatically remove the form cache if it is set and the key does
    // not match. This way navigating away from the form without hitting
    // update will work.
    if (isset($view->form_cache) && $view->form_cache['key'] != $key) {
      unset($view->form_cache);
    }

    // With the below logic, we may end up rendering a form twice (or two forms
    // each sharing the same element ids), potentially resulting in
    // _drupal_add_js() being called twice to add the same setting. drupal_get_js()
    // is ok with that, but until \Drupal\Core\Ajax\AjaxResponse::ajaxRender()
    // is (http://drupal.org/node/208611), reset the _drupal_add_js() static
    // before rendering the second time.
    $drupal_add_js_original = _drupal_add_js();
    $drupal_add_js = &drupal_static('_drupal_add_js');
    $response = views_ajax_form_wrapper($form_state['form_id'], $form_state);

    // If the form has not been submitted, or was not set for rerendering, stop.
    if (!$form_state['submitted'] || !empty($form_state['rerender'])) {
      return $response;
    }

    // Sometimes we need to re-generate the form for multi-step type operations.
    if (!empty($view->stack)) {
      $drupal_add_js = $drupal_add_js_original;
      $stack = $view->stack;
      $top = array_shift($stack);

      // Build the new form state for the next form in the stack.
      $reflection = new \ReflectionClass($view::$forms[$top[1]]);
      $form_state = $reflection->newInstanceArgs(array_slice($top, 3, 2))->getFormState($view, $top[2], $form_state['ajax']);

      $form_state['input'] = array();
      $form_path = views_ui_build_form_path($form_state);
      if (!$form_state['ajax']) {
        return new RedirectResponse(url($form_path, array('absolute' => TRUE)));
      }
      $form_state['path'] = $form_path;
      $response = views_ajax_form_wrapper($form_state['form_id'], $form_state);
    }
    elseif (!$form_state['ajax']) {
      // if nothing on the stack, non-js forms just go back to the main view editor.
      return new RedirectResponse(url("admin/structure/views/view/{$view->id()}/edit/$form_state[display_id]", array('absolute' => TRUE)));
    }
    else {
      $response = new AjaxResponse();
      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new Ajax\ShowButtonsCommand());
      $response->addCommand(new Ajax\TriggerPreviewCommand());
      if (!empty($form_state['#page_title'])) {
        $response->addCommand(new Ajax\ReplaceTitleCommand($form_state['#page_title']));
      }
    }
    // If this form was for view-wide changes, there's no need to regenerate
    // the display section of the form.
    if ($display_id !== '') {
      \Drupal::entityManager()->getFormObject('view', 'edit')->rebuildCurrentTab($view, $response, $display_id);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

}
