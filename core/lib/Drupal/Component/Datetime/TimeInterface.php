<?php

namespace Drupal\Component\Datetime;

/**
 * Defines an interface for obtaining system time.
 */
interface TimeInterface {

  /**
   * Returns the timestamp for the current request.
   *
   * This method should be used to obtain the current system time at the start
   * of the request. It will be the same value for the life of the request
   * (even for long execution times).
   *
   * If the request is not available it will fallback to the current system
   * time.
   *
   * This method can replace instances of
   * @code
   * $request_time = $_SERVER['REQUEST_TIME'];
   * $request_time = $requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
   * $request_time = $request->server->get('REQUEST_TIME');
   * @endcode
   * and most instances of
   * @code
   * $time = time();
   * @endcode
   * with
   * @code
   * $request_time = \Drupal::time()->getRequestTime();
   * @endcode
   * or the equivalent using the injected service.
   *
   * Using the time service, rather than other methods, is especially important
   * when creating tests, which require predictable timestamps.
   *
   * @return int
   *   A Unix timestamp.
   *
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestMicroTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentMicroTime()
   */
  public function getRequestTime();

  /**
   * Returns the timestamp for the current request with microsecond precision.
   *
   * This method should be used to obtain the current system time, with
   * microsecond precision, at the start of the request. It will be the same
   * value for the life of the request (even for long execution times).
   *
   * If the request is not available it will fallback to the current system
   * time with microsecond precision.
   *
   * This method can replace instances of
   * @code
   * $request_time_float = $_SERVER['REQUEST_TIME_FLOAT'];
   * $request_time_float = $requestStack->getCurrentRequest()->server->get('REQUEST_TIME_FLOAT');
   * $request_time_float = $request->server->get('REQUEST_TIME_FLOAT');
   * @endcode
   * and many instances of
   * @code
   * $microtime = microtime();
   * $microtime = microtime(TRUE);
   * @endcode
   * with
   * @code
   * $request_time = \Drupal::time()->getRequestMicroTime();
   * @endcode
   * or the equivalent using the injected service.
   *
   * Using the time service, rather than other methods, is especially important
   * when creating tests, which require predictable timestamps.
   *
   * @return float
   *   A Unix timestamp with a fractional portion.
   *
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentMicroTime()
   */
  public function getRequestMicroTime();

  /**
   * Returns the current system time as an integer.
   *
   * This method should be used to obtain the current system time, at the time
   * the method was called.
   *
   * This method can replace many instances of
   * @code
   * $time = time();
   * @endcode
   * with
   * @code
   * $request_time = \Drupal::time()->getCurrentTime();
   * @endcode
   * or the equivalent using the injected service.
   *
   * This method should only be used when the current system time is actually
   * needed, such as with timers or time interval calculations. If only the
   * time at the start of the request is needed,
   * use TimeInterface::getRequestTime().
   *
   * Using the time service, rather than other methods, is especially important
   * when creating tests, which require predictable timestamps.
   *
   * @return int
   *   A Unix timestamp.
   *
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestMicroTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentMicroTime()
   */
  public function getCurrentTime();

  /**
   * Returns the current system time with microsecond precision.
   *
   * This method should be used to obtain the current system time, with
   * microsecond precision, at the time the method was called.
   *
   * This method can replace many instances of
   * @code
   * $microtime = microtime();
   * $microtime = microtime(TRUE);
   * @endcode
   * with
   * @code
   * $request_time = \Drupal::time()->getCurrentMicroTime();
   * @endcode
   * or the equivalent using the injected service.
   *
   * This method should only be used when the current system time is actually
   * needed, such as with timers or time interval calculations. If only the
   * time at the start of the request and microsecond precision is needed,
   * use TimeInterface::getRequestMicroTime().
   *
   * Using the time service, rather than other methods, is especially important
   * when creating tests, which require predictable timestamps.
   *
   * @return float
   *   A Unix timestamp with a fractional portion.
   *
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestMicroTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentTime()
   */
  public function getCurrentMicroTime();

}
