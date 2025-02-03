<?php

namespace Drupal\Core\Http\Exception;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * A cacheable ServiceUnavailableHttpException.
 */
class CacheableServiceUnavailableHttpException extends ServiceUnavailableHttpException implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheableDependencyInterface $cacheability, $retryAfter = NULL, $message = '', ?\Throwable $previous = NULL, $code = 0) {
    $this->setCacheability($cacheability);
    parent::__construct($retryAfter, $message, $previous, $code);
  }

}
