<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that search works with numeric locale settings.
 *
 * @group search
 */
class SearchSetLocaleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A node search plugin instance.
   *
   * @var \Drupal\search\Plugin\SearchInterface
   */
  protected $nodeSearchPlugin;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a plugin instance.
    $this->nodeSearchPlugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    // Create a node with a very simple body.
    $this->drupalCreateNode(['body' => [['value' => 'tapir']]]);
    // Update the search index.
    $this->nodeSearchPlugin->updateIndex();
  }

  /**
   * Verify that search works with a numeric locale set.
   */
  public function testSearchWithNumericLocale() {
    // French decimal point is comma.
    setlocale(LC_NUMERIC, 'fr_FR');
    $this->nodeSearchPlugin->setSearch('tapir', [], []);
    // The call to execute will throw an exception if a float in the wrong
    // format is passed in the query to the database, so an assertion is not
    // necessary here.
    $this->nodeSearchPlugin->execute();
  }

}
