<?php

namespace Drupal\Tests\jsonapi\Kernel\Query;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\Query\Filter;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\jsonapi\Query\Filter
 * @group jsonapi
 * @group jsonapi_query
 * @group legacy
 *
 * @internal
 */
class FilterTest extends JsonapiKernelTestBase {

  use ImageFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'file',
    'image',
    'jsonapi',
    'node',
    'serialization',
    'system',
    'text',
    'user',
  ];

  /**
   * A node storage instance.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->setUpSchemas();

    $this->savePaintingType();

    // ((RED or CIRCLE) or (YELLOW and SQUARE))
    $this->savePaintings([
      ['colors' => ['red'], 'shapes' => ['triangle'], 'title' => 'FIND'],
      ['colors' => ['orange'], 'shapes' => ['circle'], 'title' => 'FIND'],
      ['colors' => ['orange'], 'shapes' => ['triangle'], 'title' => 'DONT_FIND'],
      ['colors' => ['yellow'], 'shapes' => ['square'], 'title' => 'FIND'],
      ['colors' => ['yellow'], 'shapes' => ['triangle'], 'title' => 'DONT_FIND'],
      ['colors' => ['orange'], 'shapes' => ['square'], 'title' => 'DONT_FIND'],
    ]);

    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->fieldResolver = $this->container->get('jsonapi.field_resolver');
  }

  /**
   * @covers ::queryCondition
   */
  public function testInvalidFilterPathDueToMissingPropertyName() {
    $this->setExpectedException(CacheableBadRequestHttpException::class, 'Invalid nested filtering. The field `colors`, given in the path `colors` is incomplete, it must end with one of the following specifiers: `value`, `format`, `processed`.');
    $resource_type = new ResourceType('node', 'painting', NULL);
    Filter::createFromQueryParameter(['colors' => ''], $resource_type, $this->fieldResolver);
  }

  /**
   * @covers ::queryCondition
   */
  public function testInvalidFilterPathDueToMissingPropertyNameReferenceFieldWithMetaProperties() {
    $this->setExpectedException(CacheableBadRequestHttpException::class, 'Invalid nested filtering. The field `photo`, given in the path `photo` is incomplete, it must end with one of the following specifiers: `id`, `meta.alt`, `meta.title`, `meta.width`, `meta.height`.');
    $resource_type = new ResourceType('node', 'painting', NULL);
    Filter::createFromQueryParameter(['photo' => ''], $resource_type, $this->fieldResolver);
  }

  /**
   * @covers ::queryCondition
   */
  public function testInvalidFilterPathDueMissingMetaPrefixReferenceFieldWithMetaProperties() {
    $this->setExpectedException(CacheableBadRequestHttpException::class, 'Invalid nested filtering. The property `alt`, given in the path `photo.alt` belongs to the meta object of a relationship and must be preceded by `meta`.');
    $resource_type = new ResourceType('node', 'painting', NULL);
    Filter::createFromQueryParameter(['photo.alt' => ''], $resource_type, $this->fieldResolver);
  }

  /**
   * @covers ::queryCondition
   */
  public function testInvalidFilterPathDueToMissingPropertyNameReferenceFieldWithoutMetaProperties() {
    $this->setExpectedException(CacheableBadRequestHttpException::class, 'Invalid nested filtering. The field `uid`, given in the path `uid` is incomplete, it must end with one of the following specifiers: `id`.');
    $resource_type = new ResourceType('node', 'painting', NULL);
    Filter::createFromQueryParameter(['uid' => ''], $resource_type, $this->fieldResolver);
  }

  /**
   * @covers ::queryCondition
   */
  public function testInvalidFilterPathDueToNonexistentProperty() {
    $this->setExpectedException(CacheableBadRequestHttpException::class, 'Invalid nested filtering. The property `foobar`, given in the path `colors.foobar`, does not exist. Must be one of the following property names: `value`, `format`, `processed`.');
    $resource_type = new ResourceType('node', 'painting', NULL);
    Filter::createFromQueryParameter(['colors.foobar' => ''], $resource_type, $this->fieldResolver);
  }

  /**
   * @covers ::queryCondition
   */
  public function testInvalidFilterPathDueToElidedSoleProperty() {
    $this->setExpectedException(CacheableBadRequestHttpException::class, 'Invalid nested filtering. The property `value`, given in the path `promote.value`, does not exist. Filter by `promote`, not `promote.value` (the JSON:API module elides property names from single-property fields).');
    $resource_type = new ResourceType('node', 'painting', NULL);
    Filter::createFromQueryParameter(['promote.value' => ''], $resource_type, $this->fieldResolver);
  }

  /**
   * @covers ::queryCondition
   */
  public function testQueryCondition() {
    // Can't use a data provider because we need access to the container.
    $data = $this->queryConditionData();

    $get_sql_query_for_entity_query = function ($entity_query) {
      // Expose parts of \Drupal\Core\Entity\Query\Sql\Query::execute().
      $o = new \ReflectionObject($entity_query);
      $m1 = $o->getMethod('prepare');
      $m1->setAccessible(TRUE);
      $m2 = $o->getMethod('compile');
      $m2->setAccessible(TRUE);

      // The private property computed by the two previous private calls, whose
      // value we need to inspect.
      $p = $o->getProperty('sqlQuery');
      $p->setAccessible(TRUE);

      $m1->invoke($entity_query);
      $m2->invoke($entity_query);
      return (string) $p->getValue($entity_query);
    };

    $resource_type = new ResourceType('node', 'painting', NULL);
    foreach ($data as $case) {
      $parameter = $case[0];
      $expected_query = $case[1];
      $filter = Filter::createFromQueryParameter($parameter, $resource_type, $this->fieldResolver);

      $query = $this->nodeStorage->getQuery();

      // Get the query condition parsed from the input.
      $condition = $filter->queryCondition($query);

      // Apply it to the query.
      $query->condition($condition);

      // Verify the SQL query is exactly the same.
      $expected_sql_query = $get_sql_query_for_entity_query($expected_query);
      $actual_sql_query = $get_sql_query_for_entity_query($query);
      $this->assertSame($expected_sql_query, $actual_sql_query);

      // Compare the results.
      $this->assertEquals($expected_query->execute(), $query->execute());
    }
  }

  /**
   * Simply provides test data to keep the actual test method tidy.
   */
  protected function queryConditionData() {
    // ((RED or CIRCLE) or (YELLOW and SQUARE))
    $query = $this->nodeStorage->getQuery();

    $or_group = $query->orConditionGroup();

    $nested_or_group = $query->orConditionGroup();
    $nested_or_group->condition('colors', 'red', 'CONTAINS');
    $nested_or_group->condition('shapes', 'circle', 'CONTAINS');
    $or_group->condition($nested_or_group);

    $nested_and_group = $query->andConditionGroup();
    $nested_and_group->condition('colors', 'yellow', 'CONTAINS');
    $nested_and_group->condition('shapes', 'square', 'CONTAINS');
    $nested_and_group->notExists('photo.alt');
    $or_group->condition($nested_and_group);

    $query->condition($or_group);

    return [
      [
        [
          'or-group' => ['group' => ['conjunction' => 'OR']],
          'nested-or-group' => ['group' => ['conjunction' => 'OR', 'memberOf' => 'or-group']],
          'nested-and-group' => ['group' => ['conjunction' => 'AND', 'memberOf' => 'or-group']],
          'condition-0' => [
            'condition' => [
              'path' => 'colors.value',
              'value' => 'red',
              'operator' => 'CONTAINS',
              'memberOf' => 'nested-or-group',
            ],
          ],
          'condition-1' => [
            'condition' => [
              'path' => 'shapes.value',
              'value' => 'circle',
              'operator' => 'CONTAINS',
              'memberOf' => 'nested-or-group',
            ],
          ],
          'condition-2' => [
            'condition' => [
              'path' => 'colors.value',
              'value' => 'yellow',
              'operator' =>
              'CONTAINS',
              'memberOf' => 'nested-and-group',
            ],
          ],
          'condition-3' => [
            'condition' => [
              'path' => 'shapes.value',
              'value' => 'square',
              'operator' => 'CONTAINS',
              'memberOf' => 'nested-and-group',
            ],
          ],
          'condition-4' => [
            'condition' => [
              'path' => 'photo.meta.alt',
              'operator' => 'IS NULL',
              'memberOf' => 'nested-and-group',
            ],
          ],
        ],
        $query,
      ],
    ];
  }

  /**
   * Sets up the schemas.
   */
  protected function setUpSchemas() {
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);

    $this->installSchema('user', []);
    foreach (['user', 'node'] as $entity_type_id) {
      $this->installEntitySchema($entity_type_id);
    }
  }

  /**
   * Creates a painting node type.
   */
  protected function savePaintingType() {
    NodeType::create([
      'type' => 'painting',
    ])->save();
    $this->createTextField(
      'node', 'painting',
      'colors', 'Colors',
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->createTextField(
      'node', 'painting',
      'shapes', 'Shapes',
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->createImageField('photo', 'painting');
  }

  /**
   * Creates painting nodes.
   */
  protected function savePaintings($paintings) {
    foreach ($paintings as $painting) {
      Node::create(array_merge([
        'type' => 'painting',
      ], $painting))->save();
    }
  }

  /**
   * @covers ::createFromQueryParameter
   * @dataProvider parameterProvider
   */
  public function testCreateFromQueryParameter($case, $expected) {
    $resource_type = new ResourceType('foo', 'bar', NULL);
    $actual = Filter::createFromQueryParameter($case, $resource_type, $this->getFieldResolverMock($resource_type));
    $conditions = $actual->root()->members();
    for ($i = 0; $i < count($case); $i++) {
      $this->assertEquals($expected[$i]['path'], $conditions[$i]->field());
      $this->assertEquals($expected[$i]['value'], $conditions[$i]->value());
      $this->assertEquals($expected[$i]['operator'], $conditions[$i]->operator());
    }
  }

  /**
   * Data provider for testCreateFromQueryParameter.
   */
  public function parameterProvider() {
    return [
      'shorthand' => [
        ['uid' => ['value' => 1]],
        [['path' => 'uid', 'value' => 1, 'operator' => '=']],
      ],
      'extreme shorthand' => [
        ['uid' => 1],
        [['path' => 'uid', 'value' => 1, 'operator' => '=']],
      ],
    ];
  }

  /**
   * @covers ::createFromQueryParameter
   */
  public function testCreateFromQueryParameterNested() {
    $parameter = [
      'or-group' => ['group' => ['conjunction' => 'OR']],
      'nested-or-group' => [
        'group' => ['conjunction' => 'OR', 'memberOf' => 'or-group'],
      ],
      'nested-and-group' => [
        'group' => ['conjunction' => 'AND', 'memberOf' => 'or-group'],
      ],
      'condition-0' => [
        'condition' => [
          'path' => 'field0',
          'value' => 'value0',
          'memberOf' => 'nested-or-group',
        ],
      ],
      'condition-1' => [
        'condition' => [
          'path' => 'field1',
          'value' => 'value1',
          'memberOf' => 'nested-or-group',
        ],
      ],
      'condition-2' => [
        'condition' => [
          'path' => 'field2',
          'value' => 'value2',
          'memberOf' => 'nested-and-group',
        ],
      ],
      'condition-3' => [
        'condition' => [
          'path' => 'field3',
          'value' => 'value3',
          'memberOf' => 'nested-and-group',
        ],
      ],
    ];
    $resource_type = new ResourceType('foo', 'bar', NULL);
    $filter = Filter::createFromQueryParameter($parameter, $resource_type, $this->getFieldResolverMock($resource_type));
    $root = $filter->root();

    // Make sure the implicit root group was added.
    $this->assertEquals($root->conjunction(), 'AND');

    // Ensure the or-group and the and-group were added correctly.
    $members = $root->members();

    // Ensure the OR group was added.
    $or_group = $members[0];
    $this->assertEquals($or_group->conjunction(), 'OR');
    $or_group_members = $or_group->members();

    // Make sure the nested OR group was added with the right conditions.
    $nested_or_group = $or_group_members[0];
    $this->assertEquals($nested_or_group->conjunction(), 'OR');
    $nested_or_group_members = $nested_or_group->members();
    $this->assertEquals($nested_or_group_members[0]->field(), 'field0');
    $this->assertEquals($nested_or_group_members[1]->field(), 'field1');

    // Make sure the nested AND group was added with the right conditions.
    $nested_and_group = $or_group_members[1];
    $this->assertEquals($nested_and_group->conjunction(), 'AND');
    $nested_and_group_members = $nested_and_group->members();
    $this->assertEquals($nested_and_group_members[0]->field(), 'field2');
    $this->assertEquals($nested_and_group_members[1]->field(), 'field3');
  }

  /**
   * Provides a mock field resolver.
   */
  protected function getFieldResolverMock(ResourceType $resource_type) {
    $field_resolver = $this->prophesize(FieldResolver::class);
    $field_resolver->resolveInternalEntityQueryPath($resource_type->getEntityTypeId(), $resource_type->getBundle(), Argument::any())->willReturnArgument(2);
    return $field_resolver->reveal();
  }

}
