<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\MenuTreeParametersTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the menu link tree parameters value object.
 *
 * @group Menu
 *
 * @coversDefaultClass \Drupal\Core\Menu\MenuTreeParameters
 */
class MenuTreeParametersTest extends UnitTestCase {

  /**
   * Provides test data for testSetMinDepth().
   */
  public function providerTestSetMinDepth() {
    $data = array();

    // Valid values at the extremes and in the middle.
    $data[] = array(1, 1);
    $data[] = array(2, 2);
    $data[] = array(9, 9);

    // Invalid values are mapped to the closest valid value.
    $data[] = array(-10000, 1);
    $data[] = array(0, 1);
    // … except for those invalid values that reach beyond the maximum depth,
    // because MenuTreeParameters is a value object and hence cannot depend
    // on anything; to know the actual maximum depth, it'd have to depend on the
    // MenuTreeStorage service.
    $data[] = array(10, 10);
    $data[] = array(100000, 100000);

    return $data;
  }

  /**
   * Tests setMinDepth().
   *
   * @covers ::setMinDepth
   * @dataProvider providerTestSetMinDepth
   */
  public function testSetMinDepth($min_depth, $expected) {
    $parameters = new MenuTreeParameters();
    $parameters->setMinDepth($min_depth);
    $this->assertEquals($expected, $parameters->minDepth);
  }

  /**
   * Tests addExpandedParents().
   *
   * @covers ::addExpandedParents
   */
  public function testAddExpanded() {
    $parameters = new MenuTreeParameters();

    // Verify default value.
    $this->assertEquals(array(), $parameters->expandedParents);

    // Add actual menu link plugin IDs to be expanded.
    $parameters->addExpandedParents(array('foo', 'bar', 'baz'));
    $this->assertEquals(array('foo', 'bar', 'baz'), $parameters->expandedParents);

    // Add additional menu link plugin IDs; they should be merged, not replacing
    // the old ones.
    $parameters->addExpandedParents(array('qux', 'quux'));
    $this->assertEquals(array('foo', 'bar', 'baz', 'qux', 'quux'), $parameters->expandedParents);

    // Add pre-existing menu link plugin IDs; they should not be added again;
    // this is a set.
    $parameters->addExpandedParents(array('bar', 'quux'));
    $this->assertEquals(array('foo', 'bar', 'baz', 'qux', 'quux'), $parameters->expandedParents);
  }

  /**
   * Tests addCondition().
   *
   * @covers ::addCondition
   */
  public function testAddCondition() {
    $parameters = new MenuTreeParameters();

    // Verify default value.
    $this->assertEquals(array(), $parameters->conditions);

    // Add a condition.
    $parameters->addCondition('expanded', 1);
    $this->assertEquals(array('expanded' => 1), $parameters->conditions);

    // Add another condition.
    $parameters->addCondition('has_children', 0);
    $this->assertEquals(array('expanded' => 1, 'has_children' => 0), $parameters->conditions);

    // Add a condition with an operator.
    $parameters->addCondition('provider', array('module1', 'module2'), 'IN');
    $this->assertEquals(array('expanded' => 1, 'has_children' => 0, 'provider' => array(array('module1', 'module2'), 'IN')), $parameters->conditions);

    // Add another condition with an operator.
    $parameters->addCondition('id', 1337, '<');
    $this->assertEquals(array('expanded' => 1, 'has_children' => 0, 'provider' => array(array('module1', 'module2'), 'IN'), 'id' => array(1337, '<')), $parameters->conditions);

    // It's impossible to add two conditions on the same field; in that case,
    // the old condition will be overwritten.
    $parameters->addCondition('provider', 'other_module');
    $this->assertEquals(array('expanded' => 1, 'has_children' => 0, 'provider' => 'other_module', 'id' => array(1337, '<')), $parameters->conditions);
  }

  /**
   * Tests onlyEnabledLinks().
   *
   * @covers ::onlyEnabledLinks
   */
  public function testOnlyEnabledLinks() {
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $this->assertEquals(1, $parameters->conditions['enabled']);
  }

  /**
   * Tests setTopLevelOnly().
   *
   * @covers ::setTopLevelOnly
   */
  public function testSetTopLevelOnly() {
    $parameters = new MenuTreeParameters();
    $parameters->setTopLevelOnly();
    $this->assertEquals(1, $parameters->maxDepth);
  }

  /**
   * Tests excludeRoot().
   *
   * @covers ::excludeRoot
   */
  public function testExcludeRoot() {
    $parameters = new MenuTreeParameters();
    $parameters->excludeRoot();
    $this->assertEquals(1, $parameters->minDepth);
  }

}
