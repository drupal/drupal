<?php

namespace Drupal\Tests\Core\Assert;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Drupal\Component\Render\MarkupInterface;
use Drupal\FunctionalTests\AssertLegacyTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\WebAssert;
use PHPUnit\Framework\ExpectationFailedException;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\FunctionalTests\AssertLegacyTrait
 * @group Assert
 * @group legacy
 */
class AssertLegacyTraitTest extends UnitTestCase {

  use AssertLegacyTrait;

  /**
   * The mocked Mink session object used for testing.
   *
   * @var \Behat\Mink\Session|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $session;

  /**
   * The mocked page element used for testing.
   *
   * @var Behat\Mink\Element\DocumentElement|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $page;

  /**
   * The mocked web assert class.
   *
   * @var \Drupal\Tests\WebAssert|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $webAssert;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->page = $this->prophesize(DocumentElement::class);
    $this->session = $this->prophesize(Session::class);
    $this->session->getPage()->willReturn($this->page->reveal());
    $this->webAssert = $this->prophesize(WebAssert::class);
  }

  /**
   * @covers ::assertTextHelper
   */
  public function testAssertTextHelper() {
    $this->expectDeprecation('AssertLegacyTrait::assertTextHelper() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->pageTextContains() or $this->assertSession()->pageTextNotContains() instead. See https://www.drupal.org/node/3129738');
    $this->page->getContent()->willReturn('foo bar bar');
    $this->assertTextHelper('foo', FALSE);
  }

  /**
   * @covers ::assertRaw
   */
  public function testAssertRaw() {
    $this->expectDeprecation('Calling AssertLegacyTrait::assertRaw() with more that one argument is deprecated in drupal:8.2.0 and the method is removed from drupal:10.0.0. Use $this->assertSession()->responseContains() instead. See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $this->assertRaw('foo', '\'foo\' should be present.');
  }

