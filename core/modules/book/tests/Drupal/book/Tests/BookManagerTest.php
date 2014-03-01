<?php

/**
 * @file
 * Contains \Drupal\book\Tests\BookManagerTest.
 */

namespace Drupal\book\Tests;

use Drupal\book\BookManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the book manager.
 *
 * @group Drupal
 * @group Book
 *
 * @coversDefaultClass \Drupal\book\BookManager
 */
class BookManagerTest extends UnitTestCase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The mocked translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translation;

  /**
   * The tested book manager.
   *
   * @var \Drupal\book\BookManager
   */
  protected $bookManager;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Book manager',
      'description' => 'Test the book manager.',
      'group' => 'Book',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->translation = $this->getStringTranslationStub();
    $this->configFactory = $this->getConfigFactoryStub(array());
    $this->bookManager = new BookManager($this->connection, $this->entityManager, $this->translation, $this->configFactory);
  }

  /**
   * Tests the getBookParents() method.
   *
   * @dataProvider providerTestGetBookParents
   */
  public function testGetBookParents($book, $parent, $expected) {
    $this->assertEquals($expected, $this->bookManager->getBookParents($book, $parent));
  }

  /**
   * Provides test data for testGetBookParents.
   *
   * @return array
   *   The test data.
   */
  public function providerTestGetBookParents() {
    $empty = array(
      'p1' => 0,
      'p2' => 0,
      'p3' => 0,
      'p4' => 0,
      'p5' => 0,
      'p6' => 0,
      'p7' => 0,
      'p8' => 0,
      'p9' => 0,
    );
    return array(
      // Provides a book without an existing parent.
      array(
        array('pid' => 0, 'nid' => 12),
        array(),
        array('depth' => 1, 'p1' => 12) + $empty,
      ),
      // Provides a book with an existing parent.
      array(
        array('pid' => 11, 'nid' => 12),
        array('nid' => 11, 'depth' => 1, 'p1' => 11,),
        array('depth' => 2, 'p1' => 11, 'p2' => 12) + $empty,
      ),
      // Provides a book with two existing parents.
      array(
        array('pid' => 11, 'nid' => 12),
        array('nid' => 11, 'depth' => 2, 'p1' => 10, 'p2' => 11),
        array('depth' => 3, 'p1' => 10, 'p2' => 11, 'p3' => 12) + $empty,
      ),
    );
  }

}
