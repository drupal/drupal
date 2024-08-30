<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Functional\Views;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the serializer style plugin.
 *
 * @group rest
 * @see \Drupal\rest\Plugin\views\display\RestExport
 * @see \Drupal\rest\Plugin\views\style\Serializer
 * @see \Drupal\rest\Plugin\views\row\DataEntityRow
 * @see \Drupal\rest\Plugin\views\row\DataFieldRow
 */
class StyleSerializerTest extends ViewTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views_ui',
    'entity_test',
    'rest_test_views',
    'node',
    'text',
    'field',
    'language',
    'basic_auth',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_serializer_display_field', 'test_serializer_display_entity', 'test_serializer_display_entity_translated', 'test_serializer_node_display_field', 'test_serializer_node_exposed_filter', 'test_serializer_shared_path'];

  /**
   * A user with administrative privileges to look at test entity and configure views.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['rest_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->adminUser = $this->drupalCreateUser([
      'administer views',
      'administer entity_test content',
      'access user profiles',
      'view test entity',
    ]);

    $this->enableViewsTestModule();
    $this->renderer = \Drupal::service('renderer');
  }

  /**
   * Checks that the auth options restricts access to a REST views display.
   */
  public function testRestViewsAuthentication(): void {
    // Assume the view is hidden behind a permission.
    $this->drupalGet('test/serialize/auth_with_perm', ['query' => ['_format' => 'json']]);
    $this->assertSession()->statusCodeEquals(401);

    // Not even logging in would make it possible to see the view, because then
    // we are denied based on authentication method (cookie).
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('test/serialize/auth_with_perm', ['query' => ['_format' => 'json']]);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();

    // But if we use the basic auth authentication strategy, we should be able
    // to see the page.
    $url = $this->buildUrl('test/serialize/auth_with_perm');
    $response = \Drupal::httpClient()->get($url, [
      'auth' => [$this->adminUser->getAccountName(), $this->adminUser->pass_raw],
      'query' => [
        '_format' => 'json',
      ],
    ]);

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Verifies REST export views work on the same path as a page display.
   */
  public function testSharedPagePath(): void {
    // Test with no format as well as html explicitly.
    $this->drupalGet('test/serialize/shared');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('content-type', 'text/html; charset=UTF-8');

    $this->drupalGet('test/serialize/shared', ['query' => ['_format' => 'html']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('content-type', 'text/html; charset=UTF-8');

    $this->drupalGet('test/serialize/shared', ['query' => ['_format' => 'json']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('content-type', 'application/json');

    $this->drupalGet('test/serialize/shared', ['query' => ['_format' => 'xml']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('content-type', 'text/xml; charset=UTF-8');
  }

  /**
   * Verifies site maintenance mode functionality.
   */
  public function testSiteMaintenance(): void {
    $view = Views::getView('test_serializer_display_field');
    $view->initDisplay();
    $this->executeView($view);

    // Set the site to maintenance mode.
    $this->container->get('state')->set('system.maintenance_mode', TRUE);

    $this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'json']]);
    // Verify that the endpoint is unavailable for anonymous users.
    $this->assertSession()->statusCodeEquals(503);
  }

  /**
   * Sets up a request on the request stack with a specified format.
   *
   * @param string $format
   *   The new request format.
   */
  protected function addRequestWithFormat($format) {
    $request = \Drupal::request();
    $request = clone $request;
    $request->setRequestFormat($format);

    \Drupal::requestStack()->push($request);
  }

  /**
   * Tests the "Grouped rows" functionality.
   */
  public function testGroupRows(): void {
    $this->drupalCreateContentType(['type' => 'page']);
    // Create a text field with cardinality set to unlimited.
    $field_name = 'field_group_rows';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();
    // Create an instance of the text field on the content type.
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ]);
    $field->save();
    $grouped_field_values = ['a', 'b', 'c'];
    $edit = [
      'title' => $this->randomMachineName(),
      $field_name => $grouped_field_values,
    ];
    $this->drupalCreateNode($edit);
    $view = Views::getView('test_serializer_node_display_field');
    $view->setDisplay('rest_export_1');
    // Override the view's fields to include the field_group_rows field, set the
    // group_rows setting to true.
    $fields = [
      $field_name => [
        'id' => $field_name,
        'table' => 'node__' . $field_name,
        'field' => $field_name,
        'type' => 'string',
        'group_rows' => TRUE,
      ],
    ];
    $view->displayHandlers->get('default')->overrideOption('fields', $fields);
    $build = $view->preview();
    // Get the serializer service.
    $serializer = $this->container->get('serializer');
    // Check if the field_group_rows field is grouped.
    $expected = [];
    $expected[] = [$field_name => implode(', ', $grouped_field_values)];
    $this->assertEquals($serializer->serialize($expected, 'json'), (string) $this->renderer->renderRoot($build));
    // Set the group rows setting to false.
    $view = Views::getView('test_serializer_node_display_field');
    $view->setDisplay('rest_export_1');
    $fields[$field_name]['group_rows'] = FALSE;
    $view->displayHandlers->get('default')->overrideOption('fields', $fields);
    $build = $view->preview();
    // Check if the field_group_rows field is ungrouped and displayed per row.
    $expected = [];
    foreach ($grouped_field_values as $grouped_field_value) {
      $expected[] = [$field_name => $grouped_field_value];
    }
    $this->assertEquals($serializer->serialize($expected, 'json'), (string) $this->renderer->renderRoot($build));
  }

  /**
   * Tests the exposed filter works.
   *
   * There is an exposed filter on the title field which takes a title query
   * parameter. This is set to filter nodes by those whose title starts with
   * the value provided.
   */
  public function testRestViewExposedFilter(): void {
    $this->drupalCreateContentType(['type' => 'page']);
    $node0 = $this->drupalCreateNode(['title' => 'Node 1']);
    $node1 = $this->drupalCreateNode(['title' => 'Node 11']);
    $node2 = $this->drupalCreateNode(['title' => 'Node 111']);

    // Test that no filter brings back all three nodes.
    $result = Json::decode($this->drupalGet('test/serialize/node-exposed-filter', ['query' => ['_format' => 'json']]));

    $expected = [
      0 => [
        'nid' => $node0->id(),
        'body' => (string) $node0->body->processed,
      ],
      1 => [
        'nid' => $node1->id(),
        'body' => (string) $node1->body->processed,
      ],
      2 => [
        'nid' => $node2->id(),
        'body' => (string) $node2->body->processed,
      ],
    ];

    $this->assertSame($expected, $result, 'Querying a view with no exposed filter returns all nodes.');

    // Test that title starts with 'Node 11' query finds 2 of the 3 nodes.
    $result = Json::decode($this->drupalGet('test/serialize/node-exposed-filter', ['query' => ['_format' => 'json', 'title' => 'Node 11']]));

    $expected = [
      0 => [
        'nid' => $node1->id(),
        'body' => (string) $node1->body->processed,
      ],
      1 => [
        'nid' => $node2->id(),
        'body' => (string) $node2->body->processed,
      ],
    ];

    $cache_contexts = [
      'languages:language_content',
      'languages:language_interface',
      'theme',
      'request_format',
      'user.node_grants:view',
      'url',
    ];

    $this->assertSame($expected, $result, 'Querying a view with a starts with exposed filter on the title returns nodes whose title starts with value provided.');
    $this->assertCacheContexts($cache_contexts);
  }

  /**
   * Tests multilingual entity rows.
   */
  public function testMulEntityRows(): void {
    // Create some languages.
    ConfigurableLanguage::createFromLangcode('l1')->save();
    ConfigurableLanguage::createFromLangcode('l2')->save();

    // Create an entity with no translations.
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_mul');
    $storage->create(['langcode' => 'l1', 'name' => 'mul-none'])->save();

    // Create some entities with translations.
    $entity = $storage->create(['langcode' => 'l1', 'name' => 'mul-l1-orig']);
    $entity->save();
    $entity->addTranslation('l2', ['name' => 'mul-l1-l2'])->save();
    $entity = $storage->create(['langcode' => 'l2', 'name' => 'mul-l2-orig']);
    $entity->save();
    $entity->addTranslation('l1', ['name' => 'mul-l2-l1'])->save();

    // Get the names of the output.
    $json = $this->drupalGet('test/serialize/translated_entity', ['query' => ['_format' => 'json']]);
    $decoded = $this->container->get('serializer')->decode($json, 'json');
    $names = [];
    foreach ($decoded as $item) {
      $names[] = $item['name'][0]['value'];
    }
    sort($names);

    // Check that the names are correct.
    $expected = ['mul-l1-l2', 'mul-l1-orig', 'mul-l2-l1', 'mul-l2-orig', 'mul-none'];
    $this->assertSame($expected, $names, 'The translated content was found in the JSON.');
  }

}
