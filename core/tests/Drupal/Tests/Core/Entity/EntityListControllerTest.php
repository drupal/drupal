<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityListControllerTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\EntityTestListController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the entity list controller.
 *
 * @group Entity
 *
 * @see \Drupal\entity_test\EntityTestListController
 */
class EntityListControllerTest extends UnitTestCase {

  /**
   * The entity used to construct the EntityListController.
   *
   * @var \Drupal\user\RoleInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $role;

  /**
   * The EntityListController object to test.
   *
   * @var \Drupal\Core\Entity\EntityListController
   */
  protected $entityListController;

  public static function getInfo() {
    return array(
      'name' => 'Entity list controller test',
      'description' => 'Unit test of entity access checking system.',
      'group' => 'Entity'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->role = $this->getMock('Drupal\user\RoleInterface');
    $role_storage_controller = $this->getMock('Drupal\user\RoleStorageControllerInterface');
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $this->entityListController = new TestEntityListController($entity_type, $role_storage_controller, $module_handler);
  }

  /**
   * Tests that buildRow() returns a string which has been run through
   * String::checkPlain().
   *
   * @dataProvider providerTestBuildRow
   *
   * @param string $input
   *  The entity label being passed into buildRow.
   * @param string $expected
   *  The expected output of the label from buildRow.
   * @param string $message
   *   The message to provide as output for the test.
   * @param bool $ignorewarnings
   *   Whether or not to ignore PHP 5.3+ invalid multibyte sequence warnings.
   *
   * @see \Drupal\Component\Utility\Drupal\Core\Entity\EntityListController::buildRow()
   */
  public function testBuildRow($input, $expected, $message, $ignorewarnings = FALSE) {
    $this->role->expects($this->any())
      ->method('label')
      ->will($this->returnValue($input));

    if ($ignorewarnings) {
      $built_row = @$this->entityListController->buildRow($this->role);
    }
    else {
      $built_row = $this->entityListController->buildRow($this->role);
    }

    $this->assertEquals($built_row['label'], $expected, $message);
  }

  /**
   * Data provider for testBuildRow().
   *
   * @see self::testBuildRow()
   * @see \Drupal\Tests\Component\Utility\StringTest::providerCheckPlain()
   *
   * @return array
   *   An array containing a string, the expected return from
   *   String::checkPlain, a message to be output for failures, and whether the
   *   test should be processed as multibyte.
   */
  public function providerTestBuildRow() {
    $tests = array();
    // Checks that invalid multi-byte sequences are rejected.
    $tests[] = array("Foo\xC0barbaz", '', 'EntityTestListController::buildRow() rejects invalid sequence "Foo\xC0barbaz"', TRUE);
    $tests[] = array("\xc2\"", '', 'EntityTestListController::buildRow() rejects invalid sequence "\xc2\""', TRUE);
    $tests[] = array("Fooÿñ", "Fooÿñ", 'EntityTestListController::buildRow() accepts valid sequence "Fooÿñ"');

    // Checks that special characters are escaped.
    $tests[] = array("<script>", '&lt;script&gt;', 'EntityTestListController::buildRow() escapes &lt;script&gt;');
    $tests[] = array('<>&"\'', '&lt;&gt;&amp;&quot;&#039;', 'EntityTestListController::buildRow() escapes reserved HTML characters.');

    return $tests;

  }

}

class TestEntityListController extends EntityTestListController {
  public function buildOperations(EntityInterface $entity) {
    return array();
  }
}
