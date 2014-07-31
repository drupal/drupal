<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormSubmitterInterface.
 */

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
   * Usually (for exceptions, see below) $form_state['redirect'] determines
   * where to redirect the user. This can be set either to a string (the path to
   * redirect to), or an array of arguments for url(). If
   * $form_state['redirect'] is missing, the user is usually (again, see below
   * for exceptions) redirected back to the page they came from, where they
   * should see a fresh, unpopulated copy of the form.
   *
   * Here is an example of how to set up a form to redirect to the path 'node':
   * @code
   * $form_state->set('redirect', 'node');
   * @endcode
   * And here is an example of how to redirect to 'node/123?foo=bar#baz':
   * @code
   * $form_state->set('redirect', array(
   *   'node/123',
   *   array(
   *     'query' => array(
   *       'foo' => 'bar',
   *     ),
   *     'fragment' => 'baz',
   *   ),
   * ));
   * @endcode
   *
   * There are several exceptions to the "usual" behavior described above:
   * - If $form_state['programmed'] is TRUE, the form submission was usually
   *   invoked via self::submitForm(), so any redirection would break the script
   *   that invoked self::submitForm() and no redirection is done.
   * - If $form_state['rebuild'] is TRUE, the form is being rebuilt, and no
   *   redirection is done.
   * - If $form_state['no_redirect'] is TRUE, redirection is disabled. This is
   *   set, for instance, by \Drupal\system\FormAjaxController::getForm() to
   *   prevent redirection in Ajax callbacks. $form_state['no_redirect'] should
   *   never be set or altered by form builder functions or form validation
   *   or submit handlers.
   * - If $form_state['redirect'] is set to FALSE, redirection is disabled.
   * - If none of the above conditions has prevented redirection, then the
   *   redirect is accomplished by returning a RedirectResponse, passing in the
   *   value of $form_state['redirect'] if it is set, or the current path if it
   *   is not. RedirectResponse preferentially uses the value of
   *   \Drupal::request->query->get('destination') (the 'destination' URL query
   *   string) if it is present, so this will override any values set by
   *   $form_state['redirect'].
   *
   * @param $form_state
   *   The current state of the form.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *
   * @see \Drupal\Core\Form\FormBuilderInterface::processForm()
   * @see \Drupal\Core\Form\FormBuilderInterface::buildForm()
   */
  public function redirectForm(FormStateInterface $form_state);

}
