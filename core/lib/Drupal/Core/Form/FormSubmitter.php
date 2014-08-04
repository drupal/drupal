<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormSubmitter.
 */

namespace Drupal\Core\Form;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\UrlGeneratorInterface;

/**
 * Provides submission processing for forms.
 */
class FormSubmitter implements FormSubmitterInterface {

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new FormValidator.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   */
  public function __construct(RequestStack $request_stack, UrlGeneratorInterface $url_generator) {
    $this->requestStack = $request_stack;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function doSubmitForm(&$form, FormStateInterface &$form_state) {
    if (!$form_state['submitted']) {
      return;
    }

    // Execute form submit handlers.
    $this->executeSubmitHandlers($form, $form_state);

    // If batches were set in the submit handlers, we process them now,
    // possibly ending execution. We make sure we do not react to the batch
    // that is already being processed (if a batch operation performs a
    // \Drupal\Core\Form\FormBuilderInterface::submitForm).
    if ($batch = &$this->batchGet() && !isset($batch['current_set'])) {
      // Store $form_state information in the batch definition.
      $batch['form_state'] = $form_state;

      $batch['progressive'] = !$form_state['programmed'];
      $response = batch_process();
      if ($batch['progressive']) {
        return $response;
      }

      // Execution continues only for programmatic forms.
      // For 'regular' forms, we get redirected to the batch processing
      // page. Form redirection will be handled in _batch_finished(),
      // after the batch is processed.
    }

    // Set a flag to indicate the the form has been processed and executed.
    $form_state['executed'] = TRUE;

    // If no response has been set, process the form redirect.
    if (!$form_state->has('response') && $redirect = $this->redirectForm($form_state)) {
      $form_state->setResponse($redirect);
    }

    // If there is a response was set, return it instead of continuing.
    if (($response = $form_state->get('response')) && $response instanceof Response) {
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function executeSubmitHandlers(&$form, FormStateInterface &$form_state) {
    // If there was a button pressed, use its handlers.
    if (!empty($form_state['submit_handlers'])) {
      $handlers = $form_state['submit_handlers'];
    }
    // Otherwise, check for a form-level handler.
    elseif (!empty($form['#submit'])) {
      $handlers = $form['#submit'];
    }
    else {
      $handlers = array();
    }

    foreach ($handlers as $function) {
      // Check if a previous _submit handler has set a batch, but make sure we
      // do not react to a batch that is already being processed (for instance
      // if a batch operation performs a
      //  \Drupal\Core\Form\FormBuilderInterface::submitForm()).
      if (($batch = &$this->batchGet()) && !isset($batch['id'])) {
        // Some previous submit handler has set a batch. To ensure correct
        // execution order, store the call in a special 'control' batch set.
        // See _batch_next_set().
        $batch['sets'][] = array('form_submit' => $function);
        $batch['has_form_submits'] = TRUE;
      }
      else {
        call_user_func_array($function, array(&$form, &$form_state));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function redirectForm(FormStateInterface $form_state) {
    // According to RFC 7231, 303 See Other status code must be used to redirect
    // user agent (and not default 302 Found).
    // @see http://tools.ietf.org/html/rfc7231#section-6.4.4
    $status_code = Response::HTTP_SEE_OTHER;
    $redirect = $form_state->getRedirect();

    // Allow using redirect responses directly if needed.
    if ($redirect instanceof RedirectResponse) {
      return $redirect;
    }

    $url = NULL;
    // Check for a route-based redirection.
    if ($redirect instanceof Url) {
      $url = $redirect->toString();
    }
    // An array contains the path to use for the redirect, as well as options to
    // use for generating the URL.
    elseif (is_array($redirect)) {
      if (isset($redirect[1])) {
        $options = $redirect[1];
      }
      else {
        $options = array();
      }
      // Redirections should always use absolute URLs.
      $options['absolute'] = TRUE;
      if (isset($redirect[2])) {
        $status_code = $redirect[2];
      }
      $url = $this->urlGenerator->generateFromPath($redirect[0], $options);
    }
    // A string represents the path to use for the redirect.
    elseif (is_string($redirect)) {
      // This function can be called from the installer, which guarantees
      // that $redirect will always be a string, so catch that case here
      // and use the appropriate redirect function.
      if ($this->drupalInstallationAttempted()) {
        install_goto($redirect);
      }
      else {
        $url = $this->urlGenerator->generateFromPath($redirect, array('absolute' => TRUE));
      }
    }
    // If no redirect was specified, redirect to the current path.
    elseif ($redirect === NULL) {
      $request = $this->requestStack->getCurrentRequest();
      // @todo Remove dependency on the internal _system_path attribute:
      //   https://www.drupal.org/node/2293521.
      $url = $this->urlGenerator->generateFromPath($request->attributes->get('_system_path'), array(
        'query' => $request->query->all(),
        'absolute' => TRUE,
      ));
    }

    if ($url) {
      return new RedirectResponse($url, $status_code);
    }
  }

  /**
   * Wraps drupal_installation_attempted().
   *
   * @return bool
   */
  protected function drupalInstallationAttempted() {
    return drupal_installation_attempted();
  }

  /**
   * Wraps batch_get().
   */
  protected function &batchGet() {
    return batch_get();
  }

}
