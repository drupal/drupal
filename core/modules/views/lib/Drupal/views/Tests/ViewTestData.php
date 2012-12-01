<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewTestData.
 */

namespace Drupal\views\Tests;

/**
 * Provides tests view data and the base test schema with sample data records.
 *
 * The methods will be used by both views test base classes.
 *
 * @see \Drupal\views\Tests\ViewUnitTestBase.
 * @see \Drupal\views\Tests\ViewTestBase.
 */
class ViewTestData {

  /**
   * Returns the schema definition.
   */
  public static function schemaDefinition() {
    $schema['views_test_data'] = array(
      'description' => 'Basic test table for Views tests.',
      'fields' => array(
        'id' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'name' => array(
          'description' => "A person's name",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'age' => array(
          'description' => "The person's age",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'job' => array(
          'description' => "The person's job",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => 'Undefined',
        ),
        'created' => array(
          'description' => "The creation date of this record",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'primary key' => array('id'),
      'unique keys' => array(
        'name' => array('name')
      ),
      'indexes' => array(
        'ages' => array('age'),
      ),
    );
    return $schema;
  }

  /**
   * Returns the views data definition.
   */
  public static function viewsData() {
    // Declaration of the base table.
    $data['views_test_data']['table'] = array(
      'group' => t('Views test'),
      'base' => array(
        'field' => 'id',
        'title' => t('Views test data'),
        'help' => t('Users who have created accounts on your site.'),
      ),
    );

    // Declaration of fields.
    $data['views_test_data']['id'] = array(
      'title' => t('ID'),
      'help' => t('The test data ID'),
      'field' => array(
        'id' => 'numeric',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['name'] = array(
      'title' => t('Name'),
      'help' => t('The name of the person'),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'filter' => array(
        'id' => 'string',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['age'] = array(
      'title' => t('Age'),
      'help' => t('The age of the person'),
      'field' => array(
        'id' => 'numeric',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['job'] = array(
      'title' => t('Job'),
      'help' => t('The job of the person'),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'filter' => array(
        'id' => 'string',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['created'] = array(
      'title' => t('Created'),
      'help' => t('The creation date of this record'),
      'field' => array(
        'id' => 'date',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'date',
      ),
      'filter' => array(
        'id' => 'date',
      ),
      'sort' => array(
        'id' => 'date',
      ),
    );
    return $data;
  }

  /**
   * Returns a very simple test dataset.
   */
  public static function dataSet() {
    return array(
      array(
        'name' => 'John',
        'age' => 25,
        'job' => 'Singer',
        'created' => gmmktime(0, 0, 0, 1, 1, 2000),
      ),
      array(
        'name' => 'George',
        'age' => 27,
        'job' => 'Singer',
        'created' => gmmktime(0, 0, 0, 1, 2, 2000),
      ),
      array(
        'name' => 'Ringo',
        'age' => 28,
        'job' => 'Drummer',
        'created' => gmmktime(6, 30, 30, 1, 1, 2000),
      ),
      array(
        'name' => 'Paul',
        'age' => 26,
        'job' => 'Songwriter',
        'created' => gmmktime(6, 0, 0, 1, 1, 2000),
      ),
      array(
        'name' => 'Meredith',
        'age' => 30,
        'job' => 'Speaker',
        'created' => gmmktime(6, 30, 10, 1, 1, 2000),
      ),
    );
  }

}

