<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\Query\Select;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the orderBy() method of select queries.
 *
 * @group Database
 */
class OrderByTest extends UnitTestCase {

  /**
   * The select query object to test.
   *
   * @var \Drupal\Core\Database\Query\Select
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $mockPdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mockPdo, []);
    $this->query = new Select($connection, 'test', NULL);
  }

  /**
   * Checks that invalid sort directions in ORDER BY get converted to ASC.
   */
  public function testInvalidDirection() {
    $this->query->orderBy('test', 'invalid direction');
    $order_bys = $this->query->getOrderBy();
    $this->assertEquals('ASC', $order_bys['test'], 'Invalid order by direction is converted to ASC.');
  }

  /**
   * Tests that fields passed for ordering get escaped properly.
   */
  public function testFieldEscaping() {
    $this->query->orderBy('x; DROP table node; --');
    $sql = $this->query->__toString();
    $this->assertStringEndsWith('ORDER BY xDROPtablenode ASC', $sql, 'Order by field is escaped correctly.');
  }

}
