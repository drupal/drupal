<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Last-chance handler for exceptions: the final exception subscriber.
 *
 * This handler will catch any exceptions not caught elsewhere and report
 * them as an error page.
 *
 * Each format has its own way of handling exceptions:
 * - html: exception.default_html, exception.custom_page_html and
 *   exception.fast_404_html
 * - json: exception.default_json
 *
 * And when the serialization module is installed, all serialization formats are
 * handled by a single exception subscriber:: serialization.exception.default.
 *
 * This exception subscriber runs after all the above (it has a lower priority),
 * which makes it the last-chance exception handler. It always sends a plain
 * text response. If it's a displayable error and the error level is configured
 * to be verbose, then a helpful backtrace is also printed.
 */
class FinalExceptionSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * @var string
   *
   * One of the error level constants defined in bootstrap.inc.
   */
  protected $errorLevel;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new FinalExceptionSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the configured error level.
   *
   * @return string
   */
  protected function getErrorLevel() {
    if (!isset($this->errorLevel)) {
      $this->errorLevel = $this->configFactory->get('system.logging')->get('error_level');
    }
    return $this->errorLevel;
  }

  /**
   * Handles exceptions for this subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $error = Error::decodeException($exception);

    // Display the message if the current error reporting level allows this type
    // of message to be displayed, and unconditionally in update.php.
    $message = '';
    if ($this->isErrorDisplayable($error)) {
      // If error type is 'User notice' then treat it as debug information
      // instead of an error message.
      // @see debug()
      if ($error['%type'] == 'User notice') {
        $error['%type'] = 'Debug';
      }

      $error = $this->simplifyFileInError($error);

      unset($error['backtrace']);

      if (!$this->isErrorLevelVerbose()) {
        // Without verbose logging, use a simple message.

        // We use \Drupal\Component\Render\FormattableMarkup directly here,
        // rather than use t() since we are in the middle of error handling, and
        // we don't want t() to cause further errors.
        $message = new FormattableMarkup('%type: @message in %function (line %line of %file).', $error);
      }
      else {
        // With verbose logging, we will also include a backtrace.

        $backtrace_exception = $exception;
        while ($backtrace_exception->getPrevious()) {
          $backtrace_exception = $backtrace_exception->getPrevious();
        }
        $backtrace = $backtrace_exception->getTrace();
        // First trace is the error itself, already contained in the message.
        // While the second trace is the error source and also contained in the
        // message, the message doesn't contain argument values, so we output it
        // once more in the backtrace.
        array_shift($backtrace);

        // Generate a backtrace containing only scalar argument values.
        $error['@backtrace'] = Error::formatBacktrace($backtrace);
        $message = new FormattableMarkup('%type: @message in %function (line %line of %file). <pre class="backtrace">@backtrace</pre>', $error);
      }
    }

    $content = $this->t('The website encountered an unexpected error. Please try again later.');
    $content .= $message ? '</br></br>' . $message : '';
    $response = new Response($content, 500, ['Content-Type' => 'text/plain']);

    if ($exception instanceof HttpExceptionInterface) {
      $response->setStatusCode($exception->getStatusCode());
      $response->headers->add($exception->getHeaders());
    }
    else {
      $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR, '500 Service unavailable (with message)');
    }

    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run as the final (very late) KernelEvents::EXCEPTION subscriber.
    $events[KernelEvents::EXCEPTION][] = ['onException', -256];
    return $events;
  }

  /**
   * Checks whether the error level is verbose or not.
   *
   * @return bool
   */
  protected function isErrorLevelVerbose() {
    return $this->getErrorLevel() === ERROR_REPORTING_DISPLAY_VERBOSE;
  }

  /**
   * Wrapper for error_displayable().
   *
   * @param $error
   *   Optional error to examine for ERROR_REPORTING_DISPLAY_SOME.
   *
   * @return bool
   *
   * @see \error_displayable
   */
  protected function isErrorDisplayable($error) {
    return error_displayable($error);
  }

  /**
   * Attempts to reduce error verbosity in the error message's file path.
   *
   * Attempts to reduce verbosity by removing DRUPAL_ROOT from the file path in
   * the message. This does not happen for (false) security.
   *
   * @param $error
   *   Optional error to examine for ERROR_REPORTING_DISPLAY_SOME.
   *
   * @return
   *   The updated $error.
   */
  protected function simplifyFileInError($error) {
    // Attempt to reduce verbosity by removing DRUPAL_ROOT from the file path
    // in the message. This does not happen for (false) security.
    $root_length = strlen(DRUPAL_ROOT);
    if (substr($error['%file'], 0, $root_length) == DRUPAL_ROOT) {
      $error['%file'] = substr($error['%file'], $root_length + 1);
    }
    return $error;
  }

}
