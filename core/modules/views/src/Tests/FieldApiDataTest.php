<?php

namespace Drupal\views\Tests;

use Drupal\Component\Render\MarkupInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Tests\Views\FieldTestBase;
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
class FieldApiDataTest extends FieldTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_field_config_translation_filter'];

  /**
   * The nodes used by the translation filter tests.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $translationNodes;

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $field_names = $this->setUpFieldStorages(4);

    // Attach the field to nodes only.
    $field = [
      'field_name' => $field_names[0],
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'GiraffeA" label'
    ];
    FieldConfig::create($field)->save();

    // Attach the same field to a different bundle with a different label.
    $this->drupalCreateContentType(['type' => 'article']);
    FieldConfig::create([
      'field_name' => $field_names[0],
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'GiraffeB" label'
    ])->save();

    // Now create some example nodes/users for the view result.
    for ($i = 0; $i < 5; $i++) {
      $edit = [
        $field_names[0] => [(['value' => $this->randomMachineName()])],
      ];
      $nodes[] = $this->drupalCreateNode($edit);
    }

    $bundles = [];
    $bundles[] = $bundle = NodeType::create(['type' => 'bundle1']);
    $bundle->save();
    $bundles[] = $bundle = NodeType::create(['type' => 'bundle2']);
    $bundle->save();

    // Make the first field translatable on all bundles.
    $field = FieldConfig::create([
      'field_name' => $field_names[1],
      'entity_type' => 'node',
      'bundle' => $bundles[0]->id(),
      'translatable' => TRUE,
    ]);
    $field->save();
    $field = FieldConfig::create([
      'field_name' => $field_names[1],
      'entity_type' => 'node',
      'bundle' => $bundles[1]->id(),
      'translatable' => TRUE,
    ]);
    $field->save();

    // Make the second field not translatable on any bundle.
    $field = FieldConfig::create([
      'field_name' => $field_names[2],
      'entity_type' => 'node',
      'bundle' => $bundles[0]->id(),
      'translatable' => FALSE,
    ]);
    $field->save();
    $field = FieldConfig::create([
      'field_name' => $field_names[2],
      'entity_type' => 'node',
      'bundle' => $bundles[1]->id(),
      'translatable' => FALSE,
    ]);
    $field->save();

    // Make the last field translatable on some bundles.
    $field = FieldConfig::create([
      'field_name' => $field_names[3],
      'entity_type' => 'node',
      'bundle' => $bundles[0]->id(),
      'translatable' => TRUE,
    ]);
    $field->save();
    $field = FieldConfig::create([
      'field_name' => $field_names[3],
      'entity_type' => 'node',
      'bundle' => $bundles[1]->id(),
      'translatable' => FALSE,
    ]);
    $field->save();

    // Create some example content.
    ConfigurableLanguage::create([
      'id' => 'es',
    ])->save();
    ConfigurableLanguage::create([
      'id' => 'fr',
    ])->save();

    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', $bundles[0]->id());
    $config->setDefaultLangcode('es')
      ->setLanguageAlterable(TRUE)
      ->save();
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', $bundles[1]->id());
    $config->setDefaultLangcode('es')
      ->setLanguageAlterable(TRUE)
      ->save();

    $node = Node::create([
      'title' => 'Test title ' . $bundles[0]->id(),
      'type' => $bundles[0]->id(),
      'langcode' => 'es',
      $field_names[1] => 'field name 1: es',
      $field_names[2] => 'field name 2: es',
      $field_names[3] => 'field name 3: es',
    ]);
    $node->save();
    $this->translationNodes[] = $node;
    $translation = $node->addTranslation('fr');
    $translation->{$field_names[1]}->value = 'field name 1: fr';
    $translation->{$field_names[3]}->value = 'field name 3: fr';
    $translation->title->value = $node->title->value;
    $translation->save();

    $node = Node::create([
      'title' => 'Test title ' . $bundles[1]->id(),
      'type' => $bundles[1]->id(),
      'langcode' => 'es',
      $field_names[1] => 'field name 1: es',
      $field_names[2] => 'field name 2: es',
      $field_names[3] => 'field name 3: es',
    ]);
    $node->save();
    $this->translationNodes[] = $node;
    $translation = $node->addTranslation('fr');
    $translation->{$field_names[1]}->value = 'field name 1: fr';
    $translation->title->value = $node->title->value;
    $translation->save();

  }

  /**
   * Unit testing the views data structure.
   *
   * We check data structure for both node and node revision tables.
   */
  public function testViewsData() {
    $table_mapping = \Drupal::entityManager()->getStorage('node')->getTableMapping();
    $field_storage = $this->fieldStorages[0];
    $current_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage);
    $data = $this->getViewsData();

    $this->assertTrue(isset($data[$current_table]));
    $this->assertTrue(isset($data[$revision_table]));
    // The node field should join against node_field_data.
    $this->assertTrue(isset($data[$current_table]['table']['join']['node_field_data']));
    $this->assertTrue(isset($data[$revision_table]['table']['join']['node_field_revision']));

    $expected_join = [
      'table' => $current_table,
      'left_field' => 'nid',
      'field' => 'entity_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ['left_field' => 'langcode', 'field' => 'langcode'],
      ],
    ];
    $this->assertEqual($expected_join, $data[$current_table]['table']['join']['node_field_data']);
    $expected_join = [
      'table' => $revision_table,
      'left_field' => 'vid',
      'field' => 'revision_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ['left_field' => 'langcode', 'field' => 'langcode'],
      ],
    ];
    $this->assertEqual($expected_join, $data[$revision_table]['table']['join']['node_field_revision']);

    // Test click sortable.
    $this->assertTrue($data[$current_table][$field_storage->getName()]['field']['click sortable'], 'String field is click sortable.');
    // Click sort should only be on the primary field.
    $this->assertTrue(empty($data[$revision_table][$field_storage->getName()]['field']['click sortable']), 'Non-primary fields are not click sortable');

    $this->assertTrue($data[$current_table][$field_storage->getName()]['help'] instanceof MarkupInterface);
    $this->assertEqual($data[$current_table][$field_storage->getName()]['help'], 'Appears in: page, article. Also known as: Content: GiraffeB&quot; label');

    $this->assertTrue($data[$current_table][$field_storage->getName() . '_value']['help'] instanceof MarkupInterface);
    $this->assertEqual($data[$current_table][$field_storage->getName() . '_value']['help'], 'Appears in: page, article. Also known as: Content: GiraffeA&quot; label (field_name_0)');

    // Since each label is only used once, views_entity_field_label() will
    // return a label using alphabetical sorting.
    $this->assertEqual('GiraffeA&quot; label (field_name_0)', $data[$current_table][$field_storage->getName() . '_value']['title']);

    // Attach the same field to a different bundle with a different label.
    $this->drupalCreateContentType(['type' => 'news']);
    FieldConfig::create([
      'field_name' => $this->fieldStorages[0]->getName(),
      'entity_type' => 'node',
      'bundle' => 'news',
      'label' => 'GiraffeB" label'
    ])->save();
    $this->container->get('views.views_data')->clear();
    $data = $this->getViewsData();

    // Now the 'GiraffeB&quot; label' is used twice and therefore will be
    // selected by views_entity_field_label().
    $this->assertEqual('GiraffeB&quot; label (field_name_0)', $data[$current_table][$field_storage->getName() . '_value']['title']);
    $this->assertTrue($data[$current_table][$field_storage->getName()]['help'] instanceof MarkupInterface);
    $this->assertEqual($data[$current_table][$field_storage->getName()]['help'], 'Appears in: page, article, news. Also known as: Content: GiraffeA&quot; label');
  }

  /**
   * Gets the views data for the field created in setUp().
   *
   * @return array
   */
  protected function getViewsData() {
    $views_data = $this->container->get('views.views_data');
    $data = [];

    // Check the table and the joins of the first field.
    // Attached to node only.
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = \Drupal::entityManager()->getStorage('node')->getTableMapping();
    $current_table = $table_mapping->getDedicatedDataTableName($this->fieldStorages[0]);
    $revision_table = $table_mapping->getDedicatedRevisionTableName($this->fieldStorages[0]);
    $data[$current_table] = $views_data->get($current_table);
    $data[$revision_table] = $views_data->get($revision_table);
    return $data;
  }

  /**
   * Tests filtering entries with different translatabilty.
   */
  public function testEntityFieldFilter() {
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
        'nid' => $this->translationNodes[0]->id(),
        'langcode' => 'es',
      ],
      [
        'nid' => $this->translationNodes[1]->id(),
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
        'nid' => $this->translationNodes[0]->id(),
        'langcode' => 'fr',
      ],
      [
        'nid' => $this->translationNodes[1]->id(),
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
        'nid' => $this->translationNodes[0]->id(),
        'langcode' => 'es',
      ],
      [
        'nid' => $this->translationNodes[0]->id(),
        'langcode' => 'fr',
      ],
      [
        'nid' => $this->translationNodes[1]->id(),
        'langcode' => 'es',
      ],
      [
        'nid' => $this->translationNodes[1]->id(),
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
        'nid' => $this->translationNodes[0]->id(),
        'langcode' => 'es',
      ],
      [
        'nid' => $this->translationNodes[1]->id(),
        'langcode' => 'es',
      ],
      // Why is this one returned?
      [
        'nid' => $this->translationNodes[1]->id(),
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
        'nid' => $this->translationNodes[0]->id(),
        'langcode' => 'fr',
      ],
    ];

    $this->assertIdenticalResultset($view, $expected, $map);
    $view->destroy();
  }

}
