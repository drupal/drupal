<?php

namespace Drupal\Core\Form\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\Exception\BrokenPostRequestException;
use Drupal\Core\Form\FormAjaxException;
use Drupal\Core\Form\FormAjaxResponseBuilderInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Wraps AJAX form submissions that are triggered via an exception.
 */
class FormAjaxSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The form AJAX response builder.
   *
   * @var \Drupal\Core\Form\FormAjaxResponseBuilderInterface
   */
  protected $formAjaxResponseBuilder;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new FormAjaxSubscriber.
   *
   * @param \Drupal\Core\Form\FormAjaxResponseBuilderInterface $form_ajax_response_builder
   *   The form AJAX response builder.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(FormAjaxResponseBuilderInterface $form_ajax_response_builder, TranslationInterface $string_translation, MessengerInterface $messenger) {
    $this->formAjaxResponseBuilder = $form_ajax_response_builder;
    $this->stringTranslation = $string_translation;
    $this->messenger = $messenger;
  }

  /**
   * Alters the wrapper format if this is an AJAX form request.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The event to process.
   */
  public function onView(ViewEvent $event) {
    // To support an AJAX form submission of a form within a block, make the
    // later VIEW subscribers process the controller result as though for
    // HTML display (i.e., add blocks). During that block building, when the
    // submitted form gets processed, an exception gets thrown by
    // \Drupal\Core\Form\FormBuilderInterface::buildForm(), allowing
    // self::onException() to return an AJAX response instead of an HTML one.
    $request = $event->getRequest();
    if ($request->query->has(FormBuilderInterface::AJAX_FORM_REQUEST)) {
      $request->query->set(MainContentViewSubscriber::WRAPPER_FORMAT, 'html');
    }
  }

  /**
   * Catches a form AJAX exception and build a response from it.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    $request = $event->getRequest();

    // Render a nice error message in case we have a file upload which exceeds
    // the configured upload limit.
    if ($exception instanceof BrokenPostRequestException && $request->query->has(FormBuilderInterface::AJAX_FORM_REQUEST)) {
      $this->messenger->addError($this->t('An unrecoverable error occurred. The uploaded file likely exceeded the maximum file size (@size) that this server supports.', ['@size' => $this->formatSize($exception->getSize())]));
      $response = new AjaxResponse(NULL, 200);
      $status_messages = ['#type' => 'status_messages'];
      $response->addCommand(new PrependCommand(NULL, $status_messages));
      $event->allowCustomResponseCode();
      $event->setResponse($response);
      return;
    }

    // Extract the form AJAX exception (it may have been passed to another
    // exception before reaching here).
    if ($exception = $this->getFormAjaxException($exception)) {
      $request = $event->getRequest();
      $form = $exception->getForm();
      $form_state = $exception->getFormState();

      // Set the build ID from the request as the old build ID on the form.
      $form['#build_id_old'] = $request->request->get('form_build_id');

      try {
        $response = $this->formAjaxResponseBuilder->buildResponse($request, $form, $form_state, []);

        // Since this response is being set in place of an exception, explicitly
        // mark this as a 200 status.
        $response->setStatusCode(200);
        $event->allowCustomResponseCode();
        $event->setResponse($response);
      }
      catch (\Exception $e) {
        // Otherwise, replace the existing exception with the new one.
        $event->setThrowable($e);
      }
    }
  }

  /**
   * Extracts a form AJAX exception.
   *
   * @param \Exception $e
   *   A generic exception that might contain a form AJAX exception.
   *
   * @return \Drupal\Core\Form\FormAjaxException|null
   *   Either the form AJAX exception, or NULL if none could be found.
   */
  protected function getFormAjaxException(\Exception $e) {
    $exception = NULL;
    while ($e) {
      if ($e instanceof FormAjaxException) {
        $exception = $e;
        break;
      }

      $e = $e->getPrevious();
    }
    return $exception;
  }

  /**
   * Wraps format_size()
   *
   * @return string
   *   The formatted size.
   */
  protected function formatSize($size) {
    return format_size($size);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before exception.logger.
    $events[KernelEvents::EXCEPTION] = ['onException', 51];
    // Run before main_content_view_subscriber.
    $events[KernelEvents::VIEW][] = ['onView', 1];

    return $events;
  }

}
