<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Unit;

use Drupal\comment\CommentStatistics;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\comment\CommentStatistics
 * @group comment
 */
class CommentStatisticsUnitTest extends UnitTestCase {

  /**
   * Mock statement.
   *
   * @var \Drupal\Core\Database\StatementInterface
   */
  protected $statement;

  /**
   * Mock select interface.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $select;

  /**
   * Mock database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * CommentStatistics service under test.
   *
   * @var \Drupal\comment\CommentStatisticsInterface
   */
  protected $commentStatistics;

  /**
   * Counts calls to fetchAssoc().
   *
   * @var int
   */
  protected $callsToFetch;

  /**
   * Sets up required mocks and the CommentStatistics service under test.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->statement = $this->getMockBuilder('Drupal\sqlite\Driver\Database\sqlite\Statement')
      ->disableOriginalConstructor()
      ->getMock();

    $this->statement->expects($this->any())
      ->method('fetchObject')
      ->willReturnCallback([$this, 'fetchObjectCallback']);

    $this->select = $this->getMockBuilder('Drupal\Core\Database\Query\Select')
      ->disableOriginalConstructor()
      ->getMock();

    $this->select->expects($this->any())
      ->method('fields')
      ->willReturnSelf();

    $this->select->expects($this->any())
      ->method('condition')
      ->willReturnSelf();

    $this->select->expects($this->any())
      ->method('execute')
      ->willReturn($this->statement);

    $this->database = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->database->expects($this->once())
      ->method('select')
      ->willReturn($this->select);

    $this->commentStatistics = new CommentStatistics($this->database, $this->createMock('Drupal\Core\Session\AccountInterface'), $this->createMock(EntityTypeManagerInterface::class), $this->createMock('Drupal\Core\State\StateInterface'), $this->createMock(TimeInterface::class), $this->database);
  }

  /**
   * Tests the read method.
   *
   * @see \Drupal\comment\CommentStatistics::read()
   *
   * @group Drupal
   * @group Comment
   */
  public function testRead(): void {
    $this->callsToFetch = 0;
    $results = $this->commentStatistics->read(['1' => 'boo', '2' => 'foo'], 'snafus');
    $this->assertEquals(['something', 'something-else'], $results);
  }

  /**
   * Return value callback for fetchObject() function on mocked object.
   *
   * @return bool|string
   *   'Something' on first, 'something-else' on second and FALSE for the
   *   other calls to function.
   */
  public function fetchObjectCallback() {
    $this->callsToFetch++;
    switch ($this->callsToFetch) {
      case 1:
        return 'something';

      case 2:
        return 'something-else';

      default:
        return FALSE;
    }
  }

}
