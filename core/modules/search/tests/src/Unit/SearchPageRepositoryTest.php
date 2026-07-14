<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\search\Entity\SearchPage;
use Drupal\search\SearchPageInterface;
use Drupal\search\SearchPageRepository;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests Drupal\search\SearchPageRepository.
 */
#[CoversClass(SearchPageRepository::class)]
#[Group('search')]
class SearchPageRepositoryTest extends UnitTestCase {

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepository
   */
  protected $searchPageRepository;

  /**
   * The entity query object.
   */
  protected QueryInterface&MockObject $query;

  /**
   * The search page storage.
   */
  protected ConfigEntityStorageInterface&MockObject $storage;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface&MockObject $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->query = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');

    $this->storage = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $this->storage
      ->method('getQuery')
      ->willReturn($this->query);

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\Stub $entity_type_manager */
    $entity_type_manager = $this->createStub(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->method('getStorage')
      ->willReturn($this->storage);

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->searchPageRepository = new SearchPageRepository($this->configFactory, $entity_type_manager);
  }

  /**
   * Tests the getActiveSearchPages() method.
   */
  public function testGetActiveSearchPages(): void {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->willReturn($this->query);
    $this->query->expects($this->once())
      ->method('execute')
      ->willReturn(['test' => 'test', 'other_test' => 'other_test']);

    $entities = [];
    $entities['test'] = $this->createStub(SearchPageInterface::class);
    $entities['other_test'] = $this->createStub(SearchPageInterface::class);
    $this->storage->expects($this->once())
      ->method('loadMultiple')
      ->with(['test' => 'test', 'other_test' => 'other_test'])
      ->willReturn($entities);
    $this->configFactory->expects($this->never())
      ->method('get');

    $result = $this->searchPageRepository->getActiveSearchPages();
    $this->assertSame($entities, $result);
  }

  /**
   * Tests the isSearchActive() method.
   */
  public function testIsSearchActive(): void {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->willReturn($this->query);
    $this->query->expects($this->once())
      ->method('range')
      ->with(0, 1)
      ->willReturn($this->query);
    $this->query->expects($this->once())
      ->method('execute')
      ->willReturn(['test' => 'test']);
    $this->storage->expects($this->never())
      ->method('loadMultiple');
    $this->configFactory->expects($this->never())
      ->method('get');

    $this->assertTrue($this->searchPageRepository->isSearchActive());
  }

  /**
   * Tests the getIndexableSearchPages() method.
   */
  public function testGetIndexableSearchPages(): void {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->willReturn($this->query);
    $this->query->expects($this->once())
      ->method('execute')
      ->willReturn(['test' => 'test', 'other_test' => 'other_test']);

    $entities = [];
    $entities['test'] = $this->createMock('Drupal\search\SearchPageInterface');
    $entities['test']->expects($this->once())
      ->method('isIndexable')
      ->willReturn(TRUE);
    $entities['other_test'] = $this->createMock('Drupal\search\SearchPageInterface');
    $entities['other_test']->expects($this->once())
      ->method('isIndexable')
      ->willReturn(FALSE);
    $this->storage->expects($this->once())
      ->method('loadMultiple')
      ->with(['test' => 'test', 'other_test' => 'other_test'])
      ->willReturn($entities);
    $this->configFactory->expects($this->never())
      ->method('get');

    $result = $this->searchPageRepository->getIndexableSearchPages();
    $this->assertCount(1, $result);
    $this->assertSame($entities['test'], reset($result));
  }

  /**
   * Tests the clearDefaultSearchPage() method.
   */
  public function testClearDefaultSearchPage(): void {
    $this->query->expects($this->never())
      ->method('execute');
    $this->storage->expects($this->never())
      ->method('loadMultiple');
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('clear')
      ->with('default_page')
      ->willReturn($config);
    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('search.settings')
      ->willReturn($config);
    $this->searchPageRepository->clearDefaultSearchPage();
  }

