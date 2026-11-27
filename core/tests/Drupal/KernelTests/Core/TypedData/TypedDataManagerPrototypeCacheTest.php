<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\Validation\Plugin\Validation\Constraint\EmailConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\LengthConstraint;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests TypedDataManager prototype cache does not collide across definitions.
 *
 * TypedDataManager::getPropertyInstance() reuses prototype objects. The cache
 * key must identify the child data definition exactly, otherwise two
 * separately built MapDataDefinition or ListDataDefinition objects that
 * declare a same-named property or item can share a prototype. The colliding
 * lookup then returns an object of the wrong class, or one whose constraints
 * belong to the other definition.
 *
 * Distinct definition objects must never share a prototype, and the same
 * definition object must still be prototyped once. The entity and field paths
 * are covered by
 * \Drupal\KernelTests\Core\TypedData\TypedDataManagerPrototypeCacheEntityTest.
 *
 * @see \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance()
 */
#[Group('TypedData')]
#[RunTestsInSeparateProcesses]
class TypedDataManagerPrototypeCacheTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

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
   * Creates a typed map.
   *
   * @param \Drupal\Core\TypedData\MapDataDefinition $definition
   *   The definition of the map.
   * @param array $value
   *   The value of the map.
   *
   * @return \Drupal\Core\TypedData\Plugin\DataType\Map
   *   The typed map.
   */
  protected function createMap(MapDataDefinition $definition, array $value): Map {
    $map = $this->typedDataManager->create($definition, $value);
    $this->assertInstanceOf(Map::class, $map);
    return $map;
  }

  /**
   * Tests Map cached as prototype does not collide with a scalar property.
   *
   * When the first Map defines a property as a nested Map and the second Map
   * defines the same property name as a string, accessing the property on the
   * second Map should return StringData, not a Map prototype.
   */
  public function testMapPropertyTypeCollision(): void {
    // Map A: 'title' is a nested Map.
    $map_a = MapDataDefinition::create('map');
    $map_a->setPropertyDefinition('title', MapDataDefinition::create('map'));
    $instance_a = $this->createMap($map_a, ['title' => []]);
    // Access the property to populate the prototype cache.
    $prop_a = $instance_a->get('title');
    $this->assertInstanceOf(Map::class, $prop_a);

    // Map B: 'title' is a plain string.
    $map_b = MapDataDefinition::create('map');
    $map_b->setPropertyDefinition('title', DataDefinition::create('string'));
    $instance_b = $this->createMap($map_b, ['title' => 'Hello']);

    // Accessing 'title' on Map B must return StringData, not the cached Map.
    $prop_b = $instance_b->get('title');
    $this->assertInstanceOf(StringData::class, $prop_b, 'Property "title" on Map B should be StringData, not a cached Map prototype.');
    $this->assertSame('Hello', $prop_b->getValue());
  }

  /**
   * Tests different scalar types do not collide in the prototype cache.
   *
   * When the first Map defines a property as a string and the second defines
   * the same property name as an integer, the second should return
   * IntegerData, not StringData.
   */
  public function testMapPropertyScalarTypeCollision(): void {
    // Map A: 'count' is a string.
    $map_a = MapDataDefinition::create('map');
    $map_a->setPropertyDefinition('count', DataDefinition::create('string'));
    $instance_a = $this->createMap($map_a, ['count' => 'five']);
    // Populate the prototype cache with StringData.
    $prop_a = $instance_a->get('count');
    $this->assertInstanceOf(StringData::class, $prop_a);

    // Map B: 'count' is an integer.
    $map_b = MapDataDefinition::create('map');
    $map_b->setPropertyDefinition('count', DataDefinition::create('integer'));
    $instance_b = $this->createMap($map_b, ['count' => 42]);

    // Accessing 'count' on Map B must return IntegerData, not StringData.
    $prop_b = $instance_b->get('count');
    $this->assertInstanceOf(IntegerData::class, $prop_b, 'Property "count" on Map B should be IntegerData, not the cached StringData prototype.');
    $this->assertSame(42, $prop_b->getValue());
  }

  /**
   * Tests constraints are not silently dropped due to prototype caching.
   *
   * When the first Map defines a property with no constraints and the second
   * defines the same property name with a constraint, validation on the second
   * must still enforce the constraint.
   */
  public function testMapPropertyConstraintsDropped(): void {
    // Map A: 'email' is a string with NO constraints.
    $map_a = MapDataDefinition::create('map');
    $map_a->setPropertyDefinition('email', DataDefinition::create('string'));
    $instance_a = $this->createMap($map_a, ['email' => 'not-an-email']);
    // Populate the prototype cache without any constraints.
    $instance_a->get('email');

    // Map B: 'email' is a string WITH an Email constraint.
    $map_b = MapDataDefinition::create('map');
    $email_def = DataDefinition::create('string');
    $email_def->addConstraint('Email');
    $map_b->setPropertyDefinition('email', $email_def);
    $instance_b = $this->createMap($map_b, ['email' => 'not-an-email']);

    // Validation must enforce the Email constraint, not use the cached
    // prototype that has no constraints.
    $prop_b = $instance_b->get('email');
    $violations = $prop_b->validate();
    $this->assertCount(1, $violations, 'The Email constraint should produce a violation for an invalid email address, but the cached prototype lost the constraint.');
    $this->assertInstanceOf(EmailConstraint::class, $violations->get(0)->getConstraint());
  }

  /**
   * Tests constraints do not leak from one Map to another via the cache.
   *
   * When the first Map defines a property with constraints and the second
   * defines the same property name without constraints, validation on the
   * second must not enforce the first Map's constraints.
   */
  public function testMapPropertyConstraintsLeaked(): void {
    // Map C: 'name' has a Length(max: 5) constraint.
    $map_c = MapDataDefinition::create('map');
    $name_def = DataDefinition::create('string');
    $name_def->addConstraint('Length', ['max' => 5]);
    $map_c->setPropertyDefinition('name', $name_def);
    $instance_c = $this->createMap($map_c, ['name' => 'Hi']);
    // Populate the prototype cache WITH the Length constraint.
    $instance_c->get('name');

    // Map D: 'name' has NO constraints.
    $map_d = MapDataDefinition::create('map');
    $map_d->setPropertyDefinition('name', DataDefinition::create('string'));
    $instance_d = $this->createMap($map_d, ['name' => 'A perfectly valid long name']);

    // Validation must NOT enforce the Length constraint from Map C's cached
    // prototype.
    $prop_d = $instance_d->get('name');
    $violations = $prop_d->validate();
    $this->assertCount(0, $violations, 'The Length constraint from Map C should not leak to Map D, but the cached prototype incorrectly enforces it.');
  }

  /**
   * Tests List item constraints are not dropped due to prototype caching.
   *
   * When the first list has string items with no constraints and the second
   * has string items with a constraint, validating an item of the second list
   * must still enforce the constraint.
   */
  public function testListItemConstraintsDropped(): void {
    // List A: unconstrained string items.
    $list_a = ListDataDefinition::create('string');
    // Creating the list with a value populates the prototype cache for its
    // items.
    $la = $this->typedDataManager->create($list_a, ['anything']);
    $this->assertInstanceOf(ListInterface::class, $la);
    $this->assertCount(1, $la);
    $this->assertInstanceOf(StringData::class, $la->get(0));

    // List B: string items with Length(max: 3) constraint.
    $item_def = DataDefinition::create('string');
    $item_def->addConstraint('Length', ['max' => 3]);
    $list_b = new ListDataDefinition([], $item_def);
    $lb = $this->typedDataManager->create($list_b, ['this is way too long']);

    // Validation must enforce the Length constraint on List B's items.
    $this->assertInstanceOf(ListInterface::class, $lb);
    $this->assertCount(1, $lb);
    $violations = $lb->get(0)->validate();
    $this->assertCount(1, $violations, 'The Length constraint on List B items should produce a violation, but the cached prototype from List A lost the constraint.');
    $this->assertInstanceOf(LengthConstraint::class, $violations->get(0)->getConstraint());
  }

  /**
   * Tests different list item types do not collide in the prototype cache.
   *
   * The list analogue of the scalar type collision above.
   */
  public function testListItemTypeCollision(): void {
    $map_a = MapDataDefinition::create('map');
    $map_a->setPropertyDefinition('items', ListDataDefinition::create('string'));
    $instance_a = $this->createMap($map_a, ['items' => ['a string']]);
    $list_a = $instance_a->get('items');
    $this->assertInstanceOf(ListInterface::class, $list_a);
    $this->assertCount(1, $list_a);
    $this->assertInstanceOf(StringData::class, $list_a->get(0));

    $map_b = MapDataDefinition::create('map');
    $map_b->setPropertyDefinition('items', ListDataDefinition::create('integer'));
    $instance_b = $this->createMap($map_b, ['items' => [42]]);
    $list_b = $instance_b->get('items');
    $this->assertInstanceOf(ListInterface::class, $list_b);
    $this->assertCount(1, $list_b);
    $this->assertInstanceOf(IntegerData::class, $list_b->get(0), 'List B items should be IntegerData, not the cached StringData prototype from List A.');
    $this->assertSame(42, $list_b->get(0)->getValue());
  }

  /**
   * Tests all items of one list still share a single prototype.
   *
   * Every delta resolves to the same item definition object and therefore to
   * one prototype.
   */
  public function testListItemsShareOnePrototype(): void {
    $list = ListDataDefinition::create('string');
    $before = $this->protoCount();
    $instance = $this->typedDataManager->create($list, array_fill(0, 10, 'value'));

    $this->assertInstanceOf(ListInterface::class, $instance);
    $this->assertCount(10, $instance);
    foreach ($instance as $item) {
      $this->assertInstanceOf(StringData::class, $item);
    }
    $this->assertSame(1, $this->protoCount() - $before, 'Ten list items add a single prototype.');
  }

  /**
   * Tests properties differing only in their settings do not collide.
   */
  public function testMapPropertySettingsCollision(): void {
    $date_a = DataDefinition::create('datetime_iso8601');
    $date_a->setSetting('datetime_type', 'date');
    $map_a = MapDataDefinition::create('map');
    $map_a->setPropertyDefinition('date', $date_a);
    $instance_a = $this->createMap($map_a, ['date' => '2020-01-01']);
    $this->assertSame('date', $instance_a->get('date')->getDataDefinition()->getSetting('datetime_type'));

    $date_b = DataDefinition::create('datetime_iso8601');
    $date_b->setSetting('datetime_type', 'datetime');
    $map_b = MapDataDefinition::create('map');
    $map_b->setPropertyDefinition('date', $date_b);
    $instance_b = $this->createMap($map_b, ['date' => '2020-01-01T10:00:00']);
    $this->assertSame('datetime', $instance_b->get('date')->getDataDefinition()->getSetting('datetime_type'), 'The property on Map B keeps its own settings instead of those cached for Map A.');
  }

  /**
   * Tests nested map properties do not collide at depth.
   *
   * Both maps declare 'title' as a map, and both inner maps declare 'x'. The
   * definitions differ only in the data type of that innermost property.
   */
  public function testNestedMapDepthCollision(): void {
    $inner_a = MapDataDefinition::create('map');
    $inner_a->setPropertyDefinition('x', DataDefinition::create('string'));
    $map_a = MapDataDefinition::create('map');
    $map_a->setPropertyDefinition('title', $inner_a);
    $a = $this->createMap($map_a, ['title' => ['x' => 'str']]);
    $title_a = $a->get('title');
    $this->assertInstanceOf(Map::class, $title_a);
    $this->assertInstanceOf(StringData::class, $title_a->get('x'));

    $inner_b = MapDataDefinition::create('map');
    $inner_b->setPropertyDefinition('x', DataDefinition::create('integer'));
    $map_b = MapDataDefinition::create('map');
    $map_b->setPropertyDefinition('title', $inner_b);
    $b = $this->createMap($map_b, ['title' => ['x' => 7]]);
    $title_b = $b->get('title');
    $this->assertInstanceOf(Map::class, $title_b);
    $this->assertInstanceOf(IntegerData::class, $title_b->get('x'), 'The innermost property of Map B should be IntegerData, not the prototype cached for Map A.');
    $this->assertSame(7, $title_b->get('x')->getValue());
  }

  /**
   * Tests data references with different targets do not collide.
   *
   * Both maps declare 'ref' as an entity reference. The definitions differ only
   * in their target definition.
   */
  public function testDataReferenceTargetCollision(): void {
    $ref_a = DataReferenceDefinition::create('entity');
    $ref_a->setTargetDefinition(EntityDataDefinition::create('node'));
    $map_a = MapDataDefinition::create('map');
    $map_a->setPropertyDefinition('ref', $ref_a);
    $instance_a = $this->createMap($map_a, []);
    $ref_instance_a = $instance_a->get('ref');
    $this->assertInstanceOf(EntityReference::class, $ref_instance_a);
    $ref_definition_a = $ref_instance_a->getDataDefinition();
    $this->assertInstanceOf(DataReferenceDefinitionInterface::class, $ref_definition_a);
    $this->assertSame('entity:node', $ref_definition_a->getTargetDefinition()->getDataType());

    $ref_b = DataReferenceDefinition::create('entity');
    $ref_b->setTargetDefinition(EntityDataDefinition::create('user'));
    $map_b = MapDataDefinition::create('map');
    $map_b->setPropertyDefinition('ref', $ref_b);
    $instance_b = $this->createMap($map_b, []);
    $ref_instance_b = $instance_b->get('ref');
    $this->assertInstanceOf(EntityReference::class, $ref_instance_b);
    $ref_definition_b = $ref_instance_b->getDataDefinition();
    $this->assertInstanceOf(DataReferenceDefinitionInterface::class, $ref_definition_b);
    $this->assertSame('entity:user', $ref_definition_b->getTargetDefinition()->getDataType(), 'The reference on Map B should resolve against the user target, not the node target cached for Map A.');
  }

  /**
   * Tests an unknown property still throws after a prototype was cached.
   */
  public function testUnknownPropertyStillThrows(): void {
    $map_a = MapDataDefinition::create('map');
    $map_a->setPropertyDefinition('title', DataDefinition::create('string'));
    $this->createMap($map_a, ['title' => 'Hello'])->get('title');

    // Map B does not declare 'title' at all.
    $map_b = MapDataDefinition::create('map');
    $map_b->setPropertyDefinition('other', DataDefinition::create('string'));
    $instance_b = $this->createMap($map_b, ['other' => 'Hello']);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Property title is unknown.');
    $instance_b->get('title');
  }

  /**
   * Tests two maps sharing a child definition object share one prototype.
   *
   * This is the reuse half of the contract: prototyping must still collapse
   * repeated instantiation of the same definition to a single prototype.
   */
  public function testSharedDefinitionSharesPrototype(): void {
    $shared = DataDefinition::create('string');
    $map_a = MapDataDefinition::create('map');
    $map_a->setPropertyDefinition('name', $shared);
    $map_b = MapDataDefinition::create('map');
    $map_b->setPropertyDefinition('name', $shared);

    $before = $this->protoCount();
    $this->createMap($map_a, ['name' => 'a'])->get('name');
    $after_one = $this->protoCount();
    $this->createMap($map_b, ['name' => 'b'])->get('name');
    $this->assertSame(1, $after_one - $before);
    $this->assertSame($after_one, $this->protoCount(), 'A second map using the same child definition reuses the prototype.');
  }

  /**
   * Tests clearing the cached definitions empties the prototype cache.
   */
  public function testClearCachedDefinitionsClearsPrototypes(): void {
    $map = MapDataDefinition::create('map');
    $map->setPropertyDefinition('name', DataDefinition::create('string'));
    $this->createMap($map, ['name' => 'warm'])->get('name');
    $this->assertGreaterThan(0, $this->protoCount());

    $this->typedDataManager->clearCachedDefinitions();
    $this->assertSame(0, $this->protoCount());
    $property = $this->createMap($map, ['name' => 'again'])->get('name');
    $this->assertInstanceOf(StringData::class, $property);
    $this->assertSame('again', $property->getValue());
    $this->assertSame(1, $this->protoCount());
  }

  /**
   * Tests the deprecation of writing an array to the prototype cache.
   *
   * The next lookup triggers the deprecation, replaces the array with a new
   * cache and still returns the property's typed data.
   */
  #[IgnoreDeprecations]
  public function testArrayWrittenToPrototypesResetsCache(): void {
    $this->expectUserDeprecationMessage('Setting \\Drupal\\Core\\TypedData\\TypedDataManager::$prototypes to anything other than a \\WeakMap or NULL is deprecated in drupal:11.6.0 and is removed from drupal:13.0.0. See https://www.drupal.org/node/3638699');
    $ref = new \ReflectionProperty($this->typedDataManager, 'prototypes');
    $ref->setValue($this->typedDataManager, []);

    $map = MapDataDefinition::create('map');
    $map->setPropertyDefinition('name', DataDefinition::create('string'));
    $property = $this->createMap($map, ['name' => 'reset'])->get('name');
    $this->assertSame('reset', $property->getValue());
  }

}
