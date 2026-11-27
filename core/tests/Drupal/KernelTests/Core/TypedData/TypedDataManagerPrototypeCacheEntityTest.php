<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the typed data prototype cache on the entity field path.
 *
 * Entity field access is the path prototyping exists for. Field and item
 * definition objects are built once per bundle, so prototypes must still be
 * shared across entities and across the deltas of one field. A field definition
 * that is deliberately rebuilt must not keep the prototype that was cached for
 * its previous state.
 *
 * @see \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance()
 * @see \Drupal\KernelTests\Core\TypedData\TypedDataManagerPrototypeCacheTest
 */
#[Group('TypedData')]
#[RunTestsInSeparateProcesses]
class TypedDataManagerPrototypeCacheEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'field', 'node', 'text', 'filter'];

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->typedDataManager = $this->container->get('typed_data_manager');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['filter', 'node']);
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_multi',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_multi',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
  }

  /**
   * Returns the number of cached typed data prototypes.
   *
   * @return int
   *   The number of prototypes currently held by the typed data manager.
   */
  protected function protoCount(): int {
    $ref = new \ReflectionProperty($this->typedDataManager, 'prototypes');
    $value = $ref->getValue($this->typedDataManager);
    return $value === NULL ? 0 : count($value);
  }

  /**
   * Tests further entities and further deltas do not add prototypes.
   *
   * All deltas of a field resolve to the same item definition object, so
   * touching many of them must rely on a single prototype.
   */
  public function testEntityFieldAccessReusesPrototypes(): void {
    // Warm the cache on one node with a single delta.
    $node = Node::create([
      'type' => 'article',
      'title' => 'First',
      'uid' => 0,
      'field_multi' => ['one'],
    ]);
    $node->get('title')->get(0)->get('value')->getValue();
    $node->get('uid')->get(0)->get('target_id')->getValue();
    $node->get('field_multi')->get(0)->get('value')->getValue();
    $warm = $this->protoCount();
    $this->assertGreaterThan(0, $warm);

    // A node with twenty deltas must not add a prototype per delta.
    $many = Node::create([
      'type' => 'article',
      'title' => 'Many',
      'uid' => 0,
      'field_multi' => array_fill(0, 20, 'value'),
    ]);
    $this->assertCount(20, $many->get('field_multi'));
    foreach ($many->get('field_multi') as $item) {
      $item->get('value')->getValue();
    }
    $this->assertSame($warm, $this->protoCount(), 'Further deltas of the same field reuse the item prototype.');

    // Five more nodes must not add any prototypes either.
    for ($i = 0; $i < 5; $i++) {
      $other = Node::create([
        'type' => 'article',
        'title' => 'Node ' . $i,
        'uid' => 0,
        'field_multi' => ['one'],
      ]);
      $other->get('title')->get(0)->get('value')->getValue();
      $other->get('uid')->get(0)->get('target_id')->getValue();
      $other->get('field_multi')->get(0)->get('value')->getValue();
    }
    $this->assertSame($warm, $this->protoCount(), 'Further entities of the same bundle reuse the existing prototypes.');
  }

  /**
   * Tests a rebuilt field item definition is not served a stale prototype.
   *
   * FieldConfigBase::addPropertyConstraints() resets the item definition so
   * that the next instantiation picks up the new constraints.
   *
   * @see \Drupal\Core\Field\FieldConfigBase::addPropertyConstraints()
   */
  public function testFieldPropertyConstraintsAreNotStale(): void {
    $field_definitions = $this->container->get('entity_field.manager')->getFieldDefinitions('node', 'article');
    $field_definition = $field_definitions['field_multi'];
    $this->assertInstanceOf(FieldConfig::class, $field_definition);

    // Warm the prototype cache with the unconstrained item definition.
    $node = Node::create([
      'type' => 'article',
      'title' => 'First',
      'uid' => 0,
      'field_multi' => ['this is way too long'],
    ]);
    $this->assertCount(0, $node->get('field_multi')->get(0)->validate());

    // Adding a property constraint resets the item definition.
    $field_definition->addPropertyConstraints('value', ['Length' => ['max' => 3]]);

    // The next item must be built from the new definition, constraints
    // included.
    $constrained = Node::create([
      'type' => 'article',
      'title' => 'Second',
      'uid' => 0,
      'field_multi' => ['this is way too long'],
    ]);
    $violations = $constrained->get('field_multi')->get(0)->validate();
    $this->assertCount(1, $violations, 'The added property constraint is enforced instead of the prototype cached before the definition was reset.');
    $this->assertSame('value', $violations->get(0)->getPropertyPath());
  }

}
