<?php

namespace Drupal\Core\Http\Exception;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * A cacheable PreconditionFailedHttpException.
 */
class CacheablePreconditionFailedHttpException extends PreconditionFailedHttpException implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheableDependencyInterface $cacheability, $message = NULL, \Exception $previous = NULL, $code = 0) {
    $this->setCacheability($cacheability);
    parent::__construct($message, $previous, $code);
  }

}
