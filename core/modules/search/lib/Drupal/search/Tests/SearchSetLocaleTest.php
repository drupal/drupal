<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchSetLocaleTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests searching with locale values set.
 */
class SearchSetLocaleTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment');

  /**
   * A node search plugin instance.
   *
   * @var \Drupal\search\Plugin\SearchInterface
   */
  protected $nodeSearchPlugin;

  public static function getInfo() {
    return array(
      'name' => 'Search with numeric locale set',
      'description' => 'Check that search works with numeric locale settings',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a plugin instance.
    $this->nodeSearchPlugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    // Create a node with a very simple body.
    $this->drupalCreateNode(array('body' => array(array('value' => 'tapir'))));
    // Update the search index.
    $this->nodeSearchPlugin->updateIndex();
    search_update_totals();
  }

  /**
   * Verify that search works with a numeric locale set.
   */
  public function testSearchWithNumericLocale() {
    // French decimal point is comma.
    setlocale(LC_NUMERIC, 'fr_FR');
    $this->nodeSearchPlugin->setSearch('tapir', array(), array());
    // The call to execute will throw an exception if a float in the wrong
    // format is passed in the query to the database, so an assertion is not
    // necessary here.
    $this->nodeSearchPlugin->execute();
  }
}
