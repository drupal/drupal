<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Plugin\views\filter\EntityReference;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\filter\EntityReference handler.
 *
 * @group views
 */
class FieldEntityReferenceTest extends ViewsKernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'user',
    'field',
    'text',
    'filter',
    'views',
  ];

  /**
   * Test host nodes containing the entity reference.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $hostNodes;

  /**
   * Test target nodes referenced by the entity reference.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $targetNodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['node', 'user', 'filter']);

    ViewTestData::createTestViews(static::class, ['views_test_config']);
    // Create two node types.
    $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);

    // Add an entity reference field to the page type referencing the article
    // type.
    $selection_handler_settings = [
      'target_bundles' => [
        'article' => 'article',
      ],
    ];
    $this->createEntityReferenceField('node', 'page', 'field_test', 'Test reference', 'node', $selection_handler = 'default', $selection_handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Create user 1.
    $admin = $this->createUser();

    // Create target nodes to be referenced.
    foreach (range(0, 5) as $count) {
      $this->targetNodes[$count] = $this->createNode([
        'type' => 'article',
        'title' => 'Article ' . $count,
        'status' => 1,
      ]);
    }

    // Create a page referencing Article 0 and Article 1.
    $this->hostNodes[0] = $this->createNode([
      'type' => 'page',
      'title' => 'Page 0',
      'status' => 1,
      'field_test' => [
        $this->targetNodes[0]->id(),
        $this->targetNodes[1]->id(),
      ],
    ]);

    // Create a page referencing Article 1, Article 2, and Article 3.
    $this->hostNodes[1] = $this->createNode([
      'type' => 'page',
      'title' => 'Page 1',
      'status' => 1,
      'field_test' => [
        $this->targetNodes[1]->id(),
        $this->targetNodes[2]->id(),
        $this->targetNodes[3]->id(),
      ],
    ]);

    // Create a page referencing nothing.
    $this->hostNodes[2] = $this->createNode([
      'type' => 'page',
      'title' => 'Page 2',
      'status' => 1,
    ]);
  }

  /**
   * Tests that results are successfully filtered by the select list widget.
   */
  public function testViewEntityReferenceAsSelectList() {
    $view = Views::getView('test_filter_entity_reference');
    $view->setDisplay();
    $view->preExecute([]);
    $view->setExposedInput([
      'field_test_target_id' => [$this->targetNodes[0]->id()],
    ]);
    $this->executeView($view);

    // Expect to have only Page 0, with Article 0 referenced.
    $expected = [
      ['title' => 'Page 0'],
    ];
    $this->assertIdenticalResultset($view, $expected, [
      'title' => 'title',
    ]);

    // Change to both Article 0 and Article 3.
    $view = Views::getView('test_filter_entity_reference');
    $view->setDisplay();
    $view->setExposedInput([
      'field_test_target_id' => [
        $this->targetNodes[0]->id(),
        $this->targetNodes[3]->id(),
      ],
    ]);
    $this->executeView($view);

    // Expect to have Page 0 and 1, with Article 0 and 3 referenced.
    $expected = [
      ['title' => 'Page 0'],
      ['title' => 'Page 1'],
    ];
    $this->assertIdenticalResultset($view, $expected, [
      'title' => 'title',
    ]);
  }

  /**
   * Tests that results are successfully filtered by the autocomplete widget.
   */
  public function testViewEntityReferenceAsAutocomplete() {

    // Change the widget to autocomplete.
    $view = Views::getView('test_filter_entity_reference');
    $view->setDisplay();
    $filters = $view->displayHandlers->get('default')->getOption('filters');
    $filters['field_test_target_id']['widget'] = EntityReference::WIDGET_AUTOCOMPLETE;
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);
    $view->setExposedInput([
      'field_test_target_id' => [
        ['target_id' => $this->targetNodes[0]->id()],
        ['target_id' => $this->targetNodes[3]->id()],
      ],
    ]);
    $this->executeView($view);

    // Expect to have Page 0 and 1, with Article 0 and 3 referenced.
    $expected = [
      ['title' => 'Page 0'],
      ['title' => 'Page 1'],
    ];
    $this->assertIdenticalResultset($view, $expected, [
      'title' => 'title',
    ]);
  }

}
