<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use GuzzleHttp\Psr7\Uri;

/**
 * URL-encodes the input value.
 *
 * Example:
 *
 * @code
 * process:
 *   new_url:
 *     plugin: urlencode
 *     source: 'http://example.com/a url with spaces.html'
 * @endcode
 *
 * This will convert the source URL 'http://example.com/a url with spaces.html'
 * into 'http://example.com/a%20url%20with%20spaces.html'.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('urlencode')]
class UrlEncode extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Only apply to a full URL.
    if (is_string($value) && strpos($value, '://') > 0) {
      // URL encode everything after the hostname.
      $parsed_url = parse_url($value);
      // Fail on seriously malformed URLs.
      if ($parsed_url === FALSE) {
        throw new MigrateException("Value '$value' is not a valid URL");
      }
      // Iterate over specific pieces of the URL raw URL encoding each one.
      $url_parts_to_encode = ['path', 'query', 'fragment'];
      foreach ($parsed_url as $parsed_url_key => $parsed_url_value) {
        if (in_array($parsed_url_key, $url_parts_to_encode)) {
          // urlencode() would convert spaces to + signs.
          $urlencoded_parsed_url_value = rawurlencode($parsed_url_value);
          // Restore special characters depending on which part of the URL this
          // is.
          switch ($parsed_url_key) {
            case 'query':
              $urlencoded_parsed_url_value = str_replace('%26', '&', $urlencoded_parsed_url_value);
              break;

            case 'path':
              $urlencoded_parsed_url_value = str_replace('%2F', '/', $urlencoded_parsed_url_value);
              break;
          }

          $parsed_url[$parsed_url_key] = $urlencoded_parsed_url_value;
        }
      }
      $value = (string) Uri::fromParts($parsed_url);
    }
    return $value;
  }

}
