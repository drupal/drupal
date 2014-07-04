<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6AggregatorSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing aggregator.settings.yml migration.
 */
class Drupal6AggregatorSettings extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('variable');
    $this->database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'aggregator_fetcher',
      'value' => 's:10:"aggregator";',
    ))
    ->values(array(
      'name' => 'aggregator_parser',
      'value' => 's:10:"aggregator";',
    ))
    ->values(array(
      'name' => 'aggregator_processors',
      'value' => 'a:1:{i:0;s:10:"aggregator";}',
    ))
    ->values(array(
      'name' => 'aggregator_allowed_html_tags',
      'value' => 's:70:"<a> <b> <br /> <dd> <dl> <dt> <em> <i> <li> <ol> <p> <strong> <u> <ul>";',
    ))
    ->values(array(
      'name' => 'aggregator_teaser_length',
      'value' => 's:3:"600";',
    ))
    ->values(array(
      'name' => 'aggregator_clear',
      'value' => 's:7:"9676800";',
    ))
    ->values(array(
      'name' => 'aggregator_summary_items',
      'value' => 's:1:"3";',
    ))
    ->execute();
  }
}
