<?php
/**
 * @file
 * Definition of Drupal\views\Tests\ViewsSqlTest
 */
namespace Drupal\views\Tests;

use Drupal\views\View;

abstract class ViewsSqlTest extends ViewsTestBase {

  protected function setUp() {
    parent::setUp('views', 'views_ui');

    // Define the schema and views data variable before enabling the test module.
    variable_set('views_test_schema', $this->schemaDefinition());
    variable_set('views_test_views_data', $this->viewsData());
    variable_set('views_test_views_plugins', $this->viewsPlugins());

    module_enable(array('views_test'));
    $this->resetAll();

    // Load the test dataset.
    $data_set = $this->dataSet();
    $query = db_insert('views_test')
      ->fields(array_keys($data_set[0]));
    foreach ($data_set as $record) {
      $query->values($record);
    }
    $query->execute();
    $this->checkPermissions(array(), TRUE);
  }

  /**
   * This function allows to enable views ui from a higher class which can't change the setup function anymore.
   *
   * @TODO
   *   Convert existing setUp functions.
   */
  function enableViewsUi() {
    module_enable(array('views_ui'));
    // @TODO Figure out why it's required to clear the cache here.
    views_module_include('views_default', TRUE);
    views_get_all_views(TRUE);
    menu_router_rebuild();
  }

  /**
   * The schema definition.
   */
  protected function schemaDefinition() {
    $schema['views_test'] = array(
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
   * The views data definition.
   */
  protected function viewsData() {
    // Declaration of the base table.
    $data['views_test']['table'] = array(
      'group' => t('Views test'),
      'base' => array(
        'field' => 'id',
        'title' => t('Views test'),
        'help' => t('Users who have created accounts on your site.'),
      ),
    );

    // Declaration of fields.
    $data['views_test']['id'] = array(
      'title' => t('ID'),
      'help' => t('The test data ID'),
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_numeric',
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_numeric',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    );
    $data['views_test']['name'] = array(
      'title' => t('Name'),
      'help' => t('The name of the person'),
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_string',
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_string',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    );
    $data['views_test']['age'] = array(
      'title' => t('Age'),
      'help' => t('The age of the person'),
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_numeric',
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_numeric',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    );
    $data['views_test']['job'] = array(
      'title' => t('Job'),
      'help' => t('The job of the person'),
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_string',
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_string',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    );
    $data['views_test']['created'] = array(
      'title' => t('Created'),
      'help' => t('The creation date of this record'),
      'field' => array(
        'handler' => 'views_handler_field_date',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_date',
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_date',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort_date',
      ),
    );
    return $data;
  }

  protected function viewsPlugins() {
    return array();
  }

  /**
   * A very simple test dataset.
   */
  protected function dataSet() {
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

  /**
   * Build and return a basic view of the views_test table.
   *
   * @return Drupal\views\View
   */
  protected function getBasicView() {
    // Create the basic view.
    $view = new View();
    $view->name = 'test_view';
    $view->add_display('default');
    $view->base_table = 'views_test';

    // Set up the fields we need.
    $display = $view->new_display('default', 'Master', 'default');
    $display->override_option('fields', array(
      'id' => array(
        'id' => 'id',
        'table' => 'views_test',
        'field' => 'id',
        'relationship' => 'none',
      ),
      'name' => array(
        'id' => 'name',
        'table' => 'views_test',
        'field' => 'name',
        'relationship' => 'none',
      ),
      'age' => array(
        'id' => 'age',
        'table' => 'views_test',
        'field' => 'age',
        'relationship' => 'none',
      ),
    ));

    // Set up the sort order.
    $display->override_option('sorts', array(
      'id' => array(
        'order' => 'ASC',
        'id' => 'id',
        'table' => 'views_test',
        'field' => 'id',
        'relationship' => 'none',
      ),
    ));

    // Set up the pager.
    $display->override_option('pager', array(
      'type' => 'none',
      'options' => array('offset' => 0),
    ));

    return $view;
  }
}
