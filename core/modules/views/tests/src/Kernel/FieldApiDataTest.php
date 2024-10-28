<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\Component\Render\MarkupInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Views;

/**
 * Tests the Field Views data.
 *
 * @group views
 */
class FieldApiDataTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'language',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_field_config_translation_filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
  }

  /**
   * Unit testing the views data structure.
   *
   * We check data structure for both node and node revision tables.
   */
  public function testViewsData(): void {
    $field_storage_string = FieldStorageConfig::create([
      'field_name' => 'field_string',
      'entity_type' => 'node',
      'type' => 'string',
    ]);
    $field_storage_string->save();

    $field_storage_string_long = FieldStorageConfig::create([
      'field_name' => 'field_string_long',
      'entity_type' => 'node',
      'type' => 'string_long',
    ]);
    $field_storage_string_long->save();

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Attach the field to nodes.
    FieldConfig::create([
      'field_name' => 'field_string',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'GiraffeA" label',
    ])->save();

    // Attach the string_long field to the page node type.
    FieldConfig::create([
      'field_name' => 'field_string_long',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'string_long label',
    ])->save();

    // Attach the same field to a different bundle with a different label.
    FieldConfig::create([
      'field_name' => 'field_string',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'GiraffeB" label',
    ])->save();

    // Now create some example nodes/users for the view result.
    for ($i = 0; $i < 5; $i++) {
      $edit = [
        'field_string' => [(['value' => $this->randomMachineName()])],
      ];
      $nodes[] = Node::create(['type' => 'page'] + $edit);
    }

    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->getTableMapping();

    $current_table = $table_mapping->getDedicatedDataTableName($field_storage_string);
    $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage_string);
    $data = $this->getViewsData();

    $this->assertArrayHasKey($current_table, $data);
    $this->assertArrayHasKey($revision_table, $data);

    // The node field should join against node_field_data.
    $this->assertArrayHasKey('node_field_data', $data[$current_table]['table']['join']);
    $this->assertArrayHasKey('node_field_revision', $data[$revision_table]['table']['join']);

    $expected_join = [
      'table' => $current_table,
      'left_field' => 'nid',
      'field' => 'entity_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ['left_field' => 'langcode', 'field' => 'langcode'],
      ],
    ];
    $this->assertSame($expected_join, $data[$current_table]['table']['join']['node_field_data']);
    $expected_join = [
      'table' => $revision_table,
      'left_field' => 'vid',
      'field' => 'revision_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ['left_field' => 'langcode', 'field' => 'langcode'],
      ],
    ];
    $this->assertSame($expected_join, $data[$revision_table]['table']['join']['node_field_revision']);

    // Test click sortable for string field.
    $this->assertTrue($data[$current_table][$field_storage_string->getName()]['field']['click sortable']);
    // Click sort should only be on the primary field.
    $this->assertArrayNotHasKey($field_storage_string->getName(), $data[$revision_table]);
    // Test click sortable for long text field.
    $data_long = $this->getViewsData('field_string_long');
    $current_table_long = $table_mapping->getDedicatedDataTableName($field_storage_string_long);
    $this->assertTrue($data_long[$current_table_long][$field_storage_string_long->getName()]['field']['click sortable']);

    $this->assertInstanceOf(MarkupInterface::class, $data[$current_table][$field_storage_string->getName()]['help']);
    $this->assertEquals('Appears in: page, article. Also known as: Content: GiraffeB&quot; label', $data[$current_table][$field_storage_string->getName()]['help']);

    $this->assertInstanceOf(MarkupInterface::class, $data[$current_table][$field_storage_string->getName() . '_value']['help']);
    $this->assertEquals('Appears in: page, article. Also known as: Content: GiraffeA&quot; label (field_string)', $data[$current_table][$field_storage_string->getName() . '_value']['help']);

    // Since each label is only used once, views_entity_field_label() will
    // return a label using alphabetical sorting.
    $this->assertEquals('GiraffeA&quot; label (field_string)', $data[$current_table][$field_storage_string->getName() . '_value']['title']);

    // Attach the same field to a different bundle with a different label.
    NodeType::create([
      'type' => 'news',
      'name' => 'News',
    ])->save();
    FieldConfig::create([
      'field_name' => $field_storage_string->getName(),
      'entity_type' => 'node',
      'bundle' => 'news',
      'label' => 'GiraffeB" label',
    ])->save();
    $this->container->get('views.views_data')->clear();
    $data = $this->getViewsData();

    // Now the 'GiraffeB&quot; label' is used twice and therefore will be
    // selected by views_entity_field_label().
    $this->assertEquals('GiraffeB&quot; label (field_string)', $data[$current_table][$field_storage_string->getName() . '_value']['title']);
    $this->assertInstanceOf(MarkupInterface::class, $data[$current_table][$field_storage_string->getName()]['help']);
    $this->assertEquals('Appears in: page, article, news. Also known as: Content: GiraffeA&quot; label', $data[$current_table][$field_storage_string->getName()]['help']);
  }

  /**
   * Gets the views data for the field created in setUp().
   *
   * @param string $field_storage_key
   *   (optional) The optional field name.
   *
   * @return array
   *   Views data.
   */
  protected function getViewsData($field_storage_key = 'field_string'): array {
    $views_data = $this->container->get('views.views_data');
    $data = [];

    // Check the table and the joins of the first field. Attached to node only.
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->container->get('entity_type.manager')->getStorage('node')->getTableMapping();
    $field_storage = FieldStorageConfig::loadByName('node', $field_storage_key);
    $current_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage);
    $data[$current_table] = $views_data->get($current_table);
    $data[$revision_table] = $views_data->get($revision_table);
    return $data;
  }

  /**
   * Tests filtering entries with different translatability.
   */
  public function testEntityFieldFilter(): void {
    NodeType::create([
      'type' => 'bundle1',
      'name' => 'Bundle One',
    ])->save();
    NodeType::create([
      'type' => 'bundle2',
      'name' => 'Bundle Two',
    ])->save();

    // Create some example content.
    ConfigurableLanguage::createFromLangcode('es')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

    ContentLanguageSettings::loadByEntityTypeBundle('node', 'bundle1')
      ->setDefaultLangcode('es')
      ->setLanguageAlterable(TRUE)
      ->save();
    ContentLanguageSettings::loadByEntityTypeBundle('node', 'bundle2')
      ->setDefaultLangcode('es')
      ->setLanguageAlterable(TRUE)
      ->save();

    $field_translation_map = [
      1 => ['bundle1' => TRUE, 'bundle2' => TRUE],
      2 => ['bundle1' => FALSE, 'bundle2' => FALSE],
      3 => ['bundle1' => TRUE, 'bundle2' => FALSE],
    ];

    for ($i = 1; $i < 4; $i++) {
      $field_name = "field_name_$i";
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'string',
      ])->save();

      foreach (['bundle1', 'bundle2'] as $bundle) {
        FieldConfig::create([
          'field_name' => $field_name,
          'entity_type' => 'node',
          'bundle' => $bundle,
          'translatable' => $field_translation_map[$i][$bundle],
        ])->save();
      }
    }

    $node1 = Node::create([
      'title' => 'Test title bundle1',
      'type' => 'bundle1',
      'langcode' => 'es',
      'field_name_1' => 'field name 1: es',
      'field_name_2' => 'field name 2: es',
      'field_name_3' => 'field name 3: es',
    ]);
    $node1->save();
    $node1->addTranslation('fr', [
      'title' => $node1->title->value,
      'field_name_1' => 'field name 1: fr',
      'field_name_3' => 'field name 3: fr',
    ])->save();

    $node2 = Node::create([
      'title' => 'Test title bundle2',
      'type' => 'bundle2',
      'langcode' => 'es',
      'field_name_1' => 'field name 1: es',
      'field_name_2' => 'field name 2: es',
      'field_name_3' => 'field name 3: es',
    ]);
    $node2->save();

    $node2->addTranslation('fr', [
      'title' => $node2->title->value,
      'field_name_1' => 'field name 1: fr',
    ])->save();

    $map = [
      'nid' => 'nid',
      'langcode' => 'langcode',
    ];

    $view = Views::getView('test_field_config_translation_filter');

    // Filter by 'field name 1: es'.
    $view->setDisplay('embed_1');
    $this->executeView($view);
    $expected = [
      [
        'nid' => $node1->id(),
        'langcode' => 'es',
      ],
      [
        'nid' => $node2->id(),
        'langcode' => 'es',
      ],
    ];

    $this->assertIdenticalResultset($view, $expected, $map);
    $view->destroy();

    // Filter by 'field name 1: fr'.
    $view->setDisplay('embed_2');
    $this->executeView($view);
    $expected = [
      [
        'nid' => $node1->id(),
        'langcode' => 'fr',
      ],
      [
        'nid' => $node2->id(),
        'langcode' => 'fr',
      ],
    ];

    $this->assertIdenticalResultset($view, $expected, $map);
    $view->destroy();

    // Filter by 'field name 2: es'.
    $view->setDisplay('embed_3');
    $this->executeView($view);
    $expected = [
      [
        'nid' => $node1->id(),
        'langcode' => 'es',
      ],
      [
        'nid' => $node1->id(),
        'langcode' => 'fr',
      ],
      [
        'nid' => $node2->id(),
        'langcode' => 'es',
      ],
      [
        'nid' => $node2->id(),
        'langcode' => 'fr',
      ],
    ];

    $this->assertIdenticalResultset($view, $expected, $map);
    $view->destroy();

    // Filter by 'field name 2: fr', which doesn't exist.
    $view->setDisplay('embed_4');
    $this->executeView($view);
    $expected = [];

    $this->assertIdenticalResultset($view, $expected, $map);
    $view->destroy();

    // Filter by 'field name 3: es'.
    $view->setDisplay('embed_5');
    $this->executeView($view);
    $expected = [
      [
        'nid' => $node1->id(),
        'langcode' => 'es',
      ],
      [
        'nid' => $node2->id(),
        'langcode' => 'es',
      ],
      // Why is this one returned?
      [
        'nid' => $node2->id(),
        'langcode' => 'fr',
      ],
    ];

    $this->assertIdenticalResultset($view, $expected, $map);
    $view->destroy();

    // Filter by 'field name 3: fr'.
    $view->setDisplay('embed_6');
    $this->executeView($view);
    $expected = [
      [
        'nid' => $node1->id(),
        'langcode' => 'fr',
      ],
    ];

    $this->assertIdenticalResultset($view, $expected, $map);
    $view->destroy();
  }

}
