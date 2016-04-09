<?php

namespace Drupal\Tests\Core\Config\Entity\Query;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\Query\QueryFactory;
use Drupal\Tests\UnitTestCase;

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
  public function testGetKeys(array $expected, $key, Config $config) {
    $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $key_value_factory = $this->getMock('Drupal\Core\KeyValueStore\KeyValueFactoryInterface');
    $config_manager = $this->getMock('Drupal\Core\Config\ConfigManagerInterface');
    $config_entity_type = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $query_factory = new QueryFactory($config_factory, $key_value_factory, $config_manager);
    $method = new \ReflectionMethod($query_factory, 'getKeys');
    $method->setAccessible(TRUE);

    $actual = $method->invoke($query_factory, $config, $key, 'get', $config_entity_type);
    $this->assertEquals($expected, $actual);
  }

  public function providerTestGetKeys() {
    $tests = [];

    $tests[] = [
      ['uuid:abc'],
      'uuid',
      $this->getConfigObject('test')->set('uuid', 'abc')
    ];

    // Tests a lookup being set to a top level key when sub-keys exist.
    $tests[] = [
      [],
      'uuid',
      $this->getConfigObject('test')->set('uuid.blah', 'abc')
    ];

    // Tests a non existent key.
    $tests[] = [
      [],
      'uuid',
      $this->getConfigObject('test')
    ];

    // Tests a non existent sub key.
    $tests[] = [
      [],
      'uuid.blah',
      $this->getConfigObject('test')->set('uuid', 'abc')
    ];

    // Tests a existent sub key.
    $tests[] = [
      ['uuid.blah:abc'],
      'uuid.blah',
      $this->getConfigObject('test')->set('uuid.blah', 'abc')
    ];

    // One wildcard.
    $tests[] = [
      ['test.*.value:a', 'test.*.value:b'],
      'test.*.value',
      $this->getConfigObject('test')->set('test.a.value', 'a')->set('test.b.value', 'b')
    ];

    // Three wildcards.
    $tests[] = [
      ['test.*.sub2.*.sub4.*.value:aaa', 'test.*.sub2.*.sub4.*.value:aab', 'test.*.sub2.*.sub4.*.value:bab'],
      'test.*.sub2.*.sub4.*.value',
      $this->getConfigObject('test')
        ->set('test.a.sub2.a.sub4.a.value', 'aaa')
        ->set('test.a.sub2.a.sub4.b.value', 'aab')
        ->set('test.b.sub2.a.sub4.b.value', 'bab')
    ];

    // Three wildcards in a row.
    $tests[] = [
      ['test.*.*.*.value:abc', 'test.*.*.*.value:abd'],
      'test.*.*.*.value',
      $this->getConfigObject('test')->set('test.a.b.c.value', 'abc')->set('test.a.b.d.value', 'abd')
    ];

    return $tests;
  }

  /**
   * @expectedException \LogicException
   * @expectedExceptionMessage test_config_entity_type lookup key test.* ends with a wildcard this can not be used as a lookup
   */
  public function testGetKeysWildCardEnd() {
    $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $key_value_factory = $this->getMock('Drupal\Core\KeyValueStore\KeyValueFactoryInterface');
    $config_manager = $this->getMock('Drupal\Core\Config\ConfigManagerInterface');
    $config_entity_type = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $config_entity_type->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('test_config_entity_type');
    $query_factory = new QueryFactory($config_factory, $key_value_factory, $config_manager);

    $method = new \ReflectionMethod($query_factory, 'getKeys');
    $method->setAccessible(TRUE);
    $method->invoke($query_factory, $this->getConfigObject('test'), 'test.*', 'get', $config_entity_type);
  }

  /**
   * Gets a test configuration object.
   *
   * @param string $name
   *   The config name.
   *
   * @return \Drupal\Core\Config\Config|\PHPUnit_Framework_MockObject_MockObject
   *   The test configuration object.
   */
  protected function getConfigObject($name) {
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->setMethods(['save', 'delete'])
      ->getMock();
    return $config->setName($name);
  }
}
