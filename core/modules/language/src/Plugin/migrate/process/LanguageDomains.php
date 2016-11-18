<?php

namespace Drupal\language\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\ArrayBuild;
use Drupal\migrate\Row;

/**
 * This plugin makes sure that no domain is empty if domain negotiation is used.
 *
 * @MigrateProcessPlugin(
 *   id = "language_domains",
 *   handle_multiples = TRUE
 * )
 */
class LanguageDomains extends ArrayBuild {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('domain_negotiation')) {
      global $base_url;

      foreach ($value as $old_key => $old_value) {
        if (empty($old_value['domain'])) {
          // The default language domain might be empty.
          // If it is, use the current domain.
          $value[$old_key]['domain'] = parse_url($base_url, PHP_URL_HOST);
        }
        else {
          // Ensure we have a protocol when checking for the hostname.
          $domain = 'http://' . str_replace(['http://', 'https://'], '', $old_value['domain']);
          // Only keep the host part of the domain.
          $value[$old_key]['domain'] = parse_url($domain, PHP_URL_HOST);
        }
      }
    }

    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
