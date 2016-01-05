<?php

/**
 * @file
 * Contains \Drupal\Tests\forum\Unit\Breadcrumb\ForumBreadcrumbBuilderBaseTest.
 */

namespace Drupal\Tests\forum\Unit\Breadcrumb;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\forum\Breadcrumb\ForumBreadcrumbBuilderBase
 * @group forum
 */
class ForumBreadcrumbBuilderBaseTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests ForumBreadcrumbBuilderBase::__construct().
   *
   * @covers ::__construct
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

    $prophecy = $this->prophesize('Drupal\taxonomy\VocabularyInterface');
    $prophecy->label()->willReturn('Fora_is_the_plural_of_forum');
    $prophecy->id()->willReturn(5);
    $prophecy->getCacheTags()->willReturn(['taxonomy_vocabulary:5']);
    $prophecy->getCacheContexts()->willReturn([]);
    $prophecy->getCacheMaxAge()->willReturn(Cache::PERMANENT);

    $vocab_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $vocab_storage->expects($this->any())
      ->method('load')
      ->will($this->returnValueMap(array(
        array('forums', $prophecy->reveal()),
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
    $breadcrumb_builder->setStringTranslation($translation_manager);

    // Our empty data set.
    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');

    // Expected result set.
    $expected = array(
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Fora_is_the_plural_of_forum', 'forum.index'),
    );

    // And finally, the test.
    $breadcrumb = $breadcrumb_builder->build($route_match);
    $this->assertEquals($expected, $breadcrumb->getLinks());
    $this->assertEquals(['route'], $breadcrumb->getCacheContexts());
    $this->assertEquals(['taxonomy_vocabulary:5'], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

}
