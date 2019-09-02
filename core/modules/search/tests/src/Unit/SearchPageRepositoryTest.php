<?php

/**
 * @file
 * Contains \Drupal\Tests\search\Unit\SearchPageRepositoryTest.
 */

namespace Drupal\Tests\search\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search\Entity\SearchPage;
use Drupal\search\SearchPageRepository;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\search\SearchPageRepository
 * @group search
 */
class SearchPageRepositoryTest extends UnitTestCase {

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepository
   */
  protected $searchPageRepository;

  /**
   * The entity query object.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $query;

  /**
   * The search page storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->query = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');

    $this->storage = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $this->storage->expects($this->any())
      ->method('getQuery')
      ->will($this->returnValue($this->query));

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entity_type_manager */
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($this->storage));

    $this->configFactory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $this->searchPageRepository = new SearchPageRepository($this->configFactory, $entity_type_manager);
  }

  /**
   * Tests the getActiveSearchPages() method.
   */
  public function testGetActiveSearchPages() {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->will($this->returnValue($this->query));
    $this->query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(['test' => 'test', 'other_test' => 'other_test']));

    $entities = [];
    $entities['test'] = $this->createMock('Drupal\search\SearchPageInterface');
    $entities['other_test'] = $this->createMock('Drupal\search\SearchPageInterface');
    $this->storage->expects($this->once())
      ->method('loadMultiple')
      ->with(['test' => 'test', 'other_test' => 'other_test'])
      ->will($this->returnValue($entities));

    $result = $this->searchPageRepository->getActiveSearchPages();
    $this->assertSame($entities, $result);
  }

  /**
   * Tests the isSearchActive() method.
   */
  public function testIsSearchActive() {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->will($this->returnValue($this->query));
    $this->query->expects($this->once())
      ->method('range')
      ->with(0, 1)
      ->will($this->returnValue($this->query));
    $this->query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(['test' => 'test']));

    $this->assertSame(TRUE, $this->searchPageRepository->isSearchActive());
  }

  /**
   * Tests the getIndexableSearchPages() method.
   */
  public function testGetIndexableSearchPages() {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->will($this->returnValue($this->query));
    $this->query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(['test' => 'test', 'other_test' => 'other_test']));

    $entities = [];
    $entities['test'] = $this->createMock('Drupal\search\SearchPageInterface');
    $entities['test']->expects($this->once())
      ->method('isIndexable')
      ->will($this->returnValue(TRUE));
    $entities['other_test'] = $this->createMock('Drupal\search\SearchPageInterface');
    $entities['other_test']->expects($this->once())
      ->method('isIndexable')
      ->will($this->returnValue(FALSE));
    $this->storage->expects($this->once())
      ->method('loadMultiple')
      ->with(['test' => 'test', 'other_test' => 'other_test'])
      ->will($this->returnValue($entities));

    $result = $this->searchPageRepository->getIndexableSearchPages();
    $this->assertCount(1, $result);
    $this->assertSame($entities['test'], reset($result));
  }

  /**
   * Tests the clearDefaultSearchPage() method.
   */
  public function testClearDefaultSearchPage() {
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('clear')
      ->with('default_page')
      ->will($this->returnValue($config));
    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('search.settings')
      ->will($this->returnValue($config));
    $this->searchPageRepository->clearDefaultSearchPage();
  }

  /**
   * Tests the getDefaultSearchPage() method when the default is active.
   */
  public function testGetDefaultSearchPageWithActiveDefault() {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->will($this->returnValue($this->query));
    $this->query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(['test' => 'test', 'other_test' => 'other_test']));

    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('get')
      ->with('default_page')
      ->will($this->returnValue('test'));
    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('search.settings')
      ->will($this->returnValue($config));

    $this->assertSame('test', $this->searchPageRepository->getDefaultSearchPage());
  }

  /**
   * Tests the getDefaultSearchPage() method when the default is inactive.
   */
  public function testGetDefaultSearchPageWithInactiveDefault() {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->will($this->returnValue($this->query));
    $this->query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(['test' => 'test']));

    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('get')
      ->with('default_page')
      ->will($this->returnValue('other_test'));
    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('search.settings')
      ->will($this->returnValue($config));

    $this->assertSame('test', $this->searchPageRepository->getDefaultSearchPage());
  }

  /**
   * Tests the setDefaultSearchPage() method.
   */
  public function testSetDefaultSearchPage() {
    $id = 'bananas';
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('set')
      ->with('default_page', $id)
      ->will($this->returnValue($config));
    $config->expects($this->once())
      ->method('save')
      ->will($this->returnValue($config));
    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('search.settings')
      ->will($this->returnValue($config));

    $search_page = $this->createMock('Drupal\search\SearchPageInterface');
    $search_page->expects($this->once())
      ->method('id')
      ->will($this->returnValue($id));
    $search_page->expects($this->once())
      ->method('enable')
      ->will($this->returnValue($search_page));
    $search_page->expects($this->once())
      ->method('save')
      ->will($this->returnValue($search_page));
    $this->searchPageRepository->setDefaultSearchPage($search_page);
  }

  /**
   * Tests the sortSearchPages() method.
   */
  public function testSortSearchPages() {
    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue('Drupal\Tests\search\Unit\TestSearchPage'));
    $this->storage->expects($this->once())
      ->method('getEntityType')
      ->will($this->returnValue($entity_type));

    // Declare entities out of their expected order so we can be sure they were
    // sorted. We cannot mock these because of uasort(), see
    // https://bugs.php.net/bug.php?id=50688.
    $unsorted_entities['test4'] = new TestSearchPage(['weight' => 0, 'status' => FALSE, 'label' => 'Test4']);
    $unsorted_entities['test3'] = new TestSearchPage(['weight' => 10, 'status' => TRUE, 'label' => 'Test3']);
    $unsorted_entities['test2'] = new TestSearchPage(['weight' => 0, 'status' => TRUE, 'label' => 'Test2']);
    $unsorted_entities['test1'] = new TestSearchPage(['weight' => 0, 'status' => TRUE, 'label' => 'Test1']);
    $expected = $unsorted_entities;
    ksort($expected);

    $sorted_entities = $this->searchPageRepository->sortSearchPages($unsorted_entities);
    $this->assertSame($expected, $sorted_entities);
  }

}

class TestSearchPage extends SearchPage {

  public function __construct(array $values) {
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  public function label($langcode = NULL) {
    return $this->label;
  }

}
