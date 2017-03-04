<?php

namespace Drupal\Tests\comment\Unit;

use Drupal\comment\CommentStatistics;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\comment\CommentStatistics
 * @group comment
 */
class CommentStatisticsUnitTest extends UnitTestCase {

  /**
   * Mock statement.
   *
   * @var \Drupal\Core\Database\Statement
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
  protected $calls_to_fetch;

  /**
   * Sets up required mocks and the CommentStatistics service under test.
   */
  protected function setUp() {
    $this->statement = $this->getMockBuilder('Drupal\Core\Database\Driver\sqlite\Statement')
      ->disableOriginalConstructor()
      ->getMock();

    $this->statement->expects($this->any())
      ->method('fetchObject')
      ->will($this->returnCallback([$this, 'fetchObjectCallback']));

    $this->select = $this->getMockBuilder('Drupal\Core\Database\Query\Select')
      ->disableOriginalConstructor()
      ->getMock();

    $this->select->expects($this->any())
      ->method('fields')
      ->will($this->returnSelf());

    $this->select->expects($this->any())
      ->method('condition')
      ->will($this->returnSelf());

    $this->select->expects($this->any())
      ->method('execute')
      ->will($this->returnValue($this->statement));

    $this->database = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->database->expects($this->once())
      ->method('select')
      ->will($this->returnValue($this->select));

    $this->commentStatistics = new CommentStatistics($this->database, $this->getMock('Drupal\Core\Session\AccountInterface'), $this->getMock('Drupal\Core\Entity\EntityManagerInterface'), $this->getMock('Drupal\Core\State\StateInterface'));
  }

  /**
   * Tests the read method.
   *
   * @see \Drupal\comment\CommentStatistics::read()
   *
   * @group Drupal
   * @group Comment
   */
  public function testRead() {
    $this->calls_to_fetch = 0;
    $results = $this->commentStatistics->read(['1' => 'boo', '2' => 'foo'], 'snafoos');
    $this->assertEquals($results, ['something', 'something-else']);
  }

  /**
   * Return value callback for fetchObject() function on mocked object.
   *
   * @return bool|string
   *   'Something' on first, 'something-else' on second and FALSE for the
   *   other calls to function.
   */
  public function fetchObjectCallback() {
    $this->calls_to_fetch++;
    switch ($this->calls_to_fetch) {
      case 1:
        return 'something';

      case 2:
        return 'something-else';

      default:
        return FALSE;
    }
  }

}
