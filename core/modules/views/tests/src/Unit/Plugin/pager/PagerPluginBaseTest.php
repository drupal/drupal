<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Plugin\pager\PagerPluginBaseTest.
 */

namespace Drupal\Tests\views\Unit\Plugin\pager;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Database\StatementInterface;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\pager\PagerPluginBase
 * @group views
 */
class PagerPluginBaseTest extends UnitTestCase {

  /**
   * The mock pager plugin instance.
   *
   * @var \Drupal\views\Plugin\views\pager\PagerPluginBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pager;

  protected function setUp() {
    $this->pager = $this->getMockBuilder('Drupal\views\Plugin\views\pager\PagerPluginBase')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $view = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $options = [
      'items_per_page' => 5,
      'offset' => 1,
    ];

    $this->pager->init($view, $display, $options);

    $this->pager->current_page = 1;
  }

  /**
   * Tests the getItemsPerPage() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::getItemsPerPage()
   */
  public function testGetItemsPerPage() {
    $this->assertEquals(5, $this->pager->getItemsPerPage());
  }

  /**
   * Tests the setItemsPerPage() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::setItemsPerPage()
   */
  public function testSetItemsPerPage() {
    $this->pager->setItemsPerPage(6);
    $this->assertEquals(6, $this->pager->getItemsPerPage());
  }

  /**
   * Tests the getOffset() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::getOffset()
   */
  public function testGetOffset() {
    $this->assertEquals(1, $this->pager->getOffset());
  }

  /**
   * Tests the setOffset() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::setOffset()
   */
  public function testSetOffset() {
    $this->pager->setOffset(2);
    $this->assertEquals(2, $this->pager->getOffset());
  }

  /**
   * Tests the getCurrentPage() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::getCurrentPage()
   */
  public function testGetCurrentPage() {
    $this->assertEquals(1, $this->pager->getCurrentPage());
  }

  /**
   * Tests the setCurrentPage() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::setCurrentPage()
   */
  public function testSetCurrentPage() {
    $this->pager->setCurrentPage(2);
    $this->assertEquals(2, $this->pager->getCurrentPage());

    // A non numeric number or number below 0 should return 0.
    $this->pager->setCurrentPage('two');
    $this->assertEquals(0, $this->pager->getCurrentPage());

    $this->pager->setCurrentPage(-2);
    $this->assertEquals(0, $this->pager->getCurrentPage());
  }

  /**
   * Tests the getTotalItems() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::getTotalItems()
   */
  public function testGetTotalItems() {
    // Should return 0 by default.
    $this->assertEquals(0, $this->pager->getTotalItems());

    $this->pager->total_items = 10;
    $this->assertEquals(10, $this->pager->getTotalItems());
  }

  /**
   * Tests the getPagerId() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::getPagerId()
   */
  public function testGetPagerId() {
    // Should return 0 if 'id' is not set.
    $this->assertEquals(0, $this->pager->getPagerId());

    $this->pager->options['id'] = 1;

    $this->assertEquals(1, $this->pager->getPagerId());
  }

  /**
   * Tests the usePager() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::usePager()
   */
  public function testUsePager() {
    $this->assertTrue($this->pager->usePager());
  }

  /**
   * Tests the useCountQuery() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::useCountQuery()
   */
  public function testUseCountQuery() {
    $this->assertTrue($this->pager->useCountQuery());
  }

  /**
   * Tests the usesExposed() method.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::usedExposed()
   */
  public function testUsesExposed() {
    $this->assertFalse($this->pager->usesExposed());
  }

  /**
   * Tests the hasMoreRecords() method.
   *
   * @dataProvider providerTestHasMoreRecords
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::hasMoreRecords()
   */
  public function testHasMoreRecords($items_per_page, $total_items, $current_page, $has_more_records) {
    $this->pager->setItemsPerPage($items_per_page);
    $this->pager->total_items = $total_items;
    $this->pager->setCurrentPage($current_page);
    $this->assertEquals($has_more_records, $this->pager->hasMoreRecords());
  }

  /**
   * Provides test data for the hasMoreRecord method test.
   *
   * @see self::testHasMoreRecords
   */
  public function providerTestHasMoreRecords() {
    return [
      // No items per page, so there can't be more available records.
      [0, 0, 0, FALSE],
      [0, 10, 0, FALSE],
      // The amount of total items equals the items per page, so there is no
      // next page available.
      [5, 5, 0, FALSE],
      // There is one more item, and we are at the first page.
      [5, 6, 0, TRUE],
      // Now we are on the second page, which has just a single one left.
      [5, 6, 1, FALSE],
      // Increase the total items, so we have some available on the third page.
      [5, 12, 1, TRUE]
    ];
  }

  /**
   * Tests the executeCountQuery method without a set offset.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::executeCountQuery()
   */
  public function testExecuteCountQueryWithoutOffset() {
    $statement = $this->getMock('\Drupal\Tests\views\Unit\Plugin\pager\TestStatementInterface');

    $statement->expects($this->once())
      ->method('fetchField')
      ->will($this->returnValue(3));

    $query = $this->getMockBuilder('\Drupal\Core\Database\Query\Select')
      ->disableOriginalConstructor()
      ->getMock();

    $query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue($statement));

    $this->pager->setOffset(0);
    $this->assertEquals(3, $this->pager->executeCountQuery($query));
  }

  /**
   * Tests the executeCountQuery method with a set offset.
   *
   * @see \Drupal\views\Plugin\views\pager\PagerPluginBase::executeCountQuery()
   */
  public function testExecuteCountQueryWithOffset() {
    $statement = $this->getMock('\Drupal\Tests\views\Unit\Plugin\pager\TestStatementInterface');

    $statement->expects($this->once())
      ->method('fetchField')
      ->will($this->returnValue(3));

    $query = $this->getMockBuilder('\Drupal\Core\Database\Query\Select')
      ->disableOriginalConstructor()
      ->getMock();

    $query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue($statement));

    $this->pager->setOffset(2);
    $this->assertEquals(1, $this->pager->executeCountQuery($query));
  }

}

/**
 * As StatementInterface extends \Traversable, which though always needs
 * an additional interface. The Statement class itself can't be mocked because
 * of its __wakeup function.
 */
interface TestStatementInterface extends StatementInterface, \Iterator {}
