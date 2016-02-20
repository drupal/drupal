<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\migrate\process\d6\UrlAliasLanguage.
 */

namespace Drupal\path\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Language\LanguageInterface;

/**
 * Url alias language code process.
 *
 * @MigrateProcessPlugin(
 *   id = "d6_url_alias_language"
 * )
 */
class UrlAliasLanguage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $langcode = ($value === '') ? LanguageInterface::LANGCODE_NOT_SPECIFIED : $value;
    return $langcode;
  }

}
