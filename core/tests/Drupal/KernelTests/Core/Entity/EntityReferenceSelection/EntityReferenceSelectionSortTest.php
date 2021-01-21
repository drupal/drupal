<?php

namespace Drupal\KernelTests\Core\Entity\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests sorting referenced items.
 *
 * @group entity_reference
 */
class EntityReferenceSelectionSortTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node'];

  protected function setUp(): void {
    parent::setUp();

    // Create an Article node type.
    $article = NodeType::create([
      'type' => 'article',
    ]);
    $article->save();

    // Test as a non-admin.
    $normal_user = $this->createUser([], ['access content']);
    \Drupal::currentUser()->setAccount($normal_user);
  }

  /**
   * Assert sorting by field and property.
   */
  public function testSort() {
    // Add text field to entity, to sort by.
    FieldStorageConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'type' => 'text',
      'entity_types' => ['node'],
    ])->save();

    FieldConfig::create([
      'label' => 'Text Field',
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'bundle' => 'article',
      'settings' => [],
      'required' => FALSE,
    ])->save();

    // Build a set of test data.
    $node_values = [
      'published1' => [
        'type' => 'article',
        'status' => 1,
        'title' => 'Node published1 (<&>)',
        'uid' => 1,
        'field_text' => [
          [
            'value' => 1,
          ],
        ],
      ],
      'published2' => [
        'type' => 'article',
        'status' => 1,
        'title' => 'Node published2 (<&>)',
        'uid' => 1,
        'field_text' => [
          [
            'value' => 2,
          ],
        ],
      ],
    ];

    $nodes = [];
    $node_labels = [];
    foreach ($node_values as $key => $values) {
      $node = Node::create($values);
      $node->save();
      $nodes[$key] = $node;
      $node_labels[$key] = Html::escape($node->label());
    }

    $selection_options = [
      'target_type' => 'node',
      'handler' => 'default',
      'target_bundles' => NULL,
      // Add sorting.
      'sort' => [
        'field' => 'field_text.value',
        'direction' => 'DESC',
      ],
    ];
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getInstance($selection_options);

    // Not only assert the result, but make sure the keys are sorted as
    // expected.
    $result = $handler->getReferenceableEntities();
    $expected_result = [
      $nodes['published2']->id() => $node_labels['published2'],
      $nodes['published1']->id() => $node_labels['published1'],
    ];
    $this->assertSame($expected_result, $result['article'], 'Query sorted by field returned expected values.');

    // Assert sort by base field.
    $selection_options['sort'] = [
      'field' => 'nid',
      'direction' => 'ASC',
    ];
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getInstance($selection_options);
    $result = $handler->getReferenceableEntities();
    $expected_result = [
      $nodes['published1']->id() => $node_labels['published1'],
      $nodes['published2']->id() => $node_labels['published2'],
    ];
    $this->assertSame($expected_result, $result['article'], 'Query sorted by property returned expected values.');
  }

}
