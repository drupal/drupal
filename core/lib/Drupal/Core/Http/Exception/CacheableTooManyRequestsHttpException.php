<?php

namespace Drupal\Core\Http\Exception;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * A cacheable TooManyRequestsHttpException.
 */
class CacheableTooManyRequestsHttpException extends TooManyRequestsHttpException implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheableDependencyInterface $cacheability, $retryAfter = NULL, $message = '', ?\Exception $previous = NULL, $code = 0) {
    $this->setCacheability($cacheability);
    parent::__construct($retryAfter, $message, $previous, $code);
  }

}
