<?php

namespace Drupal\Core\Http\Exception;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A cacheable NotFoundHttpException.
 */
class CacheableNotFoundHttpException extends NotFoundHttpException implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheableDependencyInterface $cacheability, $message = '', \Exception $previous = NULL, $code = 0) {
    $this->setCacheability($cacheability);
    parent::__construct($message, $previous, $code);
  }

}
