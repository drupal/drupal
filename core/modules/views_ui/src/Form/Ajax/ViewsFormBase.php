<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\ViewsFormBase.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views_ui\ViewUI;
use Drupal\views\ViewEntityInterface;
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
  public function getFormState(ViewEntityInterface $view, $display_id, $js) {
    // $js may already have been converted to a Boolean.
    $ajax = is_string($js) ? $js === 'ajax' : $js;
    return (new FormState())
      ->set('form_id', $this->getFormId())
      ->set('form_key', $this->getFormKey())
      ->set('ajax', $ajax)
      ->set('display_id', $display_id)
      ->set('view', $view)
      ->set('type', $this->type)
      ->set('id', $this->id)
      ->disableRedirect()
      ->addBuildInfo('callback_object', $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(ViewEntityInterface $view, $display_id, $js) {
    $form_state = $this->getFormState($view, $display_id, $js);
    $view = $form_state->get('view');
    $key = $form_state->get('form_key');

    // @todo Remove the need for this.
    \Drupal::moduleHandler()->loadInclude('views_ui', 'inc', 'admin');

    // Reset the cache of IDs. Drupal rather aggressively prevents ID
    // duplication but this causes it to remember IDs that are no longer even
    // being used.
    Html::resetSeenIds();

    // check to see if this is the top form of the stack. If it is, pop
    // it off; if it isn't, the user clicked somewhere else and the stack is
    // now irrelevant.
    if (!empty($view->stack)) {
      $identifier = implode('-', array_filter([$key, $view->id(), $display_id, $form_state->get('type'), $form_state->get('id')]));
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
    $form_class = get_class($form_state->getFormObject());
    $response = $this->ajaxFormWrapper($form_class, $form_state);

    // If the form has not been submitted, or was not set for rerendering, stop.
    if (!$form_state->isSubmitted() || $form_state->get('rerender')) {
      return $response;
    }

    // Sometimes we need to re-generate the form for multi-step type operations.
    if (!empty($view->stack)) {
      $drupal_add_js = $drupal_add_js_original;
      $stack = $view->stack;
      $top = array_shift($stack);

      // Build the new form state for the next form in the stack.
      $reflection = new \ReflectionClass($view::$forms[$top[1]]);
      /** @var $form_state \Drupal\Core\Form\FormStateInterface */
      $form_state = $reflection->newInstanceArgs(array_slice($top, 3, 2))->getFormState($view, $top[2], $form_state->get('ajax'));
      $form_class = get_class($form_state->getFormObject());

      $form_state->setUserInput(array());
      $form_url = views_ui_build_form_url($form_state);
      if (!$form_state->get('ajax')) {
        return new RedirectResponse($form_url->setAbsolute()->toString());
      }
      $form_state->set('url', $form_url);
      $response = $this->ajaxFormWrapper($form_class, $form_state);
    }
    elseif (!$form_state->get('ajax')) {
      // if nothing on the stack, non-js forms just go back to the main view editor.
      $display_id = $form_state->get('display_id');
      return new RedirectResponse($this->url('entity.view.edit_display_form', ['view' => $view->id(), 'display_id' => $display_id], ['absolute' => TRUE]));
    }
    else {
      $response = new AjaxResponse();
      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new Ajax\ShowButtonsCommand(!empty($view->changed)));
      $response->addCommand(new Ajax\TriggerPreviewCommand());
      if ($page_title = $form_state->get('page_title')) {
        $response->addCommand(new Ajax\ReplaceTitleCommand($page_title));
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
   * Wrapper for handling AJAX forms.
   *
   * Wrapper around \Drupal\Core\Form\FormBuilderInterface::buildForm() to
   * handle some AJAX stuff automatically.
   * This makes some assumptions about the client.
   *
   * @param \Drupal\Core\Form\FormInterface|string $form_class
   *   The value must be one of the following:
   *   - The name of a class that implements \Drupal\Core\Form\FormInterface.
   *   - An instance of a class that implements \Drupal\Core\Form\FormInterface.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|string|array
   *   Returns one of three possible values:
   *   - A \Drupal\Core\Ajax\AjaxResponse object.
   *   - The rendered form, as a string.
   *   - A render array with the title in #title and the rendered form in the
   *   #markup array.
   */
  protected function ajaxFormWrapper($form_class, FormStateInterface &$form_state) {
    // This won't override settings already in.
    if (!$form_state->has('rerender')) {
      $form_state->set('rerender', FALSE);
    }
    $ajax = $form_state->get('ajax');
    // Do not overwrite if the redirect has been disabled.
    if (!$form_state->isRedirectDisabled()) {
      $form_state->disableRedirect($ajax);
    }
    $form_state->disableCache();

    $form = \Drupal::formBuilder()->buildForm($form_class, $form_state);
    $output = drupal_render($form);
    drupal_process_attached($form);

    // These forms have the title built in, so set the title here:
    $title = $form_state->get('title') ?: '';

    if ($ajax && (!$form_state->isExecuted() || $form_state->get('rerender'))) {
      // If the form didn't execute and we're using ajax, build up an
      // Ajax command list to execute.
      $response = new AjaxResponse();

      $display = '';
      $status_messages = array('#theme' => 'status_messages');
      if ($messages = drupal_render($status_messages)) {
        $display = '<div class="views-messages">' . $messages . '</div>';
      }
      $display .= $output;

      $options = array(
        'dialogClass' => 'views-ui-dialog',
        'width' => '50%',
      );

      $response->addCommand(new OpenModalDialogCommand($title, $display, $options));

      if ($section = $form_state->get('#section')) {
        $response->addCommand(new Ajax\HighlightCommand('.' . Html::cleanCssIdentifier($section)));
      }

      return $response;
    }

    return $title ? ['#title' => $title, '#markup' => $output] : $output;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
