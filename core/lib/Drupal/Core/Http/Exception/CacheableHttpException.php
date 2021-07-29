<?php

namespace Drupal\Core\Http\Exception;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A cacheable HttpException.
 */
class CacheableHttpException extends HttpException implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheableDependencyInterface $cacheability, $statusCode = 0, $message = NULL, \Exception $previous = NULL, $code = 0) {
    $this->setCacheability($cacheability);
    // @todo Remove condition in https://www.drupal.org/node/3002352
    if (is_array($code)) {
      parent::__construct($statusCode, $message, $previous, $code);
    }
    else {
      parent::__construct($statusCode, $message, $previous, [], $code);
    }
  }

}
