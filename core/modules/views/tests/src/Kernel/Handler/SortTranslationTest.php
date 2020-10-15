<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests sorting on translatable and not translatable fields.
 *
 * @group views
 */
class SortTranslationTest extends ViewsKernelTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = [
    'test_view_sort_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);
    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    // $this->installConfig('node');
    $this->container->get('kernel')->rebuildContainer();

    $node_type = NodeType::create(['type' => 'article']);
    $node_type->save();

    FieldStorageConfig::create([
      'field_name' => 'text',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => 'text',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Translated text',
      'translatable' => TRUE,
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'weight',
      'entity_type' => 'node',
      'type' => 'integer',
    ])->save();

    FieldConfig::create([
      'field_name' => 'weight',
      'entity_type' => 'node',
      'bundle' => 'article',
      'translatable' => FALSE,
    ])->save();

    for ($i = 0; $i < 3; $i++) {
      $node = Node::create([
        'type' => 'article',
        'title' => 'Title en ' . $i,
        'weight' => ['value' => 3 - $i],
        'text' => ['value' => 'moo en ' . $i],
        'langcode' => 'en',
      ]);
      $node->save();

      $translation = $node->addTranslation('de');
      $translation->title->value = 'Title DE ' . $i;
      $translation->text->value = 'moo DE ' . $i;
      $translation->save();
    }
  }

  /**
   * Test sorting on an untranslated field.
   */
  public function testSortbyUntranslatedIntegerField() {
    $map = [
      'nid' => 'nid',
      'node_field_data_langcode' => 'langcode',
    ];

    $view = Views::getView('test_view_sort_translation');
    $view->setDisplay('default');
    $this->executeView($view);

    // With ascending sort, the nodes should come out in reverse order.
    $expected = [
      [
        'nid' => 3,
        'langcode' => 'en',
      ],
      [
        'nid' => 2,
        'langcode' => 'en',
      ],
      [
        'nid' => 1,
        'langcode' => 'en',
      ],
    ];
    $this->assertIdenticalResultset($view, $expected, $map);
    $view->destroy();

    $view = Views::getView('test_view_sort_translation');
    $view->setDisplay('display_de');
    $this->executeView($view);

    $expected = [
      [
        'nid' => 3,
        'langcode' => 'de',
      ],
      [
        'nid' => 2,
        'langcode' => 'de',
      ],
      [
        'nid' => 1,
        'langcode' => 'de',
      ],
    ];

    // The weight field is not translated, we sort by it so the nodes
    // should come out in the same order in both languages.
    $this->assertIdenticalResultset($view, $expected, $map);
    $view->destroy();
  }

}
