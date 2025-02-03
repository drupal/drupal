<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Http;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\Core\Http\Exception\CacheableConflictHttpException;
use Drupal\Core\Http\Exception\CacheableGoneHttpException;
use Drupal\Core\Http\Exception\CacheableHttpException;
use Drupal\Core\Http\Exception\CacheableLengthRequiredHttpException;
use Drupal\Core\Http\Exception\CacheableMethodNotAllowedHttpException;
use Drupal\Core\Http\Exception\CacheableNotAcceptableHttpException;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\Core\Http\Exception\CacheablePreconditionFailedHttpException;
use Drupal\Core\Http\Exception\CacheablePreconditionRequiredHttpException;
use Drupal\Core\Http\Exception\CacheableServiceUnavailableHttpException;
use Drupal\Core\Http\Exception\CacheableTooManyRequestsHttpException;
use Drupal\Core\Http\Exception\CacheableUnauthorizedHttpException;
use Drupal\Core\Http\Exception\CacheableUnprocessableEntityHttpException;
use Drupal\Core\Http\Exception\CacheableUnsupportedMediaTypeHttpException;
use Drupal\Tests\UnitTestCase;

/**
 * @group Http
 */
class CacheableExceptionTest extends UnitTestCase {

  /**
   * @covers \Drupal\Core\Http\Exception\CacheableHttpException
   */
  public function testCacheableHttpException(): void {
    $exception = new CacheableHttpException((new CacheableMetadata())->setCacheContexts(['route']), 500, 'test message', NULL, ['X-Drupal-Exception' => 'Test'], 123);
    $this->assertSame(['route'], $exception->getCacheContexts());
    $this->assertSame(500, $exception->getStatusCode());
    $this->assertSame('test message', $exception->getMessage());
    $this->assertSame(['X-Drupal-Exception' => 'Test'], $exception->getHeaders());
    $this->assertSame(123, $exception->getCode());
  }

  /**
   * @dataProvider providerTestExceptions
   */
  public function testExceptions($status_code, $class, $argument = NULL, $expected_headers = []): void {
    $cacheable_metadata = (new CacheableMetadata())->setCacheContexts(['route']);
    $message = "$class test message";
    $previous = new class('Error of PHP 7+') extends \Error {};
    if ($argument) {
      $exception = new $class($cacheable_metadata, $argument, $message, $previous, 123);
    }
    else {
      $exception = new $class($cacheable_metadata, $message, $previous, 123);
    }
    $this->assertSame(['route'], $exception->getCacheContexts());
    $this->assertSame($message, $exception->getMessage());
    $this->assertSame($status_code, $exception->getStatusCode());
    $this->assertSame($expected_headers, $exception->getHeaders());
    $this->assertSame($previous, $exception->getPrevious());
    $this->assertSame(123, $exception->getCode());
  }

  public static function providerTestExceptions() {
    return [
      [400, CacheableBadRequestHttpException::class],
      [401, CacheableUnauthorizedHttpException::class, 'test challenge', ['WWW-Authenticate' => 'test challenge']],
      [403, CacheableAccessDeniedHttpException::class],
      [404, CacheableNotFoundHttpException::class],
      [405, CacheableMethodNotAllowedHttpException::class, ['POST', 'PUT'], ['Allow' => 'POST, PUT']],
      [406, CacheableNotAcceptableHttpException::class],
      [409, CacheableConflictHttpException::class],
      [410, CacheableGoneHttpException::class],
      [411, CacheableLengthRequiredHttpException::class],
      [412, CacheablePreconditionFailedHttpException::class],
      [415, CacheableUnsupportedMediaTypeHttpException::class],
      [422, CacheableUnprocessableEntityHttpException::class],
      [428, CacheablePreconditionRequiredHttpException::class],
      [429, CacheableTooManyRequestsHttpException::class, 60, ['Retry-After' => 60]],
      [503, CacheableServiceUnavailableHttpException::class, 60, ['Retry-After' => 60]],
    ];
  }

}
