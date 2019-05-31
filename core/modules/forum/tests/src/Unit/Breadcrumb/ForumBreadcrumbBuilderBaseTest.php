<?php

namespace Drupal\Tests\forum\Unit\Breadcrumb;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $config_factory = $this->getConfigFactoryStub(
      [
        'forum.settings' => ['IAmATestKey' => 'IAmATestValue'],
      ]
    );
    $forum_manager = $this->createMock('Drupal\forum\ForumManagerInterface');
    $translation_manager = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');

    // Make an object to test.
    $builder = $this->getMockForAbstractClass(
      'Drupal\forum\Breadcrumb\ForumBreadcrumbBuilderBase',
      // Constructor array.
      [
        $entity_type_manager,
        $config_factory,
        $forum_manager,
        $translation_manager,
      ]
    );

    // Reflect upon our properties, except for config which is a special case.
    $property_names = [
      'entityTypeManager' => $entity_type_manager,
      'forumManager' => $forum_manager,
      'stringTranslation' => $translation_manager,
    ];
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
    $translation_manager = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslationInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $forum_manager = $this->getMockBuilder('Drupal\forum\ForumManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $prophecy = $this->prophesize('Drupal\taxonomy\VocabularyInterface');
    $prophecy->label()->willReturn('Fora_is_the_plural_of_forum');
    $prophecy->id()->willReturn(5);
    $prophecy->getCacheTags()->willReturn(['taxonomy_vocabulary:5']);
    $prophecy->getCacheContexts()->willReturn([]);
    $prophecy->getCacheMaxAge()->willReturn(Cache::PERMANENT);

    $vocab_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $vocab_storage->expects($this->any())
      ->method('load')
      ->will($this->returnValueMap([
        ['forums', $prophecy->reveal()],
      ]));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValueMap([
        ['taxonomy_vocabulary', $vocab_storage],
      ]));

    $config_factory = $this->getConfigFactoryStub(
      [
        'forum.settings' => [
          'vocabulary' => 'forums',
        ],
      ]
    );

    // Build a breadcrumb builder to test.
    $breadcrumb_builder = $this->getMockForAbstractClass(
      'Drupal\forum\Breadcrumb\ForumBreadcrumbBuilderBase',
      // Constructor array.
      [
        $entity_type_manager,
        $config_factory,
        $forum_manager,
        $translation_manager,
      ]
    );

    // Add a translation manager for t().
    $translation_manager = $this->getStringTranslationStub();
    $breadcrumb_builder->setStringTranslation($translation_manager);

    // Our empty data set.
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');

    // Expected result set.
    $expected = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Fora_is_the_plural_of_forum', 'forum.index'),
    ];

    // And finally, the test.
    $breadcrumb = $breadcrumb_builder->build($route_match);
    $this->assertEquals($expected, $breadcrumb->getLinks());
    $this->assertEquals(['route'], $breadcrumb->getCacheContexts());
    $this->assertEquals(['taxonomy_vocabulary:5'], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

}
