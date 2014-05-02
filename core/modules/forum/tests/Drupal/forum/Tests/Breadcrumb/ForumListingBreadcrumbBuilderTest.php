<?php

/**
 * @file
 * Contains \Drupal\forum\Tests\Breadcrumb\ForumListingBreadcrumbBuilderTest.
 */

namespace Drupal\forum\Tests\Breadcrumb;

use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Tests the listing class for forum breadcrumbs.
 *
 * @coversDefaultClass \Drupal\forum\Breadcrumb\ForumListingBreadcrumbBuilder
 * @group Forum
 * @group Drupal
 *
 * @see \Drupal\forum\ForumListingBreadcrumbBuilder
 */
class ForumListingBreadcrumbBuilderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Forum Breadcrumb Listing Test',
      'description' => 'Tests the listing class for forum breadcrumbs.',
      'group' => 'Forum',
    );
  }

  /**
   * Tests ForumListingBreadcrumbBuilder::applies().
   *
   * @param bool $expected
   *   ForumListingBreadcrumbBuilder::applies() expected result.
   * @param array $attributes
   *   ForumListingBreadcrumbBuilder::applies() $attributes parameter.
   *
   * @dataProvider providerTestApplies
   * @covers ::applies
   */
  public function testApplies($expected, $attributes) {
    // Make some test doubles.
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $config_factory = $this->getConfigFactoryStub(array());
    $forum_manager = $this->getMock('Drupal\forum\ForumManagerInterface');

    // Make an object to test.
    $builder = $this->getMockBuilder('Drupal\forum\Breadcrumb\ForumListingBreadcrumbBuilder')
      ->setConstructorArgs(array(
        $entity_manager,
        $config_factory,
        $forum_manager,
      ))
      ->setMethods(NULL)
      ->getMock();

    $this->assertEquals($expected, $builder->applies($attributes));
  }

  /**
   * Provides test data for testApplies().
   *
   * @return array
   *   Array of datasets for testApplies(). Structured as such:
   *   - ForumListBreadcrumbBuilder::applies() expected result.
   *   - ForumListBreadcrumbBuilder::applies() $attributes input array.
   */
  public function providerTestApplies() {
    // Send a Node mock, because NodeInterface cannot be mocked.
    $mock_term = $this->getMockBuilder('Drupal\taxonomy\Entity\Term')
      ->disableOriginalConstructor()
      ->getMock();

    return array(
      array(
        FALSE,
        array(),
      ),
      array(
        FALSE,
        array(
          RouteObjectInterface::ROUTE_NAME => 'NOT.forum.page',
        ),
      ),
      array(
        FALSE,
        array(
          RouteObjectInterface::ROUTE_NAME => 'forum.page',
        ),
      ),
      array(
        TRUE,
        array(
          RouteObjectInterface::ROUTE_NAME => 'forum.page',
          'taxonomy_term' => 'anything',
        ),
      ),
      array(
        TRUE,
        array(
          RouteObjectInterface::ROUTE_NAME => 'forum.page',
          'taxonomy_term' => $mock_term,
        ),
      ),
    );
  }

  /**
   * Tests ForumListingBreadcrumbBuilder::build().
   *
   * @see \Drupal\forum\ForumListingBreadcrumbBuilder::build()
   *
   * @covers ::build
   */
  public function testBuild() {
    // Build all our dependencies, backwards.
    $term1 = $this->getMockBuilder('Drupal\taxonomy\Entity\Term')
      ->disableOriginalConstructor()
      ->getMock();
    $term1->expects($this->any())
      ->method('label')
      ->will($this->returnValue('Something'));
    $term1->expects($this->any())
      ->method('id')
      ->will($this->returnValue(1));

    $term2 = $this->getMockBuilder('Drupal\taxonomy\Entity\Term')
      ->disableOriginalConstructor()
      ->getMock();
    $term2->expects($this->any())
      ->method('label')
      ->will($this->returnValue('Something else'));
    $term2->expects($this->any())
      ->method('id')
      ->will($this->returnValue(2));

    $forum_manager = $this->getMock('Drupal\forum\ForumManagerInterface');
    $forum_manager->expects($this->at(0))
      ->method('getParents')
      ->will($this->returnValue(array($term1)));
    $forum_manager->expects($this->at(1))
      ->method('getParents')
      ->will($this->returnValue(array($term1, $term2)));

    // The root forum.
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
    $breadcrumb_builder = $this->getMock(
      'Drupal\forum\Breadcrumb\ForumListingBreadcrumbBuilder', NULL, array(
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

    // The forum listing we need a breadcrumb back from.
    $forum_listing = $this->getMockBuilder('Drupal\taxonomy\Entity\Term')
      ->disableOriginalConstructor()
      ->getMock();
    $forum_listing->tid = 23;
    $forum_listing->expects($this->any())
      ->method('label')
      ->will($this->returnValue('You_should_not_see_this'));

    // Our data set.
    $attributes = array(
      'taxonomy_term' => $forum_listing,
    );

    // First test.
    $expected1 = array(
      'Home',
      'Fora_is_the_plural_of_forum',
      'Something',
    );
    $this->assertSame($expected1, $breadcrumb_builder->build($attributes));

    // Second test.
    $expected2 = array(
      'Home',
      'Fora_is_the_plural_of_forum',
      'Something else',
      'Something',
    );
    $this->assertSame($expected2, $breadcrumb_builder->build($attributes));
  }

}
