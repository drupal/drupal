<?php

namespace Drupal\Core\Form;

/**
 * Provides an interface for processing form submissions.
 */
interface FormSubmitterInterface {

  /**
   * Handles the submitted form, executing callbacks and processing responses.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return null|\Symfony\Component\HttpFoundation\Response
   *   If a response was set by a submit handler, or if the form needs to
   *   redirect, a Response object will be returned.
   */
  public function doSubmitForm(&$form, FormStateInterface &$form_state);

  /**
   * Executes custom submission handlers for a given form.
   *
   * Button-specific handlers are checked first. If none exist, the function
   * falls back to form-level handlers.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form. If the user submitted the form by clicking
   *   a button with custom handler functions defined, those handlers will be
   *   stored here.
   */
  public function executeSubmitHandlers(&$form, FormStateInterface &$form_state);

  /**
   * Redirects the user to a URL after a form has been processed.
   *
   * After a form is submitted and processed, normally the user should be
   * redirected to a new destination page. This function figures out what that
   * destination should be, based on the $form_state and the 'destination'
   * query string in the request URL, and redirects the user there.
   *
   * The result of \Drupal\Core\Form|FormStateInterface::getRedirect()
   * determines where to redirect the user. See the possible return values
   * listed there. If the result is FALSE, then the user will not be redirected.
   *
   * Here is an example of how to set up a form to redirect to the path 'user':
   * @code
   * $form_state->setRedirect('user.page');
   * @endcode
   * And here is an example of how to redirect to 'node/123?foo=bar#baz':
   * @code
   * $form_state->setRedirect('entity.node.canonical',
   *   ['node' => 123],
   *   [
   *     'query' => [
   *       'foo' => 'bar',
   *     ],
   *     'fragment' => 'baz',
   *   ],
   * );
   * @endcode
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *
   * @see \Drupal\Core\Form\FormBuilderInterface::processForm()
   * @see \Drupal\Core\Form\FormBuilderInterface::buildForm()
   */
  public function redirectForm(FormStateInterface $form_state);

}
