<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ExceptionListener.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener as ExceptionListenerBase;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Extends the symfony exception listener to support POST subrequests.
 */
class ExceptionListener extends ExceptionListenerBase {

  /**
   * {@inheritdoc}
   *
   * In contrast to the symfony base class, do not override POST requests to GET
   * requests.
   */
  protected function duplicateRequest(\Exception $exception, Request $request) {
    $attributes = array(
      '_controller' => $this->controller,
      'exception' => FlattenException::create($exception),
      'logger' => $this->logger instanceof DebugLoggerInterface ? $this->logger : NULL,
      'format' => $request->getRequestFormat(),
    );
    return $request->duplicate(NULL, NULL, $attributes);
  }

}
