<?php

/**
 * @file
 * Contains \Drupal\forum\Tests\Breadcrumb\ForumBreadcrumbBuilderBaseTest.
 */

namespace Drupal\forum\Tests\Breadcrumb;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the ForumManager.
 *
 * @coversDefaultClass \Drupal\forum\Breadcrumb\ForumBreadcrumbBuilderBase
 * @group Forum
 * @group Drupal
 *
 * @see \Drupal\forum\ForumManager
 */
class ForumBreadcrumbBuilderBaseTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Forum Breadcrumb Base Test',
      'description' => 'Tests the abstract base class for forum breadcrumbs.',
      'group' => 'Forum',
    );
  }

  /**
   * Tests ForumBreadcrumbBuilderBase::__construct().
   *
   * @covers ::__construct()
   */
  public function testConstructor() {
    // Make some test doubles.
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $config_factory = $this->getConfigFactoryStub(
      array(
        'forum.settings' => array('IAmATestKey' => 'IAmATestValue'),
      )
    );
    $forum_manager = $this->getMock('Drupal\forum\ForumManagerInterface');

    // Make an object to test.
    $builder = $this->getMockForAbstractClass(
      'Drupal\forum\Breadcrumb\ForumBreadcrumbBuilderBase',
      // Constructor array.
      array(
        $entity_manager,
        $config_factory,
        $forum_manager,
      )
    );

    // Reflect upon our properties, except for config which is a special case.
    $property_names = array(
      'entityManager' => $entity_manager,
      'forumManager' => $forum_manager,
    );
    foreach ($property_names as $property_name => $property_value) {
      $this->assertAttributeEquals(
        $property_value, $property_name, $builder
      );
    }

    // Test that the constructor made a config object with our info in it.
    $reflector = new \ReflectionClass($builder);
    $ref_property = $reflector->getProperty('config');
    $ref_property->setAccessible(TRUE);
    $config = $ref_property->getValue($builder);
    $this->assertEquals('IAmATestValue', $config->get('IAmATestKey'));
  }

  /**
   * Tests ForumBreadcrumbBuilderBase::build().
   *
   * @see \Drupal\forum\Breadcrumb\ForumBreadcrumbBuilderBase::build()
   *
   * @covers ::build
   */
  public function testBuild() {
    // Build all our dependencies, backwards.
    $forum_manager = $this->getMockBuilder('Drupal\forum\ForumManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $vocab_item = $this->getMock('Drupal\taxonomy\VocabularyInterface');
    $vocab_item->expects($this->any())
      ->method('label')
      ->will($this->returnValue('Fora_is_the_plural_of_forum'));

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
    $breadcrumb_builder = $this->getMockForAbstractClass(
      'Drupal\forum\Breadcrumb\ForumBreadcrumbBuilderBase',
      // Constructor array.
      array(
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

    // Our empty data set.
    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');

    // Expected result set.
    $expected = array(
      'Home',
      'Fora_is_the_plural_of_forum',
    );

    // And finally, the test.
    $this->assertSame($expected, $breadcrumb_builder->build($route_match));
  }

}
