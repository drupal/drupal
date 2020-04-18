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
   * Ensures that constraint objects are not serialized.
   *
   * @covers ::__sleep
   */
  public function testSerialization() {
    $dependency = new Dependency('paragraphs_demo', 'paragraphs', '>8.x-1.1');
    $this->assertTrue($dependency->isCompatible('1.2'));
    $reflected_constraint = (new \ReflectionObject($dependency))->getProperty('constraint');
    $reflected_constraint->setAccessible(TRUE);
    $constraint = $reflected_constraint->getValue($dependency);
    $this->assertInstanceOf(Constraint::class, $constraint);

    $dependency = unserialize(serialize($dependency));
    $reflected_constraint = (new \ReflectionObject($dependency))->getProperty('constraint');
    $reflected_constraint->setAccessible(TRUE);
    $constraint = $reflected_constraint->getValue($dependency);
    $this->assertNull($constraint);
    $this->assertTrue($dependency->isCompatible('1.2'));
    $constraint = $reflected_constraint->getValue($dependency);
    $this->assertInstanceOf(Constraint::class, $constraint);
  }

}
