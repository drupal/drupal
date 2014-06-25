<?php

/**
 * @file
 * Contains \Drupal\forum\Tests\Breadcrumb\ForumNodeBreadcrumbBuilderTest.
 */

namespace Drupal\forum\Tests\Breadcrumb;

use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Tests the listing class for forum breadcrumbs.
 *
 * @group Forum
 * @group Drupal
 *
 * @see \Drupal\forum\ForumNodeBreadcrumbBuilder
 * @coversDefaultClass \Drupal\forum\Breadcrumb\ForumNodeBreadcrumbBuilder
 */
class ForumNodeBreadcrumbBuilderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Forum Breadcrumb Node Test',
      'description' => 'Tests the node class for forum breadcrumbs.',
      'group' => 'Forum',
    );
  }

  /**
   * Tests ForumNodeBreadcrumbBuilder::applies().
   *
   * @param bool $expected
   *   ForumNodeBreadcrumbBuilder::applies() expected result.
   * @param string|null $route_name
   *   (optional) A route name.
   * @param array $parameter_map
   *   (optional) An array of parameter names and values.
   *
   * @dataProvider providerTestApplies
   * @covers ::applies
   */
  public function testApplies($expected, $route_name = NULL, $parameter_map = array()) {
    // Make some test doubles.
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $config_factory = $this->getConfigFactoryStub(array());

    $forum_manager = $this->getMock('Drupal\forum\ForumManagerInterface');
    $forum_manager->expects($this->any())
      ->method('checkNodeType')
      ->will($this->returnValue(TRUE));

    // Make an object to test.
    $builder = $this->getMockBuilder('Drupal\forum\Breadcrumb\ForumNodeBreadcrumbBuilder')
      ->setConstructorArgs(
        array(
          $entity_manager,
          $config_factory,
          $forum_manager,
        )
      )
      ->setMethods(NULL)
      ->getMock();

    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->once())
      ->method('getRouteName')
      ->will($this->returnValue($route_name));
    $route_match->expects($this->any())
      ->method('getParameter')
      ->will($this->returnValueMap($parameter_map));

    $this->assertEquals($expected, $builder->applies($route_match));
  }

  /**
   * Provides test data for testApplies().
   *
   * Note that this test is incomplete, because we can't mock NodeInterface.
   *
   * @return array
   *   Array of datasets for testApplies(). Structured as such:
   *   - ForumNodeBreadcrumbBuilder::applies() expected result.
   *   - ForumNodeBreadcrumbBuilder::applies() $attributes input array.
   */
  public function providerTestApplies() {
    // Send a Node mock, because NodeInterface cannot be mocked.
    $mock_node = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    return array(
      array(
        FALSE,
      ),
      array(
        FALSE,
        'NOT.node.view',
      ),
      array(
        FALSE,
        'node.view',
      ),
      array(
        FALSE,
        'node.view',
        array(array('node', NULL)),
      ),
      array(
        TRUE,
        'node.view',
        array(array('node', $mock_node)),
      ),
    );
  }

  /**
   * Tests ForumNodeBreadcrumbBuilder::build().
   *
   * @see \Drupal\forum\ForumNodeBreadcrumbBuilder::build()
   * @covers ::build
   */
  public function testBuild() {
    // Build all our dependencies, backwards.
    $term1 = $this->getMockBuilder('Drupal\Core\Entity\EntityInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $term1->expects($this->any())
      ->method('label')
      ->will($this->returnValue('Something'));
    $term1->expects($this->any())
      ->method('id')
      ->will($this->returnValue(1));

    $term2 = $this->getMockBuilder('Drupal\Core\Entity\EntityInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $term2->expects($this->any())
      ->method('label')
      ->will($this->returnValue('Something else'));
    $term2->expects($this->any())
      ->method('id')
      ->will($this->returnValue(2));

    $forum_manager = $this->getMockBuilder('Drupal\forum\ForumManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $forum_manager->expects($this->at(0))
      ->method('getParents')
      ->will($this->returnValue(array($term1)));
    $forum_manager->expects($this->at(1))
      ->method('getParents')
      ->will($this->returnValue(array($term1, $term2)));

    $vocab_item = $this->getMock('Drupal\taxonomy\VocabularyInterface');
    $vocab_item->expects($this->any())
      ->method('label')
      ->will($this->returnValue('Forums'));
    $vocab_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $vocab_storage->expects($this->any())
      ->method('load')
      ->will($this->returnValueMap(array(
        array('forums', $vocab_item),
      )));

    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_manager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValueMap(array(
        array('taxonomy_vocabulary', $vocab_storage),
      )));

    $config_factory = $this->getConfigFactoryStub(
      array(
        'forum.settings' => array(
          'vocabulary' => 'forums',
        ),
      )
    );

    // Build a breadcrumb builder to test.
    $breadcrumb_builder = $this->getMock(
      'Drupal\forum\Breadcrumb\ForumNodeBreadcrumbBuilder', NULL, array(
        $entity_manager,
        $config_factory,
        $forum_manager,
      )
    );

    // Add a translation manager for t().
    $translation_manager = $this->getStringTranslationStub();
    $property = new \ReflectionProperty('Drupal\forum\Breadcrumb\ForumNodeBreadcrumbBuilder', 'stringTranslation');
    $property->setAccessible(TRUE);
    $property->setValue($breadcrumb_builder, $translation_manager);

    // Add a link generator for l().
    $link_generator = $this->getMockBuilder('Drupal\Core\Utility\LinkGeneratorInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $link_generator->expects($this->any())
      ->method('generate')
      ->will($this->returnArgument(0));
    $property = new \ReflectionProperty('Drupal\forum\Breadcrumb\ForumNodeBreadcrumbBuilder', 'linkGenerator');
    $property->setAccessible(TRUE);
    $property->setValue($breadcrumb_builder, $link_generator);

    // The forum node we need a breadcrumb back from.
    $forum_node = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    // Our data set.
    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->exactly(2))
      ->method('getParameter')
      ->with('node')
      ->will($this->returnValue($forum_node));

    // First test.
    $expected1 = array(
      'Home',
      'Forums',
      'Something',
    );
    $this->assertSame($expected1, $breadcrumb_builder->build($route_match));

    // Second test.
    $expected2 = array(
      'Home',
      'Forums',
      'Something else',
      'Something',
    );
    $this->assertSame($expected2, $breadcrumb_builder->build($route_match));
  }

}
