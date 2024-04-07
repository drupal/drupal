<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Entity\Query;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\Query\QueryFactory;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\Query\QueryFactory
 * @group Config
 */
class QueryFactoryTest extends UnitTestCase {

  /**
   * @covers ::getKeys
   * @covers ::getValues
   *
   * @dataProvider providerTestGetKeys
   */
  public function testGetKeys(array $expected, string $key, array $sets): void {
    $config = $this->getConfigObject('test');
    foreach ($sets as $set) {
      $config->set(...$set);
    }
    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $key_value_factory = $this->createMock('Drupal\Core\KeyValueStore\KeyValueFactoryInterface');
    $config_manager = $this->createMock('Drupal\Core\Config\ConfigManagerInterface');
    $config_entity_type = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $query_factory = new QueryFactory($config_factory, $key_value_factory, $config_manager);
    $method = new \ReflectionMethod($query_factory, 'getKeys');

    $actual = $method->invoke($query_factory, $config, $key, 'get', $config_entity_type);
    $this->assertEquals($expected, $actual);
  }

  public static function providerTestGetKeys(): \Generator {
    yield [
      ['uuid:abc'],
      'uuid',
      [['uuid', 'abc']],
    ];

    // Tests a lookup being set to a top level key when sub-keys exist.
    yield [
      [],
      'uuid',
      [['uuid.blah', 'abc']],
    ];

    // Tests a non existent key.
    yield [
      [],
      'uuid',
      [],
    ];

    // Tests a non existent sub key.
    yield [
      [],
      'uuid.blah',
      [['uuid', 'abc']],
    ];

    // Tests an existent sub key.
    yield [
      ['uuid.blah:abc'],
      'uuid.blah',
      [['uuid.blah', 'abc']],
    ];

    // One wildcard.
    yield [
      ['test.*.value:a', 'test.*.value:b'],
      'test.*.value',
      [['test.a.value', 'a'], ['test.b.value', 'b']],
    ];

    // Three wildcards.
    yield [
      ['test.*.sub2.*.sub4.*.value:aaa', 'test.*.sub2.*.sub4.*.value:aab', 'test.*.sub2.*.sub4.*.value:bab'],
      'test.*.sub2.*.sub4.*.value',
      [
        ['test.a.sub2.a.sub4.a.value', 'aaa'],
        ['test.a.sub2.a.sub4.b.value', 'aab'],
        ['test.b.sub2.a.sub4.b.value', 'bab'],
      ],
    ];

    // Three wildcards in a row.
    yield [
      ['test.*.*.*.value:abc', 'test.*.*.*.value:abd'],
      'test.*.*.*.value',
      [
        ['test.a.b.c.value', 'abc'],
        ['test.a.b.d.value', 'abd'],
      ],
    ];
  }

  /**
   * @covers ::getKeys
   * @covers ::getValues
   */
  public function testGetKeysWildCardEnd() {
    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $key_value_factory = $this->createMock('Drupal\Core\KeyValueStore\KeyValueFactoryInterface');
    $config_manager = $this->createMock('Drupal\Core\Config\ConfigManagerInterface');
    $config_entity_type = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $config_entity_type->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('test_config_entity_type');
    $query_factory = new QueryFactory($config_factory, $key_value_factory, $config_manager);

    $method = new \ReflectionMethod($query_factory, 'getKeys');
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('test_config_entity_type lookup key test.* ends with a wildcard this can not be used as a lookup');
    $method->invoke($query_factory, $this->getConfigObject('test'), 'test.*', 'get', $config_entity_type);
  }

  /**
   * Gets a test configuration object.
   *
   * @param string $name
   *   The config name.
   *
   * @return \Drupal\Core\Config\Config&\PHPUnit\Framework\MockObject\MockObject
   *   The test configuration object.
   */
  protected function getConfigObject(string $name): Config&MockObject {
    $config = $this->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['save', 'delete'])
      ->getMock();
    return $config->setName($name);
  }

}
