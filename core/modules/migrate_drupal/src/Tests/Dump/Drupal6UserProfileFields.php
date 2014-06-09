<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6UserProfileFields.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing profile fields.
 */
class Drupal6UserProfileFields extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('profile_fields', array(
      'fields' => array(
        'fid' => array(
          'type' => 'serial',
          'not null' => TRUE,
        ),
        'title' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'name' => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ),
        'explanation' => array(
          'type' => 'text',
          'not null' => FALSE,
        ),
        'category' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'page' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'type' => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => FALSE,
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'required' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'register' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'visibility' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'autocomplete' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'options' => array(
          'type' => 'text',
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'category' => array(
          'category',
        ),
      ),
      'unique keys' => array(
        'name' => array(
          'name',
        ),
      ),
      'primary key' => array(
        'fid',
      ),
      'module' => 'profile',
      'name' => 'profile_fields',
    ));

    // Insert data.
    $data = static::getData('profile_fields');
    if ($data) {
      $query = $this->database->insert('profile_fields')->fields(array_keys($data[0]));
      foreach ($data as $record) {
        $query->values($record);
      }
      $query->execute();
    }
    $this->setModuleVersion('profile', 6001);
  }

  /**
   * Returns dump data from a specific table.
   *
   * @param string $table
   *   The table name.
   *
   * @return array
   *   Array of associative arrays each one having fields as keys.
   */
  public static function getData($table) {
    $data = array(
      'profile_fields' => array(
        array(
          'fid' => '8',
          'title' => 'Favorite color',
          'name' => 'profile_color',
          'explanation' => 'List your favorite color',
          'category' => 'Personal information',
          'page' => 'Peole whose favorite color is %value',
          'type' => 'textfield',
          'weight' => '-10',
          'required' => '0',
          'register' => '1',
          'visibility' => '2',
          'autocomplete' => '1',
          'options' => '',
        ),
        array(
          'fid' => '9',
          'title' => 'Biography',
          'name' => 'profile_biography',
          'explanation' => 'Tell people a little bit about yourself',
          'category' => 'Personal information',
          'page' => '',
          'type' => 'textarea',
          'weight' => '-8',
          'required' => '0',
          'register' => '0',
          'visibility' => '2',
          'autocomplete' => '0',
          'options' => '',
        ),
        array(
          'fid' => '10',
          'title' => 'Sell your email address?',
          'name' => 'profile_sell_address',
          'explanation' => "If you check this box, we'll sell your address to spammers to help line the pockets of our shareholders. Thanks!",
          'category' => 'Communication preferences',
          'page' => 'People who want us to sell their address',
          'type' => 'checkbox',
          'weight' => '-10',
          'required' => '0',
          'register' => '1',
          'visibility' => '1',
          'autocomplete' => '0',
          'options' => '',
        ),
        array(
          'fid' => '11',
          'title' => 'Sales Category',
          'name' => 'profile_sold_to',
          'explanation' => "Select the sales categories to which this user's address was sold.",
          'category' => 'Administrative data',
          'page' => 'People whose address was sold to %value',
          'type' => 'selection',
          'weight' => '-10',
          'required' => '0',
          'register' => '0',
          'visibility' => '4',
          'autocomplete' => '0',
          'options' => "Pill spammers\r\nFitness spammers",
        ),
        array(
          'fid' => '12',
          'title' => 'Favorite bands',
          'name' => 'profile_bands',
          'explanation' => "Enter your favorite bands. When you've saved your profile, you'll be able to find other people with the same favorites.",
          'category' => 'Personal information',
          'page' => '',
          'type' => 'list',
          'weight' => '-6',
          'required' => '0',
          'register' => '1',
          'visibility' => '3',
          'autocomplete' => '1',
          'options' => '',
        ),
/*
        array(
          'fid' => '13',
          'title' => 'Your blog',
          'name' => 'profile_blog',
          'explanation' => 'Paste the full URL, including http://, of your personal blog.',
          'category' => 'Personal information',
          'page' => '',
          'type' => 'url',
          'weight' => '0',
          'required' => '0',
          'register' => '0',
          'visibility' => '2',
          'autocomplete' => '0',
          'options' => '',
        ),
*/
        array(
          'fid' => '14',
          'title' => 'Birthdate',
          'name' => 'profile_birthdate',
          'explanation' => "Enter your birth date and we'll send you a coupon.",
          'category' => 'Personal information',
          'page' => '',
          'type' => 'date',
          'weight' => '4',
          'required' => '0',
          'register' => '0',
          'visibility' => '2',
          'autocomplete' => '0',
          'options' => '',
        ),
        array(
          'fid' => '15',
          'title' => 'I love migrations',
          'name' => 'profile_love_migrations',
          'explanation' => "If you check this box, you love migrations.",
          'category' => 'Personal information',
          'page' => 'People who love migrations',
          'type' => 'checkbox',
          'weight' => '-15',
          'required' => '0',
          'register' => '0',
          'visibility' => '2',
          'autocomplete' => '0',
          'options' => '',
        ),
      ),
    );

    return isset($data[$table]) ? $data[$table] : FALSE;
  }

}

