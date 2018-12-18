<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Component\Version\Constraint;
use Drupal\Core\Extension\Dependency;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\Dependency
 * @group Extension
 */
class DependencyTest extends UnitTestCase {

  /**
   * @covers ::createFromString
   * @dataProvider providerCreateFromString
   */
  public function testCreateFromString($string, $expected_name, $expected_project, $expected_constraint) {
    $dependency = Dependency::createFromString($string);
    $this->assertSame($expected_name, $dependency->getName());
    $this->assertSame($expected_project, $dependency->getProject());
    $this->assertSame($expected_constraint, $dependency->getConstraintString());
  }

  /**
   * Data provider for testCreateFromString.
   */
  public function providerCreateFromString() {
    $tests = [];
    $tests['module_name_only'] = ['views', 'views', '', ''];
    $tests['module_and_project_names'] = ['drupal:views', 'views', 'drupal', ''];
    $tests['module_and_constraint'] = ['views (<8.x-3.1)', 'views', '', '<8.x-3.1'];
    $tests['module_and_project_names_and_constraint'] = ['drupal:views (>8.x-1.1)', 'views', 'drupal', '>8.x-1.1'];
    return $tests;
  }

  /**
   * @covers ::isCompatible
   */
  public function testIsCompatible() {
    $dependency = new Dependency('paragraphs_demo', 'paragraphs', '>8.x-1.1');
    $this->assertFalse($dependency->isCompatible('1.1'));
    $this->assertTrue($dependency->isCompatible('1.2'));
  }

  /**
   * @covers ::offsetExists
   * @group legacy
   * @expectedDeprecation Array access to Drupal\Core\Extension\Dependency properties is deprecated. Use accessor methods instead. See https://www.drupal.org/node/2756875
   */
  public function testOffsetTest() {
    $dependency = new Dependency('views', 'drupal', '>8.x-1.1');
    $this->assertTrue(isset($dependency['name']));
    $this->assertFalse(isset($dependency['foo']));
  }

  /**
   * @covers ::offsetGet
   * @group legacy
   * @expectedDeprecation Array access to the Drupal\Core\Extension\Dependency name property is deprecated. Use Drupal\Core\Extension\Dependency::getName() instead. See https://www.drupal.org/node/2756875
   * @expectedDeprecation Array access to the Drupal\Core\Extension\Dependency project property is deprecated. Use Drupal\Core\Extension\Dependency::getProject() instead. See https://www.drupal.org/node/2756875
   * @expectedDeprecation Array access to the Drupal\Core\Extension\Dependency original_version property is deprecated. Use Drupal\Core\Extension\Dependency::getConstraintString() instead. See https://www.drupal.org/node/2756875
   * @expectedDeprecation Array access to the Drupal\Core\Extension\Dependency versions property is deprecated. See https://www.drupal.org/node/2756875
   */
  public function testOffsetGet() {
    $dependency = new Dependency('views', 'drupal', '>8.x-1.1');
    $this->assertSame('views', $dependency['name']);
    $this->assertSame('drupal', $dependency['project']);
    $this->assertSame(' (>8.x-1.1)', $dependency['original_version']);
    $this->assertSame([['op' => '>', 'version' => '1.1']], $dependency['versions']);
  }

  /**
   * @covers ::offsetGet
   * @group legacy
   */
  public function testOffsetGetException() {
    $dependency = new Dependency('views', 'drupal', '>8.x-1.1');
    $this->setExpectedException(\InvalidArgumentException::class, 'The does_not_exist key is not supported');
    $dependency['does_not_exist'];
  }

  /**
   * @covers ::offsetUnset
   * @group legacy
   */
  public function testOffsetUnset() {
    $dependency = new Dependency('views', 'drupal', '>8.x-1.1');
    $this->setExpectedException(\BadMethodCallException::class, 'Drupal\Core\Extension\Dependency::offsetUnset() is not supported');
    unset($dependency['name']);
  }

  /**
   * @covers ::offsetSet
   * @group legacy
   */
  public function testOffsetSet() {
    $dependency = new Dependency('views', 'drupal', '>8.x-1.1');
    $this->setExpectedException(\BadMethodCallException::class, 'Drupal\Core\Extension\Dependency::offsetSet() is not supported');
    $dependency['name'] = 'foo';
  }

  /**
   * Ensures that constraint objects are not serialized.
   *
   * @covers ::__sleep
   */
  public function testSerialization() {
    $dependency = new Dependency('paragraphs_demo', 'paragraphs', '>8.x-1.1');
    $this->assertTrue($dependency->isCompatible('1.2'));
    $this->assertInstanceOf(Constraint::class, $this->getObjectAttribute($dependency, 'constraint'));
    $dependency = unserialize(serialize($dependency));
    $this->assertNull($this->getObjectAttribute($dependency, 'constraint'));
    $this->assertTrue($dependency->isCompatible('1.2'));
    $this->assertInstanceOf(Constraint::class, $this->getObjectAttribute($dependency, 'constraint'));
  }

}
