<?php

namespace Drupal\language\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\ArrayBuild;
use Drupal\migrate\Row;

/**
 * This plugin makes sure that no domain is empty if domain negotiation is used.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess(
  id: "language_domains",
  handle_multiples: TRUE,
)]
class LanguageDomains extends ArrayBuild {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('domain_negotiation_used')) {
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
