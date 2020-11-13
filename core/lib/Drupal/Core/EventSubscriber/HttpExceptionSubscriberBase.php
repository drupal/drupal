<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Utility base class for exception subscribers.
 *
 * A subscriber may extend this class and implement getHandledFormats() to
 * indicate which request formats it will respond to. Then implement an on*()
 * method for any error code (HTTP response code) that should be handled. For
 * example, to handle a specific error code like 404 Not Found messages add the
 * method:
 *
 * @code
 * public function on404(ExceptionEvent $event) {}
 * @endcode
 *
 * To implement a fallback for the entire 4xx class of codes, implement the
 * method:
 *
 * @code
 * public function on4xx(ExceptionEvent $event) {}
 * @endcode
 *
 * That method should then call $event->setResponse() to set the response object
 * for the exception. Alternatively, it may opt not to do so and then other
 * listeners will have the opportunity to handle the exception.
 *
 * Note: Core provides several important exception listeners by default. In most
 * cases, setting the priority of a contrib listener to the default of 0 will
 * do what you expect and handle the exceptions you'd expect it to handle.
 * If a custom priority is set, be aware of the following core-registered
 * listeners.
 *
 * - Fast404ExceptionHtmlSubscriber: 200. This subscriber will return a
 *     minimalist, high-performance 404 page for HTML requests. It is not
 *     recommended to have a priority higher than this one as it will only slow
 *     down that use case.
 * - ExceptionLoggingSubscriber: 50.  This subscriber logs all exceptions but
 *     does not handle them. Do not register a listener with a higher priority
 *     unless you want exceptions to not get logged, which makes debugging more
 *     difficult.
 * - DefaultExceptionSubscriber: -256. The subscriber of last resort, this will
 *     provide generic handling for any exception. A listener with a lower
 *     priority will never get called.
 *
 * All other core-provided exception handlers have negative priorities so most
 * module-provided listeners will naturally take precedence over them.
 */
abstract class HttpExceptionSubscriberBase implements EventSubscriberInterface {

  /**
   * Specifies the request formats this subscriber will respond to.
   *
   * @return array
   *   An indexed array of the format machine names that this subscriber will
   *   attempt to process, such as "html" or "json". Returning an empty array
   *   will apply to all formats.
   *
   * @see \Symfony\Component\HttpFoundation\Request
   */
  abstract protected function getHandledFormats();

  /**
   * Specifies the priority of all listeners in this class.
   *
   * The default priority is 1, which is very low. To have listeners that have
   * a "first attempt" at handling exceptions return a higher priority.
   *
   * @return int
   *   The event priority of this subscriber.
   */
  protected static function getPriority() {
    return 0;
  }

  /**
   * Handles errors for this subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();

    // Make the exception available for example when rendering a block.
    $request = $event->getRequest();
    $request->attributes->set('exception', $exception);

    $handled_formats = $this->getHandledFormats();

    $format = $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT, $request->getRequestFormat());

    if ($exception instanceof HttpExceptionInterface && (empty($handled_formats) || in_array($format, $handled_formats))) {
      $method = 'on' . $exception->getStatusCode();
      // Keep just the leading number of the status code to produce either a
      // 400 or a 500 method callback.
      $method_fallback = 'on' . substr($exception->getStatusCode(), 0, 1) . 'xx';
      // We want to allow the method to be called and still not set a response
      // if it has additional filtering logic to determine when it will apply.
      // It is therefore the method's responsibility to set the response on the
      // event if appropriate.
      if (method_exists($this, $method)) {
        $this->$method($event);
      }
      elseif (method_exists($this, $method_fallback)) {
        $this->$method_fallback($event);
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', static::getPriority()];
    return $events;
  }

}
