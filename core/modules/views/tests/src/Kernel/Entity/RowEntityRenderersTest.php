<?php

namespace Drupal\Tests\views\Kernel\Entity;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the entity row renderers.
 *
 * @group views
 * @see \Drupal\views\Entity\Render\RendererBase
 */
class RowEntityRenderersTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'filter',
    'text',
    'node',
    'user',
    'language',
    'views_test_language',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'test_entity_row_renderers',
    'test_entity_row_renderers_revisions_base',
  ];

  /**
   * An array of added languages.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * An array of titles for each node per language.
   *
   * @var array
   */
  protected $expected;

  /**
   * The author of the test content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testAuthor;

  /**
   * An array of IDs of the test content.
   *
   * @var array[]
   */
  protected $testIds;

  /**
   * @var array
   */
  protected array $values;

  /**
   * @var array
   */
  protected array $ids;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node', 'language']);

    $this->langcodes = [\Drupal::languageManager()->getDefaultLanguage()->getId()];
    for ($i = 0; $i < 2; $i++) {
      $langcode = 'l' . $i;
      $this->langcodes[] = $langcode;
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    $this->testAuthor = User::create([
      'name' => 'foo',
    ]);
    $this->testAuthor->save();

    // Make sure we do not try to render non-existing user data.
    $node_type = NodeType::create(['type' => 'test']);
    $node_type->setDisplaySubmitted(FALSE);
    $node_type->save();

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'field_name' => 'field_reference',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'test',
      'field_name' => 'field_reference',
    ])->save();

    $this->values = [];
    $this->ids = [];
    $controller = \Drupal::entityTypeManager()->getStorage('node');
    $langcode_index = 0;

    for ($i = 0; $i < count($this->langcodes); $i++) {
      // Create a node with a different default language each time.
      $default_langcode = $this->langcodes[$langcode_index++];
      $node = $controller->create(['type' => 'test', 'uid' => $this->testAuthor->id(), 'langcode' => $default_langcode]);

      // Ensure the default language is processed first.
      $langcodes = array_merge([$default_langcode], array_diff($this->langcodes, [$default_langcode]));

      foreach ($langcodes as $langcode) {
        // Ensure we have a predictable result order.
        $this->values[$i][$langcode] = $i . '-' . $langcode . '-' . $this->randomMachineName();

        if ($langcode != $default_langcode) {
          $node->addTranslation($langcode, [
            'title' => $this->values[$i][$langcode],
            'field_reference' => ($i + 1) % 3 + 1,
          ]);
        }
        else {
          $node->setTitle($this->values[$i][$langcode]);
          $node->set('field_reference', ($i + 1) % 3 + 1);
        }

        $node->save();

        $this->ids[] = [
          'nid' => $node->id(),
          'uid' => $this->testAuthor->id(),
        ];
      }
    }
    ViewTestData::createTestViews(static::class, ['views_test_config']);
  }

  /**
   * Tests the entity row renderers.
   */
  public function testEntityRenderers() {
    $this->checkLanguageRenderers('page_1', $this->values);
  }

  /**
   * Tests the field row renderers.
   */
  public function testFieldRenderers() {
    $this->checkLanguageRenderers('page_2', $this->values);
  }

  /**
   * Tests the entity row renderers for relationships.
   */
  public function testEntityRenderersRelationship() {
    $this->checkLanguageRenderersRelationship('page_3', $this->values);
  }

  /**
   * Tests the field row renderers for relationships.
   */
  public function testFieldRenderersRelationship() {
    $this->checkLanguageRenderersRelationship('page_4', $this->values);
  }

  /**
   * Tests the row renderer with a revision base table.
   */
  public function testRevisionBaseTable() {
    $view = Views::getView('test_entity_row_renderers_revisions_base');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->ids, ['nid' => 'nid', 'uid' => 'uid']);
  }

  /**
   * Checks that the language renderer configurations work as expected.
   *
   * @param string $display
   *   Name of display to test with.
   * @param array $values
   *   An array of node information which are each an array of node titles
   *   associated with language keys appropriate for the translation of that
   *   node.
   */
  protected function checkLanguageRenderers($display, $values) {
    $expected = [
      $values[0]['en'],
      $values[0]['en'],
      $values[0]['en'],
      $values[1]['en'],
      $values[1]['en'],
      $values[1]['en'],
      $values[2]['en'],
      $values[2]['en'],
      $values[2]['en'],
    ];
    $this->assertTranslations($display, '***LANGUAGE_language_content***', $expected, 'The current language renderer behaves as expected.');

    $expected = [
      $values[0]['en'],
      $values[0]['en'],
      $values[0]['en'],
      $values[1]['l0'],
      $values[1]['l0'],
      $values[1]['l0'],
      $values[2]['l1'],
      $values[2]['l1'],
      $values[2]['l1'],
    ];
    $this->assertTranslations($display, '***LANGUAGE_entity_default***', $expected, 'The default language renderer behaves as expected.');

    $expected = [
      $values[0]['en'],
      $values[0]['l0'],
      $values[0]['l1'],
      $values[1]['en'],
      $values[1]['l0'],
      $values[1]['l1'],
      $values[2]['en'],
      $values[2]['l0'],
      $values[2]['l1'],
    ];
    $this->assertTranslations($display, '***LANGUAGE_entity_translation***', $expected, 'The translation language renderer behaves as expected.');

    $expected = [
      $values[0][$this->langcodes[0]],
      $values[0][$this->langcodes[0]],
      $values[0][$this->langcodes[0]],
      $values[1][$this->langcodes[0]],
      $values[1][$this->langcodes[0]],
      $values[1][$this->langcodes[0]],
      $values[2][$this->langcodes[0]],
      $values[2][$this->langcodes[0]],
      $values[2][$this->langcodes[0]],
    ];
    $this->assertTranslations($display, '***LANGUAGE_site_default***', $expected, 'The site default language renderer behaves as expected.');

    $expected = [
      $values[0]['l0'],
      $values[0]['l0'],
      $values[0]['l0'],
      $values[1]['l0'],
      $values[1]['l0'],
      $values[1]['l0'],
      $values[2]['l0'],
      $values[2]['l0'],
      $values[2]['l0'],
    ];
    $this->assertTranslations($display, 'l0', $expected, 'The language specific renderer behaves as expected.');
  }

  /**
   * Checks language renderer configurations work with relationships.
   *
   * The Views with relationships filter and sort a little differently.
   * First, they filter such that we only consider English nodes when finding
   * relationships. If we didn't do this, we'd get 27 results in these Views,
   * which is just way too much. Second, after sorting by the node title, we
   * sort by the title of the referenced translation to have a predictable
   * order.
   *
   * @param string $display
   *   Name of display to test with.
   * @param array $values
   *   An array of node information which are each an array of node titles
   *   associated with language keys appropriate for the translation of that
   *   node.
   */
  protected function checkLanguageRenderersRelationship($display, $values) {
    $expected = [
      $values[1]['en'],
      $values[1]['en'],
      $values[1]['en'],
      $values[2]['en'],
      $values[2]['en'],
      $values[2]['en'],
      $values[0]['en'],
      $values[0]['en'],
      $values[0]['en'],
    ];
    $this->assertTranslations($display, '***LANGUAGE_language_content***', $expected, 'The current language renderer behaves as expected.');

    $expected = [
      $values[1]['l0'],
      $values[1]['l0'],
      $values[1]['l0'],
      $values[2]['l1'],
      $values[2]['l1'],
      $values[2]['l1'],
      $values[0]['en'],
      $values[0]['en'],
      $values[0]['en'],
    ];
    $this->assertTranslations($display, '***LANGUAGE_entity_default***', $expected, 'The default language renderer behaves as expected.');

    $expected = [
      $values[1]['en'],
      $values[1]['l0'],
      $values[1]['l1'],
      $values[2]['en'],
      $values[2]['l0'],
      $values[2]['l1'],
      $values[0]['en'],
      $values[0]['l0'],
      $values[0]['l1'],
    ];
    $this->assertTranslations($display, '***LANGUAGE_entity_translation***', $expected, 'The translation language renderer behaves as expected.');

    $expected = [
      $values[1][$this->langcodes[0]],
      $values[1][$this->langcodes[0]],
      $values[1][$this->langcodes[0]],
      $values[2][$this->langcodes[0]],
      $values[2][$this->langcodes[0]],
      $values[2][$this->langcodes[0]],
      $values[0][$this->langcodes[0]],
      $values[0][$this->langcodes[0]],
      $values[0][$this->langcodes[0]],
    ];
    $this->assertTranslations($display, '***LANGUAGE_site_default***', $expected, 'The site default language renderer behaves as expected.');

    $expected = [
      $values[1]['l0'],
      $values[1]['l0'],
      $values[1]['l0'],
      $values[2]['l0'],
      $values[2]['l0'],
      $values[2]['l0'],
      $values[0]['l0'],
      $values[0]['l0'],
      $values[0]['l0'],
    ];
    $this->assertTranslations($display, 'l0', $expected, 'The language specific renderer behaves as expected.');
  }

  /**
   * Checks that the view results match the expected values.
   *
   * @param string $display
   *   Name of display to test with.
   * @param string $renderer_id
   *   The id of the renderer to be tested.
   * @param array $expected
   *   An array of expected title translation values, one for each result row.
   * @param string $message
   *   (optional) A message to display with the assertion.
   *
   * @internal
   */
  protected function assertTranslations(string $display, string $renderer_id, array $expected, string $message = ''): void {
    $view = Views::getView('test_entity_row_renderers');
    $view->storage->invalidateCaches();
    $view->setDisplay($display);
    $view->getDisplay()->setOption('rendering_language', $renderer_id);
    $view->preview();

    $result = FALSE;
    foreach ($expected as $index => $expected_output) {
      if (!empty($view->result[$index])) {
        $build = $view->rowPlugin->render($view->result[$index]);
        $output = \Drupal::service('renderer')->renderRoot($build);
        $result = str_contains($output, $expected_output);
        if (!$result) {
          break;
        }
      }
      else {
        $result = FALSE;
        break;
      }
    }

    $this->assertTrue($result, $message);
  }

}
