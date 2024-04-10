<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Plugin\Search\NodeSearch;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests that search works with numeric locale settings.
 *
 * @group search
 */
class SearchSetLocaleTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'node',
    'search',
    'system',
    'text',
    'user',
  ];

  /**
   * A node search plugin instance.
   */
  protected NodeSearch $nodeSearchPlugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', ['node_access']);
    $this->installSchema('search', ['search_index', 'search_dataset', 'search_total']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['filter', 'node', 'search']);

    // Create the anonymous user account and set it as current user.
    $this->setUpCurrentUser(['uid' => 0]);

    // Create a node type.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Create a plugin instance.
    $this->nodeSearchPlugin = $this->container->get('plugin.manager.search')->createInstance('node_search');

    // Create a node with a very simple body.
    $this->createNode(
      [
        'body' => [
          [
            'value' => 'tapir',
          ],
        ],
        'uid' => 0,
      ]
    );

    // Create the fallback date format.
    DateFormat::create([
      'id' => 'fallback',
      'label' => 'Fallback date format',
      'pattern' => 'D, m/d/Y - H:i',
      'locked' => TRUE,
    ])->save();

    // Update the search index.
    $this->nodeSearchPlugin->updateIndex();
  }

  /**
   * Verify that search works with a numeric locale set.
   */
  public function testSearchWithNumericLocale(): void {
    // French decimal point is comma.
    setlocale(LC_NUMERIC, 'fr_FR');
    $this->nodeSearchPlugin->setSearch('tapir', [], []);
    // The call to execute will throw an exception if a float in the wrong
    // format is passed in the query to the database, so an assertion is not
    // necessary here.
    $this->nodeSearchPlugin->execute();
  }

}
