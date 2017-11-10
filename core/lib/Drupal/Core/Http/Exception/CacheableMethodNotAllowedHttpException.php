<?php

namespace Drupal\Core\Http\Exception;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * A cacheable MethodNotAllowedHttpException.
 */
class CacheableMethodNotAllowedHttpException extends MethodNotAllowedHttpException implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheableDependencyInterface $cacheability, array $allow, $message = NULL, \Exception $previous = NULL, $code = 0) {
    $this->setCacheability($cacheability);
    parent::__construct($allow, $message, $previous, $code);
  }

}
