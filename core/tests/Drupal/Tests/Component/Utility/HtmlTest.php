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
      array('__cssidentifier', '--cssidentifier', array()),
      // Verify that passing double underscores as a filter is processed.
      array('_cssidentifier', '__cssidentifier',  array('__' => '_')),
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
   *
   * @dataProvider providerTestHtmlGetUniqueIdWithAjaxIds
   *
   * @covers ::getUniqueId
   */
  public function testHtmlGetUniqueIdWithAjaxIds($expected, $source) {
    Html::setIsAjax(TRUE);
    $id = Html::getUniqueId($source);

    // Note, we truncate two hyphens at the end.
    // @see \Drupal\Component\Utility\Html::getId()
    if (strpos($source, '--') !== FALSE) {
      $random_suffix = substr($id, strlen($source) + 1);
    }
    else {
      $random_suffix = substr($id, strlen($source) + 2);
    }
    $expected = $expected . $random_suffix;
    $this->assertSame($expected, $id);
  }

  /**
   * Provides test data for testHtmlGetId().
   *
   * @return array
   *   Test data.
   */
  public function providerTestHtmlGetUniqueIdWithAjaxIds() {
    return array(
      array('test-unique-id1--', 'test-unique-id1'),
      // Note, we truncate two hyphens at the end.
      // @see \Drupal\Component\Utility\Html::getId()
      array('test-unique-id1---', 'test-unique-id1--'),
      array('test-unique-id2--', 'test-unique-id2'),
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
    Html::setIsAjax(FALSE);
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

  /**
   * Tests Html::decodeEntities().
   *
   * @dataProvider providerDecodeEntities
   * @covers ::decodeEntities
   */
  public function testDecodeEntities($text, $expected) {
    $this->assertEquals($expected, Html::decodeEntities($text));
  }

  /**
   * Data provider for testDecodeEntities().
   *
   * @see testDecodeEntities()
   */
  public function providerDecodeEntities() {
    return array(
      array('Drupal', 'Drupal'),
      array('<script>', '<script>'),
      array('&lt;script&gt;', '<script>'),
      array('&#60;script&#62;', '<script>'),
      array('&amp;lt;script&amp;gt;', '&lt;script&gt;'),
      array('"', '"'),
      array('&#34;', '"'),
      array('&amp;#34;', '&#34;'),
      array('&quot;', '"'),
      array('&amp;quot;', '&quot;'),
      array("'", "'"),
      array('&#39;', "'"),
      array('&amp;#39;', '&#39;'),
      array('©', '©'),
      array('&copy;', '©'),
      array('&#169;', '©'),
      array('→', '→'),
      array('&#8594;', '→'),
      array('➼', '➼'),
      array('&#10172;', '➼'),
      array('&euro;', '€'),
    );
  }

  /**
   * Tests Html::escape().
   *
   * @dataProvider providerEscape
   * @covers ::escape
   */
  public function testEscape($expected, $text) {
    $this->assertEquals($expected, Html::escape($text));
  }

  /**
   * Data provider for testEscape().
   *
   * @see testEscape()
   */
  public function providerEscape() {
    return array(
      array('Drupal', 'Drupal'),
      array('&lt;script&gt;', '<script>'),
      array('&amp;lt;script&amp;gt;', '&lt;script&gt;'),
      array('&amp;#34;', '&#34;'),
      array('&quot;', '"'),
      array('&amp;quot;', '&quot;'),
      array('&#039;', "'"),
      array('&amp;#039;', '&#039;'),
      array('©', '©'),
      array('→', '→'),
      array('➼', '➼'),
      array('€', '€'),
      array('Drup�al', "Drup\x80al"),
    );
  }

  /**
   * Tests relationship between escaping and decoding HTML entities.
   *
   * @covers ::decodeEntities
   * @covers ::escape
   */
  public function testDecodeEntitiesAndEscape() {
    $string = "<em>répét&eacute;</em>";
    $escaped = Html::escape($string);
    $this->assertSame('&lt;em&gt;répét&amp;eacute;&lt;/em&gt;', $escaped);
    $decoded = Html::decodeEntities($escaped);
    $this->assertSame('<em>répét&eacute;</em>', $decoded);
    $decoded = Html::decodeEntities($decoded);
    $this->assertSame('<em>répété</em>', $decoded);
    $escaped = Html::escape($decoded);
    $this->assertSame('&lt;em&gt;répété&lt;/em&gt;', $escaped);
  }

  /**
   * Tests Html::serialize().
   *
   * Resolves an issue by where an empty DOMDocument object sent to serialization would
   * cause errors in getElementsByTagName() in the serialization function.
   *
   * @covers ::serialize
   */
  public function testSerialize() {
    $document = new \DOMDocument();
    $result = Html::serialize($document);
    $this->assertSame('', $result);
  }
}