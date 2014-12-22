<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\HtmlTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Tests\UnitTestCase;

/**
 * Tests \Drupal\Component\Utility\Html.
 *
 * @group Common
 *
 * @coversDefaultClass \Drupal\Component\Utility\Html
 */
class HtmlTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $property = new \ReflectionProperty('Drupal\Component\Utility\Html', 'seenIdsInit');
    $property->setAccessible(TRUE);
    $property->setValue(NULL);
  }

  /**
   * Tests the Html::cleanCssIdentifier() method.
   *
   * @param string $expected
   *   The expected result.
   * @param string $source
   *   The string being transformed to an ID.
   * @param array|null $filter
   *   (optional) An array of string replacements to use on the identifier. If
   *   NULL, no filter will be passed and a default will be used.
   *
   * @dataProvider providerTestCleanCssIdentifier
   *
   * @covers ::cleanCssIdentifier
   */
  public function testCleanCssIdentifier($expected, $source, $filter = NULL) {
    if ($filter !== NULL) {
      $this->assertSame($expected, Html::cleanCssIdentifier($source, $filter));
    }
    else {
      $this->assertSame($expected, Html::cleanCssIdentifier($source));
    }
  }

  /**
   * Provides test data for testCleanCssIdentifier().
   *
   * @return array
   *   Test data.
   */
  public function providerTestCleanCssIdentifier() {
    $id1 = 'abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ-0123456789';
    $id2 = '¡¢£¤¥';
    $id3 = 'css__identifier__with__double__underscores';
    return array(
      // Verify that no valid ASCII characters are stripped from the identifier.
      array($id1, $id1, array()),
      // Verify that valid UTF-8 characters are not stripped from the identifier.
      array($id2, $id2, array()),
      // Verify that invalid characters (including non-breaking space) are stripped from the identifier.
      array($id3, $id3),
      // Verify that double underscores are not stripped from the identifier.
      array('invalididentifier', 'invalid !"#$%&\'()*+,./:;<=>?@[\\]^`{|}~ identifier', array()),
      // Verify that an identifier starting with a digit is replaced.
      array('_cssidentifier', '1cssidentifier', array()),
      // Verify that an identifier starting with a hyphen followed by a digit is
      // replaced.
      array('__cssidentifier', '-1cssidentifier', array()),
      // Verify that an identifier starting with two hyphens is replaced.
      array('__cssidentifier', '--cssidentifier', array())
    );
  }

  /**
   * Tests that Html::getClass() cleans the class name properly.
   *
   * @coversDefaultClass ::getClass
   */
  public function testHtmlClass() {
    // Verify Drupal coding standards are enforced.
    $this->assertSame(Html::getClass('CLASS NAME_[Ü]'), 'class-name--ü', 'Enforce Drupal coding standards.');
  }

  /**
   * Tests the Html::getUniqueId() method.
   *
   * @param string $expected
   *   The expected result.
   * @param string $source
   *   The string being transformed to an ID.
   * @param bool $reset
   *   (optional) If TRUE, reset the list of seen IDs. Defaults to FALSE.
   *
   * @dataProvider providerTestHtmlGetUniqueId
   *
   * @covers ::getUniqueId
   */
  public function testHtmlGetUniqueId($expected, $source, $reset = FALSE) {
    if ($reset) {
      Html::resetSeenIds();
    }
    $this->assertSame($expected, Html::getUniqueId($source));
  }

  /**
   * Provides test data for testHtmlGetId().
   *
   * @return array
   *   Test data.
   */
  public function providerTestHtmlGetUniqueId() {
    $id = 'abcdefghijklmnopqrstuvwxyz-0123456789';
    return array(
      // Verify that letters, digits, and hyphens are not stripped from the ID.
      array($id, $id),
      // Verify that invalid characters are stripped from the ID.
      array('invalididentifier', 'invalid,./:@\\^`{Üidentifier'),
      // Verify Drupal coding standards are enforced.
      array('id-name-1', 'ID NAME_[1]'),
      // Verify that a repeated ID is made unique.
      array('test-unique-id', 'test-unique-id', TRUE),
      array('test-unique-id--2', 'test-unique-id'),
      array('test-unique-id--3', 'test-unique-id'),
    );
  }

  /**
   * Tests the Html::getUniqueId() method.
   *
   * @param string $expected
   *   The expected result.
   * @param string $source
   *   The string being transformed to an ID.
   * @param bool $reset
   *   (optional) If TRUE, reset the list of seen IDs. Defaults to FALSE.
   *
   * @dataProvider providerTestHtmlGetUniqueIdWithAjaxIds
   *
   * @covers ::getUniqueId
   */
  public function testHtmlGetUniqueIdWithAjaxIds($expected, $source, $reset = FALSE) {
    if ($reset) {
      Html::resetSeenIds();
    }
    Html::setAjaxHtmlIds('test-unique-id1 test-unique-id2--3');
    $this->assertSame($expected, Html::getUniqueId($source));
  }

  /**
   * Provides test data for testHtmlGetId().
   *
   * @return array
   *   Test data.
   */
  public function providerTestHtmlGetUniqueIdWithAjaxIds() {
    return array(
      array('test-unique-id1--2', 'test-unique-id1', TRUE),
      array('test-unique-id1--3', 'test-unique-id1'),
      array('test-unique-id2--4', 'test-unique-id2', TRUE),
      array('test-unique-id2--5', 'test-unique-id2'),
    );
  }

  /**
   * Tests the Html::getUniqueId() method.
   *
   * @param string $expected
   *   The expected result.
   * @param string $source
   *   The string being transformed to an ID.
   *
   * @dataProvider providerTestHtmlGetId
   *
   * @covers ::getId
   */
  public function testHtmlGetId($expected, $source) {
    $this->assertSame($expected, Html::getId($source));
  }

  /**
   * Provides test data for testHtmlGetId().
   *
   * @return array
   *   Test data.
   */
  public function providerTestHtmlGetId() {
    $id = 'abcdefghijklmnopqrstuvwxyz-0123456789';
    return array(
      // Verify that letters, digits, and hyphens are not stripped from the ID.
      array($id, $id),
      // Verify that invalid characters are stripped from the ID.
      array('invalididentifier', 'invalid,./:@\\^`{Üidentifier'),
      // Verify Drupal coding standards are enforced.
      array('id-name-1', 'ID NAME_[1]'),
      // Verify that a repeated ID is made unique.
      array('test-unique-id', 'test-unique-id'),
      array('test-unique-id', 'test-unique-id'),
    );
  }

}
