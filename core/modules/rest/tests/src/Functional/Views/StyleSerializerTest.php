<?php

namespace Drupal\Tests\rest\Functional\Views;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;
use Symfony\Component\HttpFoundation\Request;

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
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'views_ui',
    'entity_test',
    'hal',
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
   */
  protected $adminUser;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(static::class, ['rest_test_views']);

    $this->adminUser = $this->drupalCreateUser([
      'administer views',
      'administer entity_test content',
      'access user profiles',
      'view test entity',
    ]);

    // Save some entity_test entities.
    for ($i = 1; $i <= 10; $i++) {
      EntityTest::create(['name' => 'test_' . $i, 'user_id' => $this->adminUser->id()])->save();
    }

    $this->enableViewsTestModule();
    $this->renderer = \Drupal::service('renderer');
  }

  /**
   * Checks that the auth options restricts access to a REST views display.
   */
  public function testRestViewsAuthentication() {
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
   * Checks the behavior of the Serializer callback paths and row plugins.
   */
  public function testSerializerResponses() {
    // Test the serialize callback.
    $view = Views::getView('test_serializer_display_field');
    $view->initDisplay();
    $this->executeView($view);

    $actual_json = $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'json']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheTags($view->getCacheTags());
    $this->assertCacheContexts(['languages:language_interface', 'theme', 'request_format']);
    // @todo Due to https://www.drupal.org/node/2352009 we can't yet test the
    // propagation of cache max-age.

    // Test the http Content-type.
    $headers = $this->getSession()->getResponseHeaders();
    $this->assertSame(['application/json'], $headers['Content-Type']);

    $expected = [];
    foreach ($view->result as $row) {
      $expected_row = [];
      foreach ($view->field as $id => $field) {
        $expected_row[$id] = $field->render($row);
      }
      $expected[] = $expected_row;
    }

    $this->assertSame(json_encode($expected), $actual_json, 'The expected JSON output was found.');

    // Test that the rendered output and the preview output are the same.
    $view->destroy();
    $view->setDisplay('rest_export_1');
    // Mock the request content type by setting it on the display handler.
    $view->display_handler->setContentType('json');
    $output = $view->preview();
    $this->assertSame((string) $this->renderer->renderRoot($output), $actual_json, 'The expected JSON preview output was found.');

    // Test a 403 callback.
    $this->drupalGet('test/serialize/denied', ['query' => ['_format' => 'json']]);
    $this->assertSession()->statusCodeEquals(403);

    // Test the entity rows.
    $view = Views::getView('test_serializer_display_entity');
    $view->initDisplay();
    $this->executeView($view);

    // Get the serializer service.
    $serializer = $this->container->get('serializer');

    $entities = [];
    foreach ($view->result as $row) {
      $entities[] = $row->_entity;
    }

    $expected = $serializer->serialize($entities, 'json');

    $actual_json = $this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'json']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame($expected, $actual_json, 'The expected JSON output was found.');
    $expected_cache_tags = $view->getCacheTags();
    $expected_cache_tags[] = 'entity_test_list';
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      $expected_cache_tags = Cache::mergeTags($expected_cache_tags, $entity->getCacheTags());
    }
    $this->assertCacheTags($expected_cache_tags);
    $this->assertCacheContexts(['languages:language_interface', 'theme', 'entity_test_view_grants', 'request_format']);

    $expected = $serializer->serialize($entities, 'hal_json');
    $actual_json = $this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'hal_json']]);
    $this->assertSame($expected, $actual_json, 'The expected HAL output was found.');
    $this->assertCacheTags($expected_cache_tags);

    // Change the format to xml.
    $view->setDisplay('rest_export_1');
    $view->getDisplay()->setOption('style', [
      'type' => 'serializer',
      'options' => [
        'uses_fields' => FALSE,
        'formats' => [
          'xml' => 'xml',
        ],
      ],
    ]);
    $view->save();
    $expected = $serializer->serialize($entities, 'xml');
    $actual_xml = $this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'xml']]);
    $this->assertSame(trim($expected), $actual_xml);
    $this->assertCacheContexts(['languages:language_interface', 'theme', 'entity_test_view_grants', 'request_format']);

    // Allow multiple formats.
    $view->setDisplay('rest_export_1');
    $view->getDisplay()->setOption('style', [
      'type' => 'serializer',
      'options' => [
        'uses_fields' => FALSE,
        'formats' => [
          'xml' => 'xml',
          'json' => 'json',
        ],
      ],
    ]);
    $view->save();
    $expected = $serializer->serialize($entities, 'json');
    $actual_json = $this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'json']]);
    $this->assertSame($expected, $actual_json, 'The expected JSON output was found.');
    $expected = $serializer->serialize($entities, 'xml');
    $actual_xml = $this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'xml']]);
    $this->assertSame(trim($expected), $actual_xml);
  }

  /**
   * Verifies REST export views work on the same path as a page display.
   */
  public function testSharedPagePath() {
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
  public function testSiteMaintenance() {
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
   * Tests REST export with views render caching enabled.
   */
  public function testRestRenderCaching() {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Core\Render\RenderCacheInterface $render_cache */
    $render_cache = \Drupal::service('render_cache');

    // Enable render caching for the views.
    /** @var \Drupal\views\ViewEntityInterface $storage */
    $storage = View::load('test_serializer_display_entity');
    $options = &$storage->getDisplay('default');
    $options['display_options']['cache'] = [
      'type' => 'tag',
    ];
    $storage->save();

    $original = DisplayPluginBase::buildBasicRenderable('test_serializer_display_entity', 'rest_export_1');

    // Ensure that there is no corresponding render cache item yet.
    $original['#cache'] += ['contexts' => []];
    $original['#cache']['contexts'] = Cache::mergeContexts($original['#cache']['contexts'], $this->container->getParameter('renderer.config')['required_cache_contexts']);

    $cache_tags = [
      'config:views.view.test_serializer_display_entity',
      'entity_test:1',
      'entity_test:10',
      'entity_test:2',
      'entity_test:3',
      'entity_test:4',
      'entity_test:5',
      'entity_test:6',
      'entity_test:7',
      'entity_test:8',
      'entity_test:9',
      'entity_test_list',
    ];
    $cache_contexts = [
      'entity_test_view_grants',
      'languages:language_interface',
      'theme',
      'request_format',
    ];

    $this->assertFalse($render_cache->get($original));

    // Request the page, once in XML and once in JSON to ensure that the caching
    // varies by it.
    $result1 = Json::decode($this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'json']]));
    $this->addRequestWithFormat('json');
    $this->assertSession()->responseHeaderEquals('content-type', 'application/json');
    $this->assertCacheContexts($cache_contexts);
    $this->assertCacheTags($cache_tags);
    $this->assertNotEmpty($render_cache->get($original));

    $result_xml = $this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'xml']]);
    $this->addRequestWithFormat('xml');
    $this->assertSession()->responseHeaderEquals('content-type', 'text/xml; charset=UTF-8');
    $this->assertCacheContexts($cache_contexts);
    $this->assertCacheTags($cache_tags);
    $this->assertNotEmpty($render_cache->get($original));

    // Ensure that the XML output is different from the JSON one.
    $this->assertNotEquals($result1, $result_xml);

    // Ensure that the cached page works.
    $result2 = Json::decode($this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'json']]));
    $this->addRequestWithFormat('json');
    $this->assertSession()->responseHeaderEquals('content-type', 'application/json');
    $this->assertEquals($result1, $result2);
    $this->assertCacheContexts($cache_contexts);
    $this->assertCacheTags($cache_tags);
    $this->assertNotEmpty($render_cache->get($original));

    // Create a new entity and ensure that the cache tags are taken over.
    EntityTest::create(['name' => 'test_11', 'user_id' => $this->adminUser->id()])->save();
    $result3 = Json::decode($this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'json']]));
    $this->addRequestWithFormat('json');
    $this->assertSession()->responseHeaderEquals('content-type', 'application/json');
    $this->assertNotEquals($result2, $result3);

    // Add the new entity cache tag and remove the first one, because we just
    // show 10 items in total.
    $cache_tags[] = 'entity_test:11';
    unset($cache_tags[array_search('entity_test:1', $cache_tags)]);

    $this->assertCacheContexts($cache_contexts);
    $this->assertCacheTags($cache_tags);
    $this->assertNotEmpty($render_cache->get($original));
  }

  /**
   * Tests the response format configuration.
   */
  public function testResponseFormatConfiguration() {
    $this->drupalLogin($this->adminUser);

    $style_options = 'admin/structure/views/nojs/display/test_serializer_display_field/rest_export_1/style_options';

    // Ensure a request with no format returns 406 Not Acceptable.
    $this->drupalGet('test/serialize/field');
    $this->assertSession()->responseHeaderEquals('content-type', 'text/html; charset=UTF-8');
    $this->assertSession()->statusCodeEquals(406);

    // Select only 'xml' as an accepted format.
    $this->drupalGet($style_options);
    $this->submitForm(['style_options[formats][xml]' => 'xml'], 'Apply');
    $this->submitForm([], 'Save');

    // Ensure a request for JSON returns 406 Not Acceptable.
    $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'json']]);
    $this->assertSession()->responseHeaderEquals('content-type', 'application/json');
    $this->assertSession()->statusCodeEquals(406);
    // Ensure a request for XML returns 200 OK.
    $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'xml']]);
    $this->assertSession()->responseHeaderEquals('content-type', 'text/xml; charset=UTF-8');
    $this->assertSession()->statusCodeEquals(200);

    // Add 'json' as an accepted format, so we have multiple.
    $this->drupalGet($style_options);
    $this->submitForm(['style_options[formats][json]' => 'json'], 'Apply');
    $this->submitForm([], 'Save');

    // Should return a 406. Emulates a sample Firefox header.
    $this->drupalGet('test/serialize/field', [], ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8']);
    $this->assertSession()->responseHeaderEquals('content-type', 'text/html; charset=UTF-8');
    $this->assertSession()->statusCodeEquals(406);

    // Ensure a request for HTML returns 406 Not Acceptable.
    $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'html']]);
    $this->assertSession()->responseHeaderEquals('content-type', 'text/html; charset=UTF-8');
    $this->assertSession()->statusCodeEquals(406);

    // Ensure a request for JSON returns 200 OK.
    $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'json']]);
    $this->assertSession()->responseHeaderEquals('content-type', 'application/json');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure a request XML returns 200 OK.
    $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'xml']]);
    $this->assertSession()->responseHeaderEquals('content-type', 'text/xml; charset=UTF-8');
    $this->assertSession()->statusCodeEquals(200);

    // Now configure no format, so both serialization formats should be allowed.
    $this->drupalGet($style_options);
    $this->submitForm([
      'style_options[formats][json]' => '0',
      'style_options[formats][xml]' => '0',
    ], 'Apply');

    // Ensure a request for JSON returns 200 OK.
    $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'json']]);
    $this->assertSession()->responseHeaderEquals('content-type', 'application/json');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure a request for XML returns 200 OK.
    $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'xml']]);
    $this->assertSession()->responseHeaderEquals('content-type', 'text/xml; charset=UTF-8');
    $this->assertSession()->statusCodeEquals(200);

    // Should return a 406 for HTML still.
    $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'html']]);
    $this->assertSession()->responseHeaderEquals('content-type', 'text/html; charset=UTF-8');
    $this->assertSession()->statusCodeEquals(406);
  }

  /**
   * Test the field ID alias functionality of the DataFieldRow plugin.
   */
  public function testUIFieldAlias() {
    $this->drupalLogin($this->adminUser);

    // Test the UI settings for adding field ID aliases.
    $this->drupalGet('admin/structure/views/view/test_serializer_display_field/edit/rest_export_1');
    $row_options = 'admin/structure/views/nojs/display/test_serializer_display_field/rest_export_1/row_options';
    $this->assertSession()->linkByHrefExists($row_options);

    // Test an empty string for an alias, this should not be used. This also
    // tests that the form can be submitted with no aliases.
    $this->drupalGet($row_options);
    $this->submitForm(['row_options[field_options][name][alias]' => ''], 'Apply');
    $this->submitForm([], 'Save');

    $view = Views::getView('test_serializer_display_field');
    $view->setDisplay('rest_export_1');
    $this->executeView($view);

    $expected = [];
    foreach ($view->result as $row) {
      $expected_row = [];
      foreach ($view->field as $id => $field) {
        $expected_row[$id] = $field->render($row);
      }
      $expected[] = $expected_row;
    }

    $this->assertEquals($expected, Json::decode($this->drupalGet('test/serialize/field', ['query' => ['_format' => 'json']])));

    // Test a random aliases for fields, they should be replaced.
    $alias_map = [
      'name' => $this->randomMachineName(),
      // Use # to produce an invalid character for the validation.
      'nothing' => '#' . $this->randomMachineName(),
      'created' => 'created',
    ];

    $edit = ['row_options[field_options][name][alias]' => $alias_map['name'], 'row_options[field_options][nothing][alias]' => $alias_map['nothing']];
    $this->drupalGet($row_options);
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('The machine-readable name must contain only letters, numbers, dashes and underscores.');

    // Change the map alias value to a valid one.
    $alias_map['nothing'] = $this->randomMachineName();

    $edit = ['row_options[field_options][name][alias]' => $alias_map['name'], 'row_options[field_options][nothing][alias]' => $alias_map['nothing']];
    $this->drupalGet($row_options);
    $this->submitForm($edit, 'Apply');

    $this->submitForm([], 'Save');

    $view = Views::getView('test_serializer_display_field');
    $view->setDisplay('rest_export_1');
    $this->executeView($view);

    $expected = [];
    foreach ($view->result as $row) {
      $expected_row = [];
      foreach ($view->field as $id => $field) {
        $expected_row[$alias_map[$id]] = $field->render($row);
      }
      $expected[] = $expected_row;
    }

    $this->assertEquals($expected, Json::decode($this->drupalGet('test/serialize/field', ['query' => ['_format' => 'json']])));
  }

  /**
   * Tests the raw output options for row field rendering.
   */
  public function testFieldRawOutput() {
    $this->drupalLogin($this->adminUser);

    // Test the UI settings for adding field ID aliases.
    $this->drupalGet('admin/structure/views/view/test_serializer_display_field/edit/rest_export_1');
    $row_options = 'admin/structure/views/nojs/display/test_serializer_display_field/rest_export_1/row_options';
    $this->assertSession()->linkByHrefExists($row_options);

    // Test an empty string for an alias, this should not be used. This also
    // tests that the form can be submitted with no aliases.
    $values = [
      'row_options[field_options][created][raw_output]' => '1',
      'row_options[field_options][name][raw_output]' => '1',
    ];
    $this->drupalGet($row_options);
    $this->submitForm($values, 'Apply');
    $this->submitForm([], 'Save');

    $view = Views::getView('test_serializer_display_field');
    $view->setDisplay('rest_export_1');
    $this->executeView($view);

    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test');

    // Update the name for each to include a script tag.
    foreach ($storage->loadMultiple() as $entity_test) {
      $name = $entity_test->name->value;
      $entity_test->set('name', "<script>$name</script>");
      $entity_test->save();
    }

    // Just test the raw 'created' value against each row.
    foreach (Json::decode($this->drupalGet('test/serialize/field', ['query' => ['_format' => 'json']])) as $index => $values) {
      $this->assertSame($view->result[$index]->views_test_data_created, $values['created'], 'Expected raw created value found.');
      $this->assertSame($view->result[$index]->views_test_data_name, $values['name'], 'Expected raw name value found.');
    }

    // Test result with an excluded field.
    $view->setDisplay('rest_export_1');
    $view->displayHandlers->get('rest_export_1')->overrideOption('fields', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ],
      'created' => [
        'id' => 'created',
        'exclude' => TRUE,
        'table' => 'views_test_data',
        'field' => 'created',
        'relationship' => 'none',
      ],
    ]);
    $view->save();
    $this->executeView($view);
    foreach (Json::decode($this->drupalGet('test/serialize/field', ['query' => ['_format' => 'json']])) as $index => $values) {
      $this->assertTrue(!isset($values['created']), 'Excluded value not found.');
    }
    // Test that the excluded field is not shown in the row options.
    $this->drupalGet('admin/structure/views/nojs/display/test_serializer_display_field/rest_export_1/row_options');
    $this->assertNoText('created');
  }

  /**
   * Tests the live preview output for json output.
   */
  public function testLivePreview() {
    // We set up a request so it looks like a request in the live preview.
    $request = new Request();
    $request->query->add([MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']);
    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = \Drupal::service('request_stack');
    $request_stack->push($request);

    $view = Views::getView('test_serializer_display_entity');
    $view->setDisplay('rest_export_1');
    $this->executeView($view);

    // Get the serializer service.
    $serializer = $this->container->get('serializer');

    $entities = [];
    foreach ($view->result as $row) {
      $entities[] = $row->_entity;
    }

    $expected = $serializer->serialize($entities, 'json');

    $view->live_preview = TRUE;

    $build = $view->preview();
    $rendered_json = $build['#plain_text'];
    $this->assertArrayNotHasKey('#markup', $build);
    $this->assertSame($expected, $rendered_json, 'Ensure the previewed json is escaped.');
    $view->destroy();

    $expected = $serializer->serialize($entities, 'xml');

    // Change the request format to xml.
    $view->setDisplay('rest_export_1');
    $view->getDisplay()->setOption('style', [
      'type' => 'serializer',
      'options' => [
        'uses_fields' => FALSE,
        'formats' => [
          'xml' => 'xml',
        ],
      ],
    ]);

    $this->executeView($view);
    $build = $view->preview();
    $rendered_xml = $build['#plain_text'];
    $this->assertEquals($expected, $rendered_xml, 'Ensure we preview xml when we change the request format.');
  }

  /**
   * Tests the views interface for REST export displays.
   */
  public function testSerializerViewsUI() {
    $this->drupalLogin($this->adminUser);
    // Click the "Update preview button".
    $this->drupalGet('admin/structure/views/view/test_serializer_display_field/edit/rest_export_1');
    $this->submitForm($edit = [], 'Update preview');
    $this->assertSession()->statusCodeEquals(200);
    // Check if we receive the expected result.
    $result = $this->assertSession()->elementExists('xpath', '//div[@id="views-live-preview"]/pre');
    $json_preview = $result->getText();
    $this->assertSame($json_preview, $this->drupalGet('test/serialize/field', ['query' => ['_format' => 'json']]), 'The expected JSON preview output was found.');
  }

  /**
   * Tests the field row style using fieldapi fields.
   */
  public function testFieldapiField() {
    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode();

    $result = Json::decode($this->drupalGet('test/serialize/node-field', ['query' => ['_format' => 'json']]));
    $this->assertEquals($node->id(), $result[0]['nid']);
    $this->assertEquals($node->body->processed, $result[0]['body']);

    // Make sure that serialized fields are not exposed to XSS.
    $node = $this->drupalCreateNode();
    $node->body = [
      'value' => '<script type="text/javascript">alert("node-body");</script>' . $this->randomMachineName(32),
      'format' => filter_default_format(),
    ];
    $node->save();
    $result = Json::decode($this->drupalGet('test/serialize/node-field', ['query' => ['_format' => 'json']]));
    $this->assertEquals($node->id(), $result[1]['nid']);
    $this->assertStringNotContainsString("<script", $this->getSession()->getPage()->getContent(), "No script tag is present in the raw page contents.");

    $this->drupalLogin($this->adminUser);

    // Add an alias and make the output raw.
    $row_options = 'admin/structure/views/nojs/display/test_serializer_node_display_field/rest_export_1/row_options';

    // Test an empty string for an alias, this should not be used. This also
    // tests that the form can be submitted with no aliases.
    $this->drupalGet($row_options);
    $this->submitForm(['row_options[field_options][title][raw_output]' => '1'], 'Apply');
    $this->submitForm([], 'Save');

    $view = Views::getView('test_serializer_node_display_field');
    $view->setDisplay('rest_export_1');
    $this->executeView($view);

    // Test the raw 'created' value against each row.
    foreach (Json::decode($this->drupalGet('test/serialize/node-field', ['query' => ['_format' => 'json']])) as $index => $values) {
      $this->assertSame($view->result[$index]->_entity->title->value, $values['title'], 'Expected raw title value found.');
    }

    // Test that multiple raw body fields are shown.
    // Set the body field to unlimited cardinality.
    $storage_definition = $node->getFieldDefinition('body')->getFieldStorageDefinition();
    $storage_definition->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $storage_definition->save();

    $this->drupalGet($row_options);
    $this->submitForm(['row_options[field_options][body][raw_output]' => '1'], 'Apply');
    $this->submitForm([], 'Save');

    $node = $this->drupalCreateNode();

    $body = [
      'value' => '<script type="text/javascript">alert("node-body");</script>' . $this->randomMachineName(32),
      'format' => filter_default_format(),
    ];
    // Add two body items.
    $node->body = [$body, $body];
    $node->save();

    $view = Views::getView('test_serializer_node_display_field');
    $view->setDisplay('rest_export_1');
    $this->executeView($view);

    $result = Json::decode($this->drupalGet('test/serialize/node-field', ['query' => ['_format' => 'json']]));
    $this->assertSame($node->body->count(), count($result[2]['body']), 'Expected count of values');
    $this->assertEquals($result[2]['body'], array_map(function ($item) {
      return $item['value'];
    }, $node->body->getValue()), 'Expected raw body values found.');
  }

  /**
   * Tests the "Grouped rows" functionality.
   */
  public function testGroupRows() {
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
  public function testRestViewExposedFilter() {
    $this->drupalCreateContentType(['type' => 'page']);
    $node0 = $this->drupalCreateNode(['title' => 'Node 1']);
    $node1 = $this->drupalCreateNode(['title' => 'Node 11']);
    $node2 = $this->drupalCreateNode(['title' => 'Node 111']);

    // Test that no filter brings back all three nodes.
    $result = Json::decode($this->drupalGet('test/serialize/node-exposed-filter', ['query' => ['_format' => 'json']]));

    $expected = [
      0 => [
        'nid' => $node0->id(),
        'body' => $node0->body->processed,
      ],
      1 => [
        'nid' => $node1->id(),
        'body' => $node1->body->processed,
      ],
      2 => [
        'nid' => $node2->id(),
        'body' => $node2->body->processed,
      ],
    ];

    $this->assertEquals($expected, $result, 'Querying a view with no exposed filter returns all nodes.');

    // Test that title starts with 'Node 11' query finds 2 of the 3 nodes.
    $result = Json::decode($this->drupalGet('test/serialize/node-exposed-filter', ['query' => ['_format' => 'json', 'title' => 'Node 11']]));

    $expected = [
      0 => [
        'nid' => $node1->id(),
        'body' => $node1->body->processed,
      ],
      1 => [
        'nid' => $node2->id(),
        'body' => $node2->body->processed,
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

    $this->assertEquals($expected, $result, 'Querying a view with a starts with exposed filter on the title returns nodes whose title starts with value provided.');
    $this->assertCacheContexts($cache_contexts);
  }

  /**
   * Test multilingual entity rows.
   */
  public function testMulEntityRows() {
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
    $decoded = $this->container->get('serializer')->decode($json, 'hal_json');
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
