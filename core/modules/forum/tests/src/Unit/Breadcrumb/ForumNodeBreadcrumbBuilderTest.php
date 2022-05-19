<?php

namespace Drupal\Tests\forum\Unit\Breadcrumb;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\forum\Breadcrumb\ForumNodeBreadcrumbBuilder;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\forum\Breadcrumb\ForumNodeBreadcrumbBuilder
 * @group forum
 */
class ForumNodeBreadcrumbBuilderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
  public function testApplies($expected, $route_name = NULL, $parameter_map = []) {
    // Make some test doubles.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $config_factory = $this->getConfigFactoryStub([]);

    $forum_manager = $this->createMock('Drupal\forum\ForumManagerInterface');
    $forum_manager->expects($this->any())
      ->method('checkNodeType')
      ->will($this->returnValue(TRUE));

    $translation_manager = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');

    // Make an object to test.
    $builder = new ForumNodeBreadcrumbBuilder($entity_type_manager, $config_factory, $forum_manager, $translation_manager);

    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->once())
      ->method('getRouteName')
      ->will($this->returnValue($route_name));
    $route_match->expects($this->any())
      ->method('getParameter')
      ->willReturnMap($parameter_map);

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

    return [
      [
        FALSE,
      ],
      [
        FALSE,
        'NOT.entity.node.canonical',
      ],
      [
        FALSE,
        'entity.node.canonical',
      ],
      [
        FALSE,
        'entity.node.canonical',
        [['node', NULL]],
      ],
      [
        TRUE,
        'entity.node.canonical',
        [['node', $mock_node]],
      ],
    ];
  }

  /**
   * Tests ForumNodeBreadcrumbBuilder::build().
   *
   * @see \Drupal\forum\ForumNodeBreadcrumbBuilder::build()
   * @covers ::build
   */
  public function testBuild() {
    // Build all our dependencies, backwards.
    $translation_manager = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslationInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $prophecy = $this->prophesize('Drupal\taxonomy\Entity\Term');
    $prophecy->label()->willReturn('Something');
    $prophecy->id()->willReturn(1);
    $prophecy->getCacheTags()->willReturn(['taxonomy_term:1']);
    $prophecy->getCacheContexts()->willReturn([]);
    $prophecy->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $term1 = $prophecy->reveal();

    $prophecy = $this->prophesize('Drupal\taxonomy\Entity\Term');
    $prophecy->label()->willReturn('Something else');
    $prophecy->id()->willReturn(2);
    $prophecy->getCacheTags()->willReturn(['taxonomy_term:2']);
    $prophecy->getCacheContexts()->willReturn([]);
    $prophecy->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $term2 = $prophecy->reveal();

    $forum_manager = $this->getMockBuilder('Drupal\forum\ForumManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $term_storage = $this->getMockBuilder(TermStorageInterface::class)->getMock();
    $term_storage->expects($this->exactly(2))
      ->method('loadAllParents')
      ->willReturnOnConsecutiveCalls(
        [$term1],
        [$term1, $term2],
      );

    $prophecy = $this->prophesize('Drupal\taxonomy\VocabularyInterface');
    $prophecy->label()->willReturn('Forums');
    $prophecy->id()->willReturn(5);
    $prophecy->getCacheTags()->willReturn(['taxonomy_vocabulary:5']);
    $prophecy->getCacheContexts()->willReturn([]);
    $prophecy->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $vocab_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $vocab_storage->expects($this->any())
      ->method('load')
      ->willReturnMap([
        ['forums', $prophecy->reveal()],
      ]);

    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->willReturnMap([
        ['taxonomy_vocabulary', $vocab_storage],
        ['taxonomy_term', $term_storage],
      ]);

    $config_factory = $this->getConfigFactoryStub(
      [
        'forum.settings' => [
          'vocabulary' => 'forums',
        ],
      ]
    );

    // Build a breadcrumb builder to test.
    $breadcrumb_builder = new ForumNodeBreadcrumbBuilder($entity_type_manager,
      $config_factory,
      $forum_manager,
      $translation_manager);

    // Add a translation manager for t().
    $translation_manager = $this->getStringTranslationStub();
    $breadcrumb_builder->setStringTranslation($translation_manager);

    // The forum node we need a breadcrumb back from.
    $forum_node = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    // Our data set.
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->exactly(2))
      ->method('getParameter')
      ->with('node')
      ->will($this->returnValue($forum_node));

    // First test.
    $expected1 = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Forums', 'forum.index'),
      Link::createFromRoute('Something', 'forum.page', ['taxonomy_term' => 1]),
    ];
    $breadcrumb = $breadcrumb_builder->build($route_match);
    $this->assertEquals($expected1, $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing(['route'], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing(['taxonomy_term:1', 'taxonomy_vocabulary:5'], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());

    // Second test.
    $expected2 = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Forums', 'forum.index'),
      Link::createFromRoute('Something else', 'forum.page', ['taxonomy_term' => 2]),
      Link::createFromRoute('Something', 'forum.page', ['taxonomy_term' => 1]),
    ];
    $breadcrumb = $breadcrumb_builder->build($route_match);
    $this->assertEquals($expected2, $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing(['route'], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing(['taxonomy_term:1', 'taxonomy_term:2', 'taxonomy_vocabulary:5'], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

}
