<?php
/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6UrlAlias.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing url alias migrations.
 */
class Drupal6UrlAlias extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('url_alias', array(
      'description' => 'A list of URL aliases for Drupal paths; a user may visit either the source or destination path.',
      'fields' => array(
        'pid' => array(
          'description' => 'A unique path alias identifier.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'src' => array(
          'description' => 'The Drupal path this alias is for; e.g. node/12.',
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ),
        'dst' => array(
          'description' => 'The alias for this path; e.g. title-of-the-story.',
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ),
        'language' => array(
          'description' => 'The language this alias is for; if blank, the alias will be used for unknown languages. Each Drupal path can have an alias for each supported language.',
          'type' => 'varchar',
          'length' => 12,
          'not null' => TRUE,
          'default' => '',
        ),
      ),
      'unique keys' => array(
        'dst_language_pid' => array(
          'dst',
          'language',
          'pid',
        ),
      ),
      'primary key' => array('pid'),
      'indexes' => array('src_language_pid' => array('src', 'language', 'pid')),
    ));

    $this->database->insert('url_alias')->fields(array(
      'pid',
      'src',
      'dst',
      'language',
    ))
    ->values(array(
      'pid' => 1,
      'src' => 'node/1',
      'dst' => 'alias-one',
      'language' => 'en',
    ))
    ->values(array(
      'pid' => 2,
      'src' => 'node/2',
      'dst' => 'alias-two',
      'language' => 'en',
    ))
    ->execute();

  }

}
