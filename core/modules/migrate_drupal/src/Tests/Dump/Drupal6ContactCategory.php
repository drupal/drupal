<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6ContactCategories.
 */

namespace Drupal\migrate_drupal\Tests\Dump;
/**
 * Database dump for testing contact category migration.
 */
class Drupal6ContactCategory extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('contact', array(
      'description' => 'Contact form category settings.',
      'fields' => array(
        'cid' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'Primary Key: Unique category ID.',
        ),
        'category' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Category name.',
          'translatable' => TRUE,
        ),
        'recipients' => array(
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
          'description' => 'Comma-separated list of recipient email addresses.',
        ),
        'reply' => array(
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
          'description' => 'Text of the auto-reply message.',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => "The category's weight.",
        ),
        'selected' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'Flag to indicate whether or not category is selected by default. (1 = Yes, 0 = No)',
        ),
      ),
      'primary key' => array('cid'),
      'unique keys' => array(
        'category' => array('category'),
      ),
      'indexes' => array(
        'list' => array('weight', 'category'),
      ),
    ));
    $this->database->insert('contact')->fields(array('cid', 'category', 'recipients', 'reply', 'weight', 'selected'))
      ->values(array(
        'cid' => '1',
        'category' => 'Website feedback',
        'recipients' => 'admin@example.com',
        'reply' => '',
        'weight' => '0',
        'selected' => '0',
      ))
      ->values(array(
        'cid' => '2',
        'category' => 'Some other category',
        'recipients' => 'test@example.com',
        'reply' => 'Thanks for contacting us, we will reply ASAP!',
        'weight' => '1',
        'selected' => '1',
      ))
      ->values(array(
        'cid' => '3',
        'category' => 'A category much longer than thirty two characters',
        'recipients' => 'fortyninechars@example.com',
        'reply' => '',
        'weight' => '2',
        'selected' => '0',
      ))
      ->execute();
    $this->setModuleVersion('contact', '6001');
  }
}
