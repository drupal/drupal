<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests;

use WebDriver\Service\CurlService;
use WebDriver\Exception\CurlExec;
use WebDriver\Exception as WebDriverException;

// cspell:ignore curle curlopt customrequest failonerror postfields
// cspell:ignore returntransfer

@trigger_error('The \Drupal\FunctionalJavascriptTests\WebDriverCurlService class is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3462152', E_USER_DEPRECATED);

/**
 * Provides a curl service to interact with Selenium driver.
 *
 * Extends WebDriver\Service\CurlService to solve problem with race conditions,
 * when multiple processes requests.
 *
 * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. There is
 *   no replacement, use the base class instead.
 *
 * @see https://www.drupal.org/node/3462152
 */
class WebDriverCurlService extends CurlService {

  /**
   * Flag that indicates if retries are enabled.
   *
   * @var bool
   */
  private static $retry = TRUE;

  /**
   * Enables retries.
   *
   * This is useful if the caller is implementing it's own waiting process.
   */
  public static function enableRetry() {
    static::$retry = TRUE;
  }

  /**
   * Disables retries.
   *
   * This is useful if the caller is implementing it's own waiting process.
   */
  public static function disableRetry() {
    static::$retry = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($requestMethod, $url, $parameters = NULL, $extraOptions = []) {
    $extraOptions += [
      CURLOPT_FAILONERROR => TRUE,
    ];
    $retries = 0;
    $max_retries = static::$retry ? 10 : 1;
    while ($retries < $max_retries) {
      try {
        $customHeaders = [
          'Content-Type: application/json;charset=UTF-8',
          'Accept: application/json;charset=UTF-8',
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

        switch ($requestMethod) {
          case 'GET':
            break;

          case 'POST':
            if ($parameters && is_array($parameters)) {
              curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
            }
            else {
              $customHeaders[] = 'Content-Length: 0';

              // Suppress "Transfer-Encoding: chunked" header automatically
              // added by cURL that causes a 400 bad request (bad
              // content-length).
              $customHeaders[] = 'Transfer-Encoding:';
            }

            // Suppress "Expect: 100-continue" header automatically added by
            // cURL that causes a 1 second delay if the remote server does not
            // support Expect.
            $customHeaders[] = 'Expect:';

            curl_setopt($curl, CURLOPT_POST, TRUE);
            break;

          case 'DELETE':
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;

          case 'PUT':
            if ($parameters && is_array($parameters)) {
              curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
            }
            else {
              $customHeaders[] = 'Content-Length: 0';

              // Suppress "Transfer-Encoding: chunked" header automatically
              // added by cURL that causes a 400 bad request (bad
              // content-length).
              $customHeaders[] = 'Transfer-Encoding:';
            }

            // Suppress "Expect: 100-continue" header automatically added by
            // cURL that causes a 1 second delay if the remote server does not
            // support Expect.
            $customHeaders[] = 'Expect:';

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            break;
        }

        foreach ($extraOptions as $option => $value) {
          curl_setopt($curl, $option, $value);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $customHeaders);

        $result = curl_exec($curl);
        $rawResult = NULL;
        if ($result !== FALSE) {
          $rawResult = trim($result);
        }

        $info = curl_getinfo($curl);
        $info['request_method'] = $requestMethod;

        if (array_key_exists(CURLOPT_FAILONERROR, $extraOptions) && $extraOptions[CURLOPT_FAILONERROR] && CURLE_GOT_NOTHING !== ($errno = curl_errno($curl)) && $error = curl_error($curl)) {
          curl_close($curl);

          throw WebDriverException::factory(WebDriverException::CURL_EXEC, sprintf("Curl error thrown for http %s to %s%s\n\n%s", $requestMethod, $url, $parameters && is_array($parameters) ? ' with params: ' . json_encode($parameters) : '', $error));
        }

        curl_close($curl);

        $result = json_decode($rawResult, TRUE);
        if (isset($result['status']) && $result['status'] === WebDriverException::STALE_ELEMENT_REFERENCE) {
          usleep(100000);
          $retries++;
          continue;
        }
        return [$rawResult, $info];
      }
      catch (CurlExec) {
        $retries++;
      }
    }
    if (empty($error)) {
      $error = "Retries: $retries and last result:\n" . ($rawResult ?? '');
    }
    throw WebDriverException::factory(WebDriverException::CURL_EXEC, sprintf("Curl error thrown for http %s to %s%s\n\n%s", $requestMethod, $url, $parameters && is_array($parameters) ? ' with params: ' . json_encode($parameters) : '', $error));
  }

}
