<?php

namespace Drupal\Core\Http\Exception;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * A cacheable UnauthorizedHttpException.
 */
class CacheableUnauthorizedHttpException extends UnauthorizedHttpException implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheableDependencyInterface $cacheability, $challenge, $message = '', ?\Exception $previous = NULL, $code = 0) {
    $this->setCacheability($cacheability);
    parent::__construct($challenge, $message, $previous, $code);
  }

}
