<?php

namespace Drupal\link\Plugin\migrate\process;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transform a pre-Drupal 8 formatted link for use in Drupal 8.
 *
 * Previous to Drupal 8, URLs didn't need to have a URI scheme assigned. The
 * contrib link module would auto-prefix the URL with a URI scheme. A link in
 * Drupal 8 has more validation and external links must include the URI scheme.
 * All external URIs need to be converted to use a URI scheme.
 *
 * Available configuration keys
 * - uri_scheme: (optional) The URI scheme prefix to use for URLs without a
 *   scheme. Defaults to 'http://', which was the default in Drupal 6 and
 *   Drupal 7.
 *
 * Examples:
 *
 * Consider a link field migration, where you want to use https:// as the
 * prefix:
 *
 * @code
 * process:
 *   field_link:
 *     plugin: field_link
 *     uri_scheme: 'https://'
 *     source: field_link
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "field_link"
 * )
 */
class FieldLink extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    $configuration += ['uri_scheme' => 'http://'];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Turn a Drupal 6/7 URI into a Drupal 8-compatible format.
   *
   * @param string $uri
   *   The 'url' value from Drupal 6/7.
   *
   * @return string
   *   The Drupal 8-compatible URI.
   *
   * @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget::getUserEnteredStringAsUri()
   */
  protected function canonicalizeUri($uri) {
    // If we already have a scheme, we're fine.
    if (empty($uri) || parse_url($uri, PHP_URL_SCHEME)) {
      return $uri;
    }

    // Remove the <front> component of the URL.
    if (strpos($uri, '<front>') === 0) {
      $uri = substr($uri, strlen('<front>'));
    }
    else {
      // List of unicode-encoded characters that were allowed in URLs,
      // according to link module in Drupal 7. Every character between &#x00BF;
      // and &#x00FF; (except × &#x00D7; and ÷ &#x00F7;) with the addition of
      // &#x0152;, &#x0153; and &#x0178;.
      // @see http://cgit.drupalcode.org/link/tree/link.module?h=7.x-1.5-beta2#n1382
      $link_ichars = '¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿŒœŸ';

      // Pattern specific to internal links.
      $internal_pattern = "/^(?:[a-z0-9" . $link_ichars . "_\-+\[\] ]+)";

      $directories = "(?:\/[a-z0-9" . $link_ichars . "_\-\.~+%=&,$'#!():;*@\[\]]*)*";
      // Yes, four backslashes == a single backslash.
      $query = "(?:\/?\?([?a-z0-9" . $link_ichars . "+_|\-\.~\/\\\\%=&,$'():;*@\[\]{} ]*))";
      $anchor = "(?:#[a-z0-9" . $link_ichars . "_\-\.~+%=&,$'():;*@\[\]\/\?]*)";

      // The rest of the path for a standard URL.
      $end = $directories . '?' . $query . '?' . $anchor . '?' . '$/i';

      if (!preg_match($internal_pattern . $end, $uri)) {
        $link_domains = '[a-z][a-z0-9-]{1,62}';

        // Starting a parenthesis group with (?: means that it is grouped, but is not captured
        $authentication = "(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=" . $link_ichars . "]|%[0-9a-f]{2})+(?::(?:[\w" . $link_ichars . "\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})*)?)?@)";
        $domain = '(?:(?:[a-z0-9' . $link_ichars . ']([a-z0-9' . $link_ichars . '\-_\[\]])*)(\.(([a-z0-9' . $link_ichars . '\-_\[\]])+\.)*(' . $link_domains . '|[a-z]{2}))?)';
        $ipv4 = '(?:[0-9]{1,3}(\.[0-9]{1,3}){3})';
        $ipv6 = '(?:[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7})';
        $port = '(?::([0-9]{1,5}))';

        // Pattern specific to external links.
        $external_pattern = '/^' . $authentication . '?(' . $domain . '|' . $ipv4 . '|' . $ipv6 . ' |localhost)' . $port . '?';
        if (preg_match($external_pattern . $end, $uri)) {
          return $this->configuration['uri_scheme'] . $uri;
        }
      }
    }

    // Add the internal: scheme and ensure a leading slash.
    return 'internal:/' . ltrim($uri, '/');
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $attributes = unserialize($value['attributes']);
    // Drupal 6/7 link attributes might be double serialized.
    if (!is_array($attributes)) {
      $attributes = unserialize($attributes);
    }

    if (!$attributes) {
      $attributes = [];
    }

    // Massage the values into the correct form for the link.
    $route['uri'] = $this->canonicalizeUri($value['url']);
    $route['options']['attributes'] = $attributes;
    $route['title'] = $value['title'];
    return $route;
  }

}