  /**
   * Tests the getDefaultSearchPage() method when the default is active.
   */
  public function testGetDefaultSearchPageWithActiveDefault(): void {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->willReturn($this->query);
    $this->query->expects($this->once())
      ->method('execute')
      ->willReturn(['test' => 'test', 'other_test' => 'other_test']);

    $this->storage->expects($this->never())
      ->method('loadMultiple');
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('get')
      ->with('default_page')
      ->willReturn('test');
    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('search.settings')
      ->willReturn($config);

    $this->assertSame('test', $this->searchPageRepository->getDefaultSearchPage());
  }

  /**
   * Tests the getDefaultSearchPage() method when the default is inactive.
   */
  public function testGetDefaultSearchPageWithInactiveDefault(): void {
    $this->query->expects($this->once())
      ->method('condition')
      ->with('status', TRUE)
      ->willReturn($this->query);
    $this->query->expects($this->once())
      ->method('execute')
      ->willReturn(['test' => 'test']);

    $this->storage->expects($this->never())
      ->method('loadMultiple');
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('get')
      ->with('default_page')
      ->willReturn('other_test');
    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('search.settings')
      ->willReturn($config);

    $this->assertSame('test', $this->searchPageRepository->getDefaultSearchPage());
  }

  /**
   * Tests the setDefaultSearchPage() method.
   */
  public function testSetDefaultSearchPage(): void {
    $this->query->expects($this->never())
      ->method('execute');
    $this->storage->expects($this->never())
      ->method('loadMultiple');
    $id = 'bananas';
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('set')
      ->with('default_page', $id)
      ->willReturn($config);
    $config->expects($this->once())
      ->method('save')
      ->willReturn($config);
    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('search.settings')
      ->willReturn($config);

    $search_page = $this->createMock('Drupal\search\SearchPageInterface');
    $search_page->expects($this->once())
      ->method('id')
      ->willReturn($id);
    $search_page->expects($this->once())
      ->method('enable')
      ->willReturn($search_page);
    $search_page->expects($this->once())
      ->method('save')
      ->willReturn($search_page);
    $this->searchPageRepository->setDefaultSearchPage($search_page);
  }

  /**
   * Tests the sortSearchPages() method.
   */
  public function testSortSearchPages(): void {
    $this->query->expects($this->never())
      ->method('execute');
    $entity_type = $this->createStub(EntityTypeInterface::class);
    $entity_type
      ->method('getClass')
      ->willReturn(TestSearchPage::class);
    $this->storage->expects($this->once())
      ->method('getEntityType')
      ->willReturn($entity_type);
    $this->configFactory->expects($this->never())
      ->method('get');

    // Declare entities out of their expected order, so we can be sure they were
    // sorted.
    $entity_test4 = $this->createStub(TestSearchPage::class);
    $entity_test4
      ->method('label')
      ->willReturn('Test4');
    $entity_test4
      ->method('status')
      ->willReturn(FALSE);
    $entity_test4
      ->method('getWeight')
      ->willReturn(0);
    $entity_test3 = $this->createStub(TestSearchPage::class);
    $entity_test3
      ->method('label')
      ->willReturn('Test3');
    $entity_test3
      ->method('status')
      ->willReturn(FALSE);
    $entity_test3
      ->method('getWeight')
      ->willReturn(10);
    $entity_test2 = $this->createStub(TestSearchPage::class);
    $entity_test2
      ->method('label')
      ->willReturn('Test2');
    $entity_test2
      ->method('status')
      ->willReturn(TRUE);
    $entity_test2
      ->method('getWeight')
      ->willReturn(0);
    $entity_test1 = $this->createStub(TestSearchPage::class);
    $entity_test1
      ->method('label')
      ->willReturn('Test1');
    $entity_test1
      ->method('status')
      ->willReturn(TRUE);
    $entity_test1
      ->method('getWeight')
      ->willReturn(0);

    $unsorted_entities = [$entity_test4, $entity_test3, $entity_test2, $entity_test1];
    $expected = [$entity_test1, $entity_test2, $entity_test3, $entity_test4];

    $sorted_entities = $this->searchPageRepository->sortSearchPages($unsorted_entities);
    $this->assertSame($expected, array_values($sorted_entities));
  }

}

/**
 * Mock for the configured search page entity.
 */
class TestSearchPage extends SearchPage {

  public function __construct(array $values) {
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    return $this->label;
  }

}
