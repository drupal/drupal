<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6UserRole.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing user role migration.
 */
class Drupal6UserRole extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    foreach (static::getSchema() as $table => $schema) {
      // Create tables.
      $this->createTable($table, $schema);

      // Insert data.
      $data = static::getData($table);
      if ($data) {
        $query = $this->database->insert($table)->fields(array_keys($data[0]));
        foreach ($data as $record) {
          $query->values($record);
        }
        $query->execute();
      }
    }
  }

  /**
   * Defines schema for this database dump.
   *
   * @return array
   *   Associative array having the structure as is returned by hook_schema().
   */
  protected static function getSchema() {
    return array(
      'permission' => array(
        'description' => 'Stores permissions for users.',
        'fields' => array(
          'pid' => array(
            'type' => 'serial',
            'not null' => TRUE,
            'description' => 'Primary Key: Unique permission ID.',
          ),
          'rid' => array(
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The {role}.rid to which the permissions are assigned.',
          ),
          'perm' => array(
            'type' => 'text',
            'not null' => FALSE,
            'size' => 'big',
            'description' => 'List of permissions being assigned.',
          ),
          'tid' => array(
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
            'description' => 'Originally intended for taxonomy-based permissions, but never used.',
          ),
        ),
        'primary key' => array('pid'),
        'indexes' => array('rid' => array('rid')),
      ),
      'role' => array(
        'description' => 'Stores user roles.',
        'fields' => array(
          'rid' => array(
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'Primary Key: Unique role id.',
          ),
          'name' => array(
            'type' => 'varchar',
            'length' => 64,
            'not null' => TRUE,
            'default' => '',
            'description' => 'Unique role name.',
          ),
        ),
        'unique keys' => array('name' => array('name')),
        'primary key' => array('rid'),
      ),
    );
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
      'permission' => array(
        array('pid' => 1, 'rid' => 1, 'perm' => 'migrate test anonymous permission'),
        array('pid' => 2, 'rid' => 2, 'perm' => 'migrate test authenticated permission'),
        array('pid' => 3, 'rid' => 3, 'perm' => 'migrate test role 1 test permission'),
        array('pid' => 4, 'rid' => 4, 'perm' => 'migrate test role 2 test permission, use PHP for settings, administer contact forms, skip comment approval, edit own blog content, edit any blog content, delete own blog content, delete any blog content, create forum content, delete any forum content, delete own forum content, edit any forum content, edit own forum content, administer nodes'),
      ),
      'role' => array(
        array('rid' => 1, 'name' => 'anonymous user'),
        array('rid' => 2, 'name' => 'authenticated user'),
        array('rid' => 3, 'name' => 'migrate test role 1'),
        array('rid' => 4, 'name' => 'migrate test role 2'),
        array('rid' => 5, 'name' => 'migrate test role 3'),
      ),
    );

    return isset($data[$table]) ? $data[$table] : FALSE;
  }

}
