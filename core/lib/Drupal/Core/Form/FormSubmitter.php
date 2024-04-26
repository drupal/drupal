<?php

namespace Drupal\Core\Form;

use Drupal\Core\EventSubscriber\RedirectResponseSubscriber;
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
   * Constructs a new FormSubmitter.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The URL generator.
   * @param \Drupal\Core\EventSubscriber\RedirectResponseSubscriber $redirectResponseSubscriber
   *   The redirect response subscriber.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected UrlGeneratorInterface $urlGenerator,
    protected RedirectResponseSubscriber $redirectResponseSubscriber,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function doSubmitForm(&$form, FormStateInterface &$form_state) {
    if (!$form_state->isSubmitted()) {
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

      $batch['progressive'] = !$form_state->isProgrammed();
      $response = batch_process();
      // If the batch has been completed and _batch_finished() called then
      // $batch will be NULL.
      if ($batch && $batch['progressive']) {
        return $response;
      }

      // Execution continues only for programmatic forms.
      // For 'regular' forms, we get redirected to the batch processing
      // page. Form redirection will be handled in _batch_finished(),
      // after the batch is processed.
    }

    // Set a flag to indicate the form has been processed and executed.
    $form_state->setExecuted();

    // If no response has been set, process the form redirect.
    if (!$form_state->getResponse() && $redirect = $this->redirectForm($form_state)) {
      $form_state->setResponse($redirect);
    }

    // If there is a response was set, return it instead of continuing.
    if (($response = $form_state->getResponse()) && $response instanceof Response) {
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function executeSubmitHandlers(&$form, FormStateInterface &$form_state) {
    // If there was a button pressed, use its handlers.
    $handlers = $form_state->getSubmitHandlers();
    // Otherwise, check for a form-level handler.
    if (!$handlers && !empty($form['#submit'])) {
      $handlers = $form['#submit'];
    }

    foreach ($handlers as $callback) {
      // Check if a previous _submit handler has set a batch, but make sure we
      // do not react to a batch that is already being processed (for instance
      // if a batch operation performs a
      // \Drupal\Core\Form\FormBuilderInterface::submitForm()).
      if (($batch = &$this->batchGet()) && !isset($batch['id'])) {
        // Some previous submit handler has set a batch. To ensure correct
        // execution order, store the call in a special 'control' batch set.
        // See _batch_next_set().
        $batch['sets'][] = ['form_submit' => $callback];
        $batch['has_form_submits'] = TRUE;
      }
      else {
        call_user_func_array($form_state->prepareCallback($callback), [&$form, &$form_state]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function redirectForm(FormStateInterface $form_state) {
    $redirect = $form_state->getRedirect();

    $this->redirectResponseSubscriber->setIgnoreDestination($form_state->getIgnoreDestination());

    // Allow using redirect responses directly if needed.
    if ($redirect instanceof RedirectResponse) {
      return $redirect;
    }

    $url = NULL;
    // Check for a route-based redirection.
    if ($redirect instanceof Url) {
      $url = $redirect->setAbsolute()->toString();
    }
    // If no redirect was specified, redirect to the current path.
    elseif ($redirect === NULL) {
      $request = $this->requestStack->getCurrentRequest();
      $url = $this->urlGenerator->generateFromRoute('<current>', [], ['query' => $request->query->all(), 'absolute' => TRUE]);
    }

    if ($url) {
      // According to RFC 7231, 303 See Other status code must be used to redirect
      // user agent (and not default 302 Found).
      // @see http://tools.ietf.org/html/rfc7231#section-6.4.4
      return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
    }
  }

  /**
   * Wraps batch_get().
   */
  protected function &batchGet() {
    return batch_get();
  }

}
