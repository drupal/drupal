<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Normalizer\Value\HttpExceptionNormalizerValue;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Normalizes an HttpException in compliance with the JSON:API specification.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see http://jsonapi.org/format/#error-objects
 */
class HttpExceptionNormalizer extends NormalizerBase {

  /**
   * The current user making the request.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * HttpExceptionNormalizer constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($object);

    $cacheability->addCacheTags(['config:system.logging']);
    if (\Drupal::config('system.logging')->get('error_level') === ERROR_REPORTING_DISPLAY_VERBOSE) {
      $cacheability->setCacheMaxAge(0);
    }

    return new HttpExceptionNormalizerValue($cacheability, static::rasterizeValueRecursive($this->buildErrorObjects($object)));
  }

  /**
   * Builds the normalized JSON:API error objects for the response.
   *
   * @param \Symfony\Component\HttpKernel\Exception\HttpException $exception
   *   The Exception.
   *
   * @return array
   *   The error objects to include in the response.
   */
  protected function buildErrorObjects(HttpException $exception) {
    $error = [];
    $status_code = $exception->getStatusCode();
    if (!empty(Response::$statusTexts[$status_code])) {
      $error['title'] = Response::$statusTexts[$status_code];
    }
    $error += [
      'status' => (string) $status_code,
      'detail' => $exception->getMessage(),
    ];
    $error['links']['via']['href'] = \Drupal::request()->getUri();
    // Provide an "info" link by default: if the exception carries a single
    // "Link" header, use that, otherwise fall back to the HTTP spec section
    // covering the exception's status code.
    $headers = $exception->getHeaders();
    if (isset($headers['Link']) && !is_array($headers['Link'])) {
      $error['links']['info']['href'] = $headers['Link'];
    }
    elseif ($info_url = $this->getInfoUrl($status_code)) {
      $error['links']['info']['href'] = $info_url;
    }
    // Exceptions thrown without an explicitly defined code get assigned zero by
    // default. Since this is no helpful information, omit it.
    if ($exception->getCode() !== 0) {
      $error['code'] = (string) $exception->getCode();
    }

    $is_verbose_reporting = \Drupal::config('system.logging')->get('error_level') === ERROR_REPORTING_DISPLAY_VERBOSE;
    $site_report_access = $this->currentUser->hasPermission('access site reports');
    if ($site_report_access && $is_verbose_reporting) {
      // The following information may contain sensitive information. Only show
      // it to authorized users.
      $error['source'] = [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
      ];
      $error['meta'] = [
        'exception' => (string) $exception,
        'trace' => $exception->getTrace(),
      ];
    }

    return [$error];
  }

  /**
   * Return a string to the common problem type.
   *
   * @return string|null
   *   URL pointing to the specific RFC-2616 section. Or NULL if it is an HTTP
   *   status code that is defined in another RFC.
   *
   * @see https://www.drupal.org/project/drupal/issues/2832211#comment-11826234
   *
   * @internal
   */
  public static function getInfoUrl($status_code) {
    // Depending on the error code we'll return a different URL.
    $url = 'https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html';
    $sections = [
      '100' => '#sec10.1.1',
      '101' => '#sec10.1.2',
      '200' => '#sec10.2.1',
      '201' => '#sec10.2.2',
      '202' => '#sec10.2.3',
      '203' => '#sec10.2.4',
      '204' => '#sec10.2.5',
      '205' => '#sec10.2.6',
      '206' => '#sec10.2.7',
      '300' => '#sec10.3.1',
      '301' => '#sec10.3.2',
      '302' => '#sec10.3.3',
      '303' => '#sec10.3.4',
      '304' => '#sec10.3.5',
      '305' => '#sec10.3.6',
      '307' => '#sec10.3.8',
      '400' => '#sec10.4.1',
      '401' => '#sec10.4.2',
      '402' => '#sec10.4.3',
      '403' => '#sec10.4.4',
      '404' => '#sec10.4.5',
      '405' => '#sec10.4.6',
      '406' => '#sec10.4.7',
      '407' => '#sec10.4.8',
      '408' => '#sec10.4.9',
      '409' => '#sec10.4.10',
      '410' => '#sec10.4.11',
      '411' => '#sec10.4.12',
      '412' => '#sec10.4.13',
      '413' => '#sec10.4.14',
      '414' => '#sec10.4.15',
      '415' => '#sec10.4.16',
      '416' => '#sec10.4.17',
      '417' => '#sec10.4.18',
      '500' => '#sec10.5.1',
      '501' => '#sec10.5.2',
      '502' => '#sec10.5.3',
      '503' => '#sec10.5.4',
      '504' => '#sec10.5.5',
      '505' => '#sec10.5.6',
    ];
    return empty($sections[$status_code]) ? NULL : $url . $sections[$status_code];
  }

  /**
   * {@inheritdoc}
   */
  public function hasCacheableSupportsMethod(): bool {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use getSupportedTypes() instead. See https://www.drupal.org/node/3359695', E_USER_DEPRECATED);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      HttpException::class => TRUE,
    ];
  }

}
