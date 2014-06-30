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
  public function doSubmitForm(&$form, &$form_state) {
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
      // We need the full $form_state when either:
      // - Some submit handlers were saved to be called during batch
      //   processing. See self::executeSubmitHandlers().
      // - The form is multistep.
      // In other cases, we only need the information expected by
      // self::redirectForm().
      if ($batch['has_form_submits'] || !empty($form_state['rebuild'])) {
        $batch['form_state'] = $form_state;
      }
      else {
        $batch['form_state'] = array_intersect_key($form_state, array_flip(array('programmed', 'rebuild', 'storage', 'no_redirect', 'redirect', 'redirect_route')));
      }

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
    if (!isset($form_state['response']) && $redirect = $this->redirectForm($form_state)) {
      $form_state['response'] = $redirect;
    }

    // If there is a response was set, return it instead of continuing.
    if (isset($form_state['response']) && $form_state['response'] instanceof Response) {
      return $form_state['response'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function executeSubmitHandlers(&$form, &$form_state) {
    // If there was a button pressed, use its handlers.
    if (isset($form_state['submit_handlers'])) {
      $handlers = $form_state['submit_handlers'];
    }
    // Otherwise, check for a form-level handler.
    elseif (isset($form['#submit'])) {
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
  public function redirectForm($form_state) {
    // Skip redirection for form submissions invoked via
    // \Drupal\Core\Form\FormBuilderInterface::submitForm().
    if (!empty($form_state['programmed'])) {
      return;
    }
    // Skip redirection if rebuild is activated.
    if (!empty($form_state['rebuild'])) {
      return;
    }
    // Skip redirection if it was explicitly disallowed.
    if (!empty($form_state['no_redirect'])) {
      return;
    }

    // Allow using redirect responses directly if needed.
    if (isset($form_state['redirect']) && $form_state['redirect'] instanceof RedirectResponse) {
      return $form_state['redirect'];
    }

    // Check for a route-based redirection.
    if (isset($form_state['redirect_route'])) {
      // @todo Remove once all redirects are converted to Url.
      if (!($form_state['redirect_route'] instanceof Url)) {
        $form_state['redirect_route'] += array(
          'route_parameters' => array(),
          'options' => array(),
        );
        $form_state['redirect_route'] = new Url($form_state['redirect_route']['route_name'], $form_state['redirect_route']['route_parameters'], $form_state['redirect_route']['options']);
      }

      $form_state['redirect_route']->setAbsolute();
      return new RedirectResponse($form_state['redirect_route']->toString());
    }

    // Only invoke a redirection if redirect value was not set to FALSE.
    if (!isset($form_state['redirect']) || $form_state['redirect'] !== FALSE) {
      if (isset($form_state['redirect'])) {
        if (is_array($form_state['redirect'])) {
          if (isset($form_state['redirect'][1])) {
            $options = $form_state['redirect'][1];
          }
          else {
            $options = array();
          }
          // Redirections should always use absolute URLs.
          $options['absolute'] = TRUE;
          if (isset($form_state['redirect'][2])) {
            $status_code = $form_state['redirect'][2];
          }
          else {
            $status_code = 302;
          }
          return new RedirectResponse($this->urlGenerator->generateFromPath($form_state['redirect'][0], $options), $status_code);
        }
        else {
          // This function can be called from the installer, which guarantees
          // that $redirect will always be a string, so catch that case here
          // and use the appropriate redirect function.
          if ($this->drupalInstallationAttempted()) {
            install_goto($form_state['redirect']);
          }
          else {
            return new RedirectResponse($this->urlGenerator->generateFromPath($form_state['redirect'], array('absolute' => TRUE)));
          }
        }
      }
      $request = $this->requestStack->getCurrentRequest();
      // @todo Remove dependency on the internal _system_path attribute:
      //   https://www.drupal.org/node/2293521.
      $url = $this->urlGenerator->generateFromPath($request->attributes->get('_system_path'), array(
        'query' => $request->query->all(),
        'absolute' => TRUE,
      ));
      return new RedirectResponse($url);
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
