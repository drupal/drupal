<?php

namespace Drupal\FunctionalJavascriptTests;

use WebDriver\Service\CurlService;
use WebDriver\Exception\CurlExec;
use WebDriver\Exception as WebDriverException;

/**
 * Provides a curl service to interact with Selenium driver.
 *
 * Extends WebDriver\Service\CurlService to solve problem with race conditions,
 * when multiple processes requests.
 */
class WebDriverCurlService extends CurlService {

  /**
   * {@inheritdoc}
   */
  public function execute($requestMethod, $url, $parameters = NULL, $extraOptions = []) {
    $extraOptions += [
      CURLOPT_FAILONERROR => TRUE,
    ];
    $retries = 0;
    while ($retries < 10) {
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

        $rawResult = trim(curl_exec($curl));

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
      catch (CurlExec $exception) {
        $retries++;
      }
    }
    throw WebDriverException::factory(WebDriverException::CURL_EXEC, sprintf("Curl error thrown for http %s to %s%s\n\n%s", $requestMethod, $url, $parameters && is_array($parameters) ? ' with params: ' . json_encode($parameters) : '', $error));
  }

}
