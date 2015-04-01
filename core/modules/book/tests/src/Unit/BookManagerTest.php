<?php

/**
 * @file
 * Contains \Drupal\Tests\book\Unit\BookManagerTest.
 */

namespace Drupal\Tests\book\Unit;

use Drupal\book\BookManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\book\BookManager
 * @group book
 */
class BookManagerTest extends UnitTestCase {

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
   * The mocked renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $renderer;

  /**
   * The tested book manager.
   *
   * @var \Drupal\book\BookManager
   */
  protected $bookManager;

  /**
   * Book outline storage.
   *
   * @var \Drupal\book\BookOutlineStorageInterface
   */
  protected $bookOutlineStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->translation = $this->getStringTranslationStub();
    $this->configFactory = $this->getConfigFactoryStub(array());
    $this->bookOutlineStorage = $this->getMock('Drupal\book\BookOutlineStorageInterface');
    $this->renderer = $this->getMock('\Drupal\Core\Render\RendererInterface');
    $this->bookManager = new BookManager($this->entityManager, $this->translation, $this->configFactory, $this->bookOutlineStorage, $this->renderer);
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
