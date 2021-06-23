<?php

namespace Drupal\path\Plugin\migrate\process\d6;

@trigger_error('The ' . __NAMESPACE__ . '\UrlAliasLanguage is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3013865', E_USER_DEPRECATED);

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Language\LanguageInterface;

/**
 * Url alias language code process.
 *
 * @MigrateProcessPlugin(
 *   id = "d6_url_alias_language"
 * )
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct
 * replacement is provided. See https://www.drupal.org/node/3013865
 *
 * @see https://www.drupal.org/node/3013865
 */
class UrlAliasLanguage extends ProcessPluginBase {

  /**
   * UrlAliasLanguage constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The Migration the plugin is being used in.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   * @param \Drupal\migrate\MigrateStubInterface $migrate_stub
   *   The migrate stub service.
   */
  // @codingStandardsIgnoreLine
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateLookupInterface $migrate_lookup, MigrateStubInterface $migrate_stub) {
    @trigger_error('\Drupal\path\Plugin\migrate\process\d6\UrlAliasLanguage is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided See https://www.drupal.org/node/TBA', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->migrateLookup = $migrate_lookup;
    $this->migrateStub = $migrate_stub;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $langcode = ($value === '') ? LanguageInterface::LANGCODE_NOT_SPECIFIED : $value;
    return $langcode;
  }

}
