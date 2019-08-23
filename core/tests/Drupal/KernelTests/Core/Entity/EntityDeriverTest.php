<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\comment\Entity\CommentType;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests EntityDeriver functionality.
 *
 * @coversDefaultClass \Drupal\Core\Entity\Plugin\DataType\Deriver\EntityDeriver
 *
 * @group Entity
 */
class EntityDeriverTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'field',
    'user',
    'node',
    'comment',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setup();

    $this->installEntitySchema('comment');
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    CommentType::create([
      'id' => 'comment',
      'name' => 'Default comment',
      'target_entity_type_id' => 'node',
    ])->save();
    entity_test_create_bundle('foo', NULL, 'entity_test_no_bundle');
    entity_test_create_bundle('entity_test_no_bundle', NULL, 'entity_test_no_bundle');
    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * Tests that types are derived for entity types with and without bundles.
   *
   * @dataProvider derivativesProvider
   */
  public function testDerivatives($data_type, $expect_exception) {
    if ($expect_exception) {
      $this->setExpectedException(PluginNotFoundException::class);
    }
    $this->typedDataManager->createDataDefinition($data_type);
  }

  /**
   * Provides test data for ::testDerivatives().
   */
  public function derivativesProvider() {
    return [
      'unbundleable entity type with no bundle type' => ['entity:user', FALSE],
      'unbundleable entity type with bundle type' => ['entity:user:user', TRUE],
      'bundleable entity type with no bundle type' => ['entity:node', FALSE],
      'bundleable entity type with bundle type' => [
        'entity:node:article',
        FALSE,
      ],
      'bundleable entity type with bundle type with matching name' => [
        'entity:comment:comment',
        FALSE,
      ],
      'unbundleable entity type with entity_test_entity_bundle_info()-generated bundle type' => [
        'entity:entity_test_no_bundle:foo',
        FALSE,
      ],
      'unbundleable entity type with entity_test_entity_bundle_info()-generated bundle type with matching name' => [
        'entity:entity_test_no_bundle:entity_test_no_bundle',
        FALSE,
      ],
    ];
  }

}
