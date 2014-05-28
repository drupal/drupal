<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentStatisticsTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\CommentStatistics;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CommentStatistics service.
 *
 * @see \Drupal\comment\CommentStatistics
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

  public static function getInfo() {
    return array(
      'name' => 'Comment statistics test',
      'description' => 'Tests the comment statistics service.',
      'group' => 'Comment',
    );
  }

  /**
   * Sets up required mocks and the CommentStatistics service under test.
   */
  public function setUp() {
    $this->statement = $this->getMockBuilder('Drupal\Core\Database\Driver\fake\FakeStatement')
      ->disableOriginalConstructor()
      ->getMock();

    $this->statement->expects($this->any())
      ->method('fetchAllAssoc')
      ->will($this->returnValue(array('1' => 'something', '2' => 'something-else')));

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
    $results = $this->commentStatistics->read(array('1' => 'boo', '2' => 'foo'), 'snafoos');
    $this->assertEquals($results, array('1' => 'something', '2' => 'something-else'));
  }

}
