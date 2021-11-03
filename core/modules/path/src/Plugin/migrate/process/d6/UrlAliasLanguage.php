<?php

namespace Drupal\path\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Language\LanguageInterface;

/**
 * Url alias language code process.
 *
 * @MigrateProcessPlugin(
 *   id = "d6_url_alias_language",
 *   no_ui = TRUE
 * )
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. No direct
 *   replacement is provided.
 *
 * @see https://www.drupal.org/node/3219051
 */
class UrlAliasLanguage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3219051', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $langcode = ($value === '') ? LanguageInterface::LANGCODE_NOT_SPECIFIED : $value;
    return $langcode;
  }

}