  /**
   * @covers ::assertNoRaw
   */
  public function testAssertNoRaw() {
    $this->expectDeprecation('AssertLegacyTrait::assertNoRaw() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseNotContains() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('Calling AssertLegacyTrait::assertNoRaw() with more that one argument is deprecated in drupal:8.2.0 and the method is removed from drupal:10.0.0. Use $this->assertSession()->responseNotContains() instead. See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $this->assertNoRaw('qux', '\'qux\' should not be present.');
  }

  /**
   * @covers ::assertUniqueText
   */
  public function testAssertUniqueText() {
    $this->expectDeprecation('AssertLegacyTrait::assertUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->pageTextContainsOnce() or $this->getSession()->pageTextMatchesCount() instead. See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $this->assertUniqueText('foo');
  }

  /**
   * @covers ::assertUniqueText
   */
  public function testAssertUniqueTextFail() {
    $this->expectDeprecation('AssertLegacyTrait::assertUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->pageTextContainsOnce() or $this->getSession()->pageTextMatchesCount() instead. See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $this->expectException(ExpectationFailedException::class);
    $this->assertUniqueText('bar');
  }

  /**
   * @covers ::assertUniqueText
   */
  public function testAssertUniqueTextUnknown() {
    $this->expectDeprecation('AssertLegacyTrait::assertUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->pageTextContainsOnce() or $this->getSession()->pageTextMatchesCount() instead. See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $this->expectException(ExpectationFailedException::class);
    $this->assertUniqueText('alice');
  }

  /**
   * @covers ::assertUniqueText
   */
  public function testAssertUniqueTextMarkup() {
    $this->expectDeprecation('AssertLegacyTrait::assertUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->pageTextContainsOnce() or $this->getSession()->pageTextMatchesCount() instead. See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $markupObject = $this->prophesize(MarkupInterface::class);
    $markupObject->__toString()->willReturn('foo');
    $this->assertUniqueText($markupObject->reveal());
  }

  /**
   * @covers ::assertNoUniqueText
   */
  public function testAssertNoUniqueText() {
    $this->expectDeprecation('AssertLegacyTrait::assertNoUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Instead, use $this->getSession()->pageTextMatchesCount() if you know the cardinality in advance, or $this->getSession()->getPage()->getText() and substr_count(). See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $this->assertNoUniqueText('bar');
  }

  /**
   * @covers ::assertNoUniqueText
   */
  public function testAssertNoUniqueTextFail() {
    $this->expectDeprecation('AssertLegacyTrait::assertNoUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Instead, use $this->getSession()->pageTextMatchesCount() if you know the cardinality in advance, or $this->getSession()->getPage()->getText() and substr_count(). See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $this->expectException(ExpectationFailedException::class);
    $this->assertNoUniqueText('foo');
  }

  /**
   * @covers ::assertNoUniqueText
   */
  public function testAssertNoUniqueTextUnknown() {
    $this->expectDeprecation('AssertLegacyTrait::assertNoUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Instead, use $this->getSession()->pageTextMatchesCount() if you know the cardinality in advance, or $this->getSession()->getPage()->getText() and substr_count(). See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $this->expectException(ExpectationFailedException::class);
    $this->assertNoUniqueText('alice');
  }

  /**
   * @covers ::assertNoUniqueText
   */
  public function testAssertNoUniqueTextMarkup() {
    $this->expectDeprecation('AssertLegacyTrait::assertNoUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Instead, use $this->getSession()->pageTextMatchesCount() if you know the cardinality in advance, or $this->getSession()->getPage()->getText() and substr_count(). See https://www.drupal.org/node/3129738');
    $this->page->getText()->willReturn('foo bar bar');
    $markupObject = $this->prophesize(MarkupInterface::class);
    $markupObject->__toString()->willReturn('bar');
    $this->assertNoUniqueText($markupObject->reveal());
  }

  /**
   * @covers ::assertOptionSelected
   */
  public function testAssertOptionSelected() {
    $this->expectDeprecation('AssertLegacyTrait::assertOptionSelected() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionExists() instead and check the "selected" attribute. See https://www.drupal.org/node/3129738');
    $option_field = $this->prophesize(NodeElement::class);
    $option_field->hasAttribute('selected')->willReturn(TRUE);

    $this->webAssert
      ->optionExists('my_select', 'two')
      ->willReturn($option_field->reveal());

    $this->assertOptionSelected('my_select', 'two');
  }

  /**
   * @covers ::assertOptionSelected
   */
  public function testAssertOptionSelectedFail() {
    $this->expectDeprecation('AssertLegacyTrait::assertOptionSelected() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionExists() instead and check the "selected" attribute. See https://www.drupal.org/node/3129738');
    $option_field = $this->prophesize(NodeElement::class);
    $option_field->hasAttribute('selected')->willReturn(FALSE);

    $this->webAssert
      ->optionExists('my_select', 'two')
      ->willReturn($option_field->reveal());

    $this->expectException(ExpectationFailedException::class);
    $this->assertOptionSelected('my_select', 'two');
  }

  /**
   * @covers ::assertNoPattern
   */
  public function testAssertNoPattern() {
    $this->expectDeprecation('AssertLegacyTrait::assertNoPattern() is deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseNotMatches() instead. See https://www.drupal.org/node/3129738');
    $this->webAssert
      ->responseNotMatches('/.*foo$/')
      ->shouldBeCalled();

    $this->assertNoPattern('/.*foo$/');
  }

  /**
   * @covers ::assertCacheTag
   */
  public function testAssertCacheTag() {
    $this->expectDeprecation('AssertLegacyTrait::assertCacheTag() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderContains() instead. See https://www.drupal.org/node/3129738');
    $this->webAssert
      ->responseHeaderContains('X-Drupal-Cache-Tags', 'some-cache-tag')
      ->shouldBeCalled();

    $this->assertCacheTag('some-cache-tag');
  }

  /**
   * @covers ::assertNoCacheTag
   */
  public function testAssertNoCacheTag() {
    $this->expectDeprecation('AssertLegacyTrait::assertNoCacheTag() is deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderNotContains() instead. See https://www.drupal.org/node/3129738');
    $this->webAssert
      ->responseHeaderNotContains('X-Drupal-Cache-Tags', 'some-cache-tag')
      ->shouldBeCalled();

    $this->assertNoCacheTag('some-cache-tag');
  }

  /**
   * @covers ::assertUrl
   */
  public function testAssertUrl() {
    $this->expectDeprecation('AssertLegacyTrait::assertUrl() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->addressEquals() instead. See https://www.drupal.org/node/3129738');
    $this->expectDeprecation('Calling AssertLegacyTrait::assertUrl() with more than one argument is deprecated in drupal:8.2.0 and the method is removed from drupal:10.0.0. Use $this->assertSession()->addressEquals() instead. See https://www.drupal.org/node/3129738');
    $this->webAssert
      ->addressEquals('bingo')
      ->shouldBeCalled();

    $this->assertUrl('bingo', 'Redundant message.');
  }

  /**
   * @covers ::assertElementPresent
   */
  public function testAssertElementPresent() {
    $this->expectDeprecation('AssertLegacyTrait::assertElementPresent() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->elementExists() instead. See https://www.drupal.org/node/3129738');
    $this->webAssert
      ->elementExists('css', '.pager')
      ->shouldBeCalled();

    $this->assertElementPresent('.pager');
  }

  /**
   * @covers ::assertElementNotPresent
   */
  public function testAssertElementNotPresent() {
    $this->expectDeprecation('AssertLegacyTrait::assertElementNotPresent() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->elementNotExists() instead. See https://www.drupal.org/node/3129738');
    $this->webAssert
      ->elementNotExists('css', '.pager')
      ->shouldBeCalled();

    $this->assertElementNotPresent('.pager');
  }

  /**
   * @covers ::pass
   */
  public function testPass() {
    $this->expectDeprecation('AssertLegacyTrait::pass() is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. PHPUnit interrupts a test as soon as a test assertion fails, so there is usually no need to call this method. If a test\'s logic relies on this method, refactor the test. See https://www.drupal.org/node/3129738');
    $this->pass('Passed.');
  }

  /**
   * @covers ::assertLinkByHref
   */
  public function testAssertLinkByHref() {
    $this->expectDeprecation('AssertLegacyTrait::assertLinkByHref() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkByHrefExists() instead. See https://www.drupal.org/node/3129738');
    $this->webAssert
      ->linkByHrefExists('boo', 0)
      ->shouldBeCalled();

    $this->assertLinkByHref('boo', 0);
  }

  /**
   * @covers ::assertNoLinkByHref
   */
  public function testAssertNoLinkByHref() {
    $this->expectDeprecation('AssertLegacyTrait::assertNoLinkByHref() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkByHrefNotExists() instead. See https://www.drupal.org/node/3129738');
    $this->webAssert
      ->linkByHrefNotExists('boo')
      ->shouldBeCalled();

    $this->assertNoLinkByHref('boo');
  }

  /**
   * @covers ::constructFieldXpath
   */
  public function testConstructFieldXpath() {
    $this->expectDeprecation('AssertLegacyTrait::constructFieldXpath() is deprecated in drupal:8.5.0 and is removed from drupal:10.0.0. Use $this->getSession()->getPage()->findField() instead. See https://www.drupal.org/node/3129738');
    $this->webAssert
      ->buildXPathQuery(Argument::any(), Argument::any())
      ->willReturn('qux');

    $this->assertSame('qux', $this->constructFieldXpath('foo', ['bar']));
  }

  /**
   * Returns a mocked behat session object.
   *
   * @return \Behat\Mink\Session
   *   The mocked session.
   */
  protected function getSession() {
    return $this->session->reveal();
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return $this->webAssert->reveal();
  }

}
