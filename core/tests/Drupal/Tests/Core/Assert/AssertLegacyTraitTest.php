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
   * @covers ::assertUniqueText
   * @expectedDeprecation AssertLegacyTrait::assertUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->pageTextContainsOnce() or $this->getSession()->pageTextMatchesCount() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertUniqueText() {
    $this->page->getText()->willReturn('foo bar bar');
    $this->assertUniqueText('foo');
  }

  /**
   * @covers ::assertUniqueText
   * @expectedDeprecation AssertLegacyTrait::assertUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->pageTextContainsOnce() or $this->getSession()->pageTextMatchesCount() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertUniqueTextFail() {
    $this->page->getText()->willReturn('foo bar bar');
    $this->expectException(ExpectationFailedException::class);
    $this->assertUniqueText('bar');
  }

  /**
   * @covers ::assertUniqueText
   * @expectedDeprecation AssertLegacyTrait::assertUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->pageTextContainsOnce() or $this->getSession()->pageTextMatchesCount() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertUniqueTextUnknown() {
    $this->page->getText()->willReturn('foo bar bar');
    $this->expectException(ExpectationFailedException::class);
    $this->assertUniqueText('alice');
  }

  /**
   * @covers ::assertUniqueText
   * @expectedDeprecation AssertLegacyTrait::assertUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->pageTextContainsOnce() or $this->getSession()->pageTextMatchesCount() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertUniqueTextMarkup() {
    $this->page->getText()->willReturn('foo bar bar');
    $markupObject = $this->prophesize(MarkupInterface::class);
    $markupObject->__toString()->willReturn('foo');
    $this->assertUniqueText($markupObject->reveal());
  }

  /**
   * @covers ::assertNoUniqueText
   * @expectedDeprecation AssertLegacyTrait::assertNoUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Instead, use $this->getSession()->pageTextMatchesCount() if you know the cardinality in advance, or $this->getSession()->getPage()->getText() and substr_count(). See https://www.drupal.org/node/3129738
   */
  public function testAssertNoUniqueText() {
    $this->page->getText()->willReturn('foo bar bar');
    $this->assertNoUniqueText('bar');
  }

  /**
   * @covers ::assertNoUniqueText
   * @expectedDeprecation AssertLegacyTrait::assertNoUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Instead, use $this->getSession()->pageTextMatchesCount() if you know the cardinality in advance, or $this->getSession()->getPage()->getText() and substr_count(). See https://www.drupal.org/node/3129738
   */
  public function testAssertNoUniqueTextFail() {
    $this->page->getText()->willReturn('foo bar bar');
    $this->expectException(ExpectationFailedException::class);
    $this->assertNoUniqueText('foo');
  }

  /**
   * @covers ::assertNoUniqueText
   * @expectedDeprecation AssertLegacyTrait::assertNoUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Instead, use $this->getSession()->pageTextMatchesCount() if you know the cardinality in advance, or $this->getSession()->getPage()->getText() and substr_count(). See https://www.drupal.org/node/3129738
   */
  public function testAssertNoUniqueTextUnknown() {
    $this->page->getText()->willReturn('foo bar bar');
    $this->expectException(ExpectationFailedException::class);
    $this->assertNoUniqueText('alice');
  }

  /**
   * @covers ::assertNoUniqueText
   * @expectedDeprecation AssertLegacyTrait::assertNoUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Instead, use $this->getSession()->pageTextMatchesCount() if you know the cardinality in advance, or $this->getSession()->getPage()->getText() and substr_count(). See https://www.drupal.org/node/3129738
   */
  public function testAssertNoUniqueTextMarkup() {
    $this->page->getText()->willReturn('foo bar bar');
    $markupObject = $this->prophesize(MarkupInterface::class);
    $markupObject->__toString()->willReturn('bar');
    $this->assertNoUniqueText($markupObject->reveal());
  }

  /**
   * @covers ::assertOptionSelected
   * @expectedDeprecation AssertLegacyTrait::assertOptionSelected() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionExists() instead and check the "selected" attribute. See https://www.drupal.org/node/3129738
   */
  public function testAssertOptionSelected() {
    $option_field = $this->prophesize(NodeElement::class);
    $option_field->hasAttribute('selected')->willReturn(TRUE);

    $this->webAssert
      ->optionExists('myselect', 'two')
      ->willReturn($option_field->reveal());

    $this->assertOptionSelected('myselect', 'two');
  }

  /**
   * @covers ::assertOptionSelected
   * @expectedDeprecation AssertLegacyTrait::assertOptionSelected() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionExists() instead and check the "selected" attribute. See https://www.drupal.org/node/3129738
   */
  public function testAssertOptionSelectedFail() {
    $option_field = $this->prophesize(NodeElement::class);
    $option_field->hasAttribute('selected')->willReturn(FALSE);

    $this->webAssert
      ->optionExists('myselect', 'two')
      ->willReturn($option_field->reveal());

    $this->expectException(ExpectationFailedException::class);
    $this->assertOptionSelected('myselect', 'two');
  }

  /**
   * @covers ::assertNoPattern
   * @expectedDeprecation AssertLegacyTrait::assertNoPattern() is deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseNotMatches() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertNoPattern() {
    $this->webAssert
      ->responseNotMatches('/.*foo$/')
      ->shouldBeCalled();

    $this->assertNoPattern('/.*foo$/');
  }

  /**
   * @covers ::assertCacheTag
   * @expectedDeprecation AssertLegacyTrait::assertCacheTag() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderContains() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertCacheTag() {
    $this->webAssert
      ->responseHeaderContains('X-Drupal-Cache-Tags', 'some-cache-tag')
      ->shouldBeCalled();

    $this->assertCacheTag('some-cache-tag');
  }

  /**
   * @covers ::assertNoCacheTag
   * @expectedDeprecation AssertLegacyTrait::assertNoCacheTag() is deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderNotContains() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertNoCacheTag() {
    $this->webAssert
      ->responseHeaderNotContains('X-Drupal-Cache-Tags', 'some-cache-tag')
      ->shouldBeCalled();

    $this->assertNoCacheTag('some-cache-tag');
  }

  /**
   * @covers ::assertUrl
   * @expectedDeprecation Calling AssertLegacyTrait::assertUrl() with more than one argument is deprecated in drupal:8.2.0 and the method is removed from drupal:10.0.0. Use $this->assertSession()->addressEquals() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertUrl() {
    $this->webAssert
      ->addressEquals('bingo')
      ->shouldBeCalled();

    $this->assertUrl('bingo', 'Redundant message.');
  }

  /**
   * @covers ::assertElementPresent
   * @expectedDeprecation AssertLegacyTrait::assertElementPresent() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->elementExists() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertElementPresent() {
    $this->webAssert
      ->elementExists('css', '.pager')
      ->shouldBeCalled();

    $this->assertElementPresent('.pager');
  }

  /**
   * @covers ::assertElementNotPresent
   * @expectedDeprecation AssertLegacyTrait::assertElementNotPresent() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->elementNotExists() instead. See https://www.drupal.org/node/3129738
   */
  public function testAssertElementNotPresent() {
    $this->webAssert
      ->elementNotExists('css', '.pager')
      ->shouldBeCalled();

    $this->assertElementNotPresent('.pager');
  }

  /**
   * @covers ::pass
   * @expectedDeprecation AssertLegacyTrait::pass() is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. PHPUnit interrupts a test as soon as a test assertion fails, so there is usually no need to call this method. If a test's logic relies on this method, refactor the test. See https://www.drupal.org/node/3129738
   */
  public function testPass() {
    $this->pass('Passed.');
  }

  /**
   * @covers ::constructFieldXpath
   * @expectedDeprecation AssertLegacyTrait::constructFieldXpath() is deprecated in drupal:8.5.0 and is removed from drupal:10.0.0. Use $this->getSession()->getPage()->findField() instead. See https://www.drupal.org/node/3129738
   */
  public function testConstructFieldXpath() {
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
