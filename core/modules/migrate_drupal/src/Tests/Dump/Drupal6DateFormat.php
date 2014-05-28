<?php

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing date formats migration.
 */
class Drupal6DateFormat extends Drupal6DumpBase {

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
        'name' => 'date_format_long',
        'value' => 's:24:"\\L\\O\\N\\G l, F j, Y - H:i";',
      ))
      ->values(array(
        'name' => 'date_format_medium',
        'value' => 's:27:"\\M\\E\\D\\I\\U\\M D, m/d/Y - H:i";',
      ))
      ->values(array(
        'name' => 'date_format_short',
        'value' => 's:22:"\\S\\H\\O\\R\\T m/d/Y - H:i";',
      ))
      ->execute();
  }

}
