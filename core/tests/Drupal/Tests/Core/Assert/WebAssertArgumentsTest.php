<?php

namespace Drupal\Tests\Core\Assert;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\Element;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\WebAssert;
use Prophecy\Argument;

/**
 * Tests that calling WebAssert methods with extra arguments leads to errors.
 *
 * @coversDefaultClass \Drupal\Tests\WebAssert
 * @group Assert
 */
class WebAssertArgumentsTest extends UnitTestCase {

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
   * @var \Drupal\Tests\WebAssert
   */
  protected $webAssert;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->page = $this->prophesize(DocumentElement::class);
    $this->session = $this->prophesize(Session::class);
    $this->session->getPage()->willReturn($this->page->reveal());
    $this->webAssert = new WebAssert($this->getSession());
  }

  /**
   * @covers ::buttonExists
   */
  public function testButtonExists(): void {
    $this->expectError(\AssertionError::class);
    $this->page->findButton(Argument::any())->willReturn('bar');
    $this->webAssert->buttonExists('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::buttonNotExists
   */
  public function testButtonNotExists(): void {
    $this->expectError(\AssertionError::class);
    $this->page->findButton(Argument::any())->willReturn(NULL);
    $this->webAssert->buttonNotExists('qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::selectExists
   */
  public function testSelectExists(): void {
    $this->expectError(\AssertionError::class);
    $this->page->find(Argument::any(), Argument::any())->willReturn('bar');
    $this->webAssert->selectExists('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::optionExists
   */
  public function testOptionExists(): void {
    $this->expectError(\AssertionError::class);
    $select = $this->prophesize(Element::class);
    $select->find(Argument::any(), Argument::any())->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($select->reveal());
    $this->webAssert->optionExists('foo', 'bar', NULL, 'Extra argument');
  }

  /**
   * @covers ::optionNotExists
   */
  public function testOptionNotExists(): void {
    $this->expectError(\AssertionError::class);
    $select = $this->prophesize(Element::class);
    $select->find(Argument::any(), Argument::any())->willReturn(NULL);
    $this->page->find(Argument::any(), Argument::any())->willReturn($select->reveal());
    $this->webAssert->optionNotExists('foo', 'qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::titleEquals
   */
  public function testTitleEquals(): void {
    $this->expectError(\AssertionError::class);
    $title = $this->prophesize(Element::class);
    $title->getText()->willReturn('foo');
    $this->page->find(Argument::any(), Argument::any())->willReturn($title->reveal());
    $this->webAssert->titleEquals('foo', 'Extra argument');
  }

  /**
   * @covers ::assertNoEscaped
   */
  public function testAssertNoEscaped(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->assertNoEscaped('qux', 'Extra argument');
  }

  /**
   * @covers ::assertEscaped
   */
  public function testAssertEscaped(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->assertEscaped('foo', 'Extra argument');
  }

  /**
   * @covers ::responseContains
   */
  public function testResponseContains(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->responseContains('foo', 'Extra argument');
  }

  /**
   * @covers ::responseNotContains
   */
  public function testResponseNotContains(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->responseNotContains('qux', 'Extra argument');
  }

  /**
   * @covers ::fieldDisabled
   */
  public function testFieldDisabled(): void {
    $this->expectError(\AssertionError::class);
    $field = $this->prophesize(NodeElement::class);
    $field->hasAttribute('disabled')->willReturn(TRUE);
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->fieldDisabled('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::fieldEnabled
   */
  public function testFieldEnabled(): void {
    $this->expectError(\AssertionError::class);
    $field = $this->prophesize(NodeElement::class);
    $field->hasAttribute('disabled')->willReturn(FALSE);
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->fieldEnabled('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::hiddenFieldExists
   */
  public function testHiddenFieldExists(): void {
    $this->expectError(\AssertionError::class);
    $this->page->find(Argument::any(), Argument::any())->willReturn('bar');
    $this->webAssert->hiddenFieldExists('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::hiddenFieldNotExists
   */
  public function testHiddenFieldNotExists(): void {
    $this->expectError(\AssertionError::class);
    $this->page->find(Argument::any(), Argument::any())->willReturn(NULL);
    $this->webAssert->hiddenFieldNotExists('qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::hiddenFieldValueEquals
   */
  public function testHiddenFieldValueEquals(): void {
    $this->expectError(\AssertionError::class);
    $field = $this->prophesize(NodeElement::class);
    $field->getValue()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($field->reveal());
    $this->webAssert->hiddenFieldValueEquals('foo', 'bar', NULL, 'Extra argument');
  }

  /**
   * @covers ::hiddenFieldValueNotEquals
   */
  public function testHiddenFieldValueNotEquals(): void {
    $this->expectError(\AssertionError::class);
    $field = $this->prophesize(NodeElement::class);
    $field->getValue()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($field->reveal());
    $this->webAssert->hiddenFieldValueNotEquals('foo', 'qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::pageTextContainsOnce
   */
  public function testPageTextContainsOnce(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextContainsOnce('foo', 'Extra argument');
  }

  /**
   * @covers ::addressEquals
   */
  public function testAddressEquals(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getCurrentUrl()->willReturn('foo');
    $this->webAssert->addressEquals('foo', 'Extra argument');
  }

  /**
   * @covers ::addressNotEquals
   */
  public function testAddressNotEquals(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getCurrentUrl()->willReturn('foo');
    $this->webAssert->addressNotEquals('qux', 'Extra argument');
  }

  /**
   * @covers ::addressMatches
   */
  public function testAddressMatches(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getCurrentUrl()->willReturn('foo');
    $this->webAssert->addressMatches('/foo/', 'Extra argument');
  }

  /**
   * @covers ::cookieEquals
   */
  public function testCookieEquals(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getCookie('foo')->willReturn('bar');
    $this->webAssert->cookieEquals('foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::cookieExists
   */
  public function testCookieExists(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getCookie('foo')->willReturn('bar');
    $this->webAssert->cookieExists('foo', 'Extra argument');
  }

  /**
   * @covers ::statusCodeEquals
   */
  public function testStatusCodeEquals(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getStatusCode()->willReturn(200);
    $this->webAssert->statusCodeEquals(200, 'Extra argument');
  }

  /**
   * @covers ::statusCodeNotEquals
   */
  public function testStatusCodeNotEquals(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getStatusCode()->willReturn(200);
    $this->webAssert->statusCodeNotEquals(403, 'Extra argument');
  }

  /**
   * @covers ::responseHeaderEquals
   */
  public function testResponseHeaderEquals(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderEquals('foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderNotEquals
   */
  public function testResponseHeaderNotEquals(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderNotEquals('foo', 'qux', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderContains
   */
  public function testResponseHeaderContains(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderContains('foo', 'ar', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderNotContains
   */
  public function testResponseHeaderNotContains(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderNotContains('foo', 'qu', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderMatches
   */
  public function testResponseHeaderMatches(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderMatches('foo', '/bar/', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderNotMatches
   */
  public function testResponseHeaderNotMatches(): void {
    $this->expectError(\AssertionError::class);
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderNotMatches('foo', '/qux/', 'Extra argument');
  }

  /**
   * @covers ::pageTextContains
   */
  public function testPageTextContains(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextContains('foo', 'Extra argument');
  }

  /**
   * @covers ::pageTextNotContains
   */
  public function testPageTextNotContains(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextNotContains('qux', 'Extra argument');
  }

  /**
   * @covers ::pageTextMatches
   */
  public function testPageTextMatches(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextMatches('/foo/', 'Extra argument');
  }

  /**
   * @covers ::pageTextNotMatches
   */
  public function testPageTextNotMatches(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextNotMatches('/qux/', 'Extra argument');
  }

  /**
   * @covers ::responseMatches
   */
  public function testResponseMatches(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->responseMatches('/foo/', 'Extra argument');
  }

  /**
   * @covers ::responseNotMatches
   */
  public function testResponseNotMatches(): void {
    $this->expectError(\AssertionError::class);
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->responseNotMatches('/qux/', 'Extra argument');
  }

  /**
   * @covers ::elementsCount
   */
  public function testElementsCount(): void {
    $this->expectError(\AssertionError::class);
    $this->page->findAll(Argument::any(), Argument::any())->willReturn(['bar']);
    $this->webAssert->elementsCount('xpath', '//foo', 1, NULL, 'Extra argument');
  }

  /**
   * @covers ::elementExists
   */
  public function testElementExists(): void {
    $this->expectError(\AssertionError::class);
    $this->page->find(Argument::any(), Argument::any())->willReturn('bar');
    $this->webAssert->elementExists('xpath', '//foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::elementNotExists
   */
  public function testElementNotExists(): void {
    $this->expectError(\AssertionError::class);
    $this->page->find(Argument::any(), Argument::any())->willReturn(NULL);
    $this->webAssert->elementNotExists('xpath', '//foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::elementTextContains
   */
  public function testElementTextContains(): void {
    $this->expectError(\AssertionError::class);
    $element = $this->prophesize(NodeElement::class);
    $element->getText()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementTextContains('xpath', '//foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::elementTextNotContains
   */
  public function testElementTextNotContains(): void {
    $this->expectError(\AssertionError::class);
    $element = $this->prophesize(NodeElement::class);
    $element->getText()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementTextNotContains('xpath', '//foo', 'qux', 'Extra argument');
  }

  /**
   * @covers ::elementContains
   */
  public function testElementContains(): void {
    $this->expectError(\AssertionError::class);
    $element = $this->prophesize(NodeElement::class);
    $element->getHtml()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementContains('xpath', '//foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::elementNotContains
   */
  public function testElementNotContains(): void {
    $this->expectError(\AssertionError::class);
    $element = $this->prophesize(NodeElement::class);
    $element->getHtml()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementNotContains('xpath', '//foo', 'qux', 'Extra argument');
  }

  /**
   * @covers ::elementAttributeExists
   */
  public function testElementAttributeExists(): void {
    $this->expectError(\AssertionError::class);
    $element = $this->prophesize(NodeElement::class);
    $element->hasAttribute('bar')->willReturn(TRUE);
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementAttributeExists('xpath', '//foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::elementAttributeContains
   */
  public function testElementAttributeContains(): void {
    $this->expectError(\AssertionError::class);
    $element = $this->prophesize(NodeElement::class);
    $element->hasAttribute('bar')->willReturn(TRUE);
    $element->getAttribute('bar')->willReturn('baz');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementAttributeContains('xpath', '//foo', 'bar', 'baz', 'Extra argument');
  }

  /**
   * @covers ::elementAttributeNotContains
   */
  public function testElementAttributeNotContains(): void {
    $this->expectError(\AssertionError::class);
    $element = $this->prophesize(NodeElement::class);
    $element->hasAttribute('bar')->willReturn(TRUE);
    $element->getAttribute('bar')->willReturn('baz');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementAttributeNotContains('xpath', '//foo', 'bar', 'qux', 'Extra argument');
  }

  /**
   * @covers ::fieldExists
   */
  public function testFieldExists(): void {
    $this->expectError(\AssertionError::class);
    $this->page->findField(Argument::any())->willReturn('bar');
    $this->webAssert->fieldExists('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::fieldNotExists
   */
  public function testFieldNotExists(): void {
    $this->expectError(\AssertionError::class);
    $this->page->findField(Argument::any())->willReturn();
    $this->webAssert->fieldNotExists('qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::fieldValueEquals
   */
  public function testFieldValueEquals(): void {
    $this->expectError(\AssertionError::class);
    $field = $this->prophesize(NodeElement::class);
    $field->getValue()->willReturn('bar');
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->fieldValueEquals('foo', 'bar', NULL, 'Extra argument');
  }

  /**
   * @covers ::fieldValueNotEquals
   */
  public function testFieldValueNotEquals(): void {
    $this->expectError(\AssertionError::class);
    $field = $this->prophesize(NodeElement::class);
    $field->getValue()->willReturn('bar');
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->fieldValueNotEquals('foo', 'qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::checkboxChecked
   */
  public function testCheckboxChecked(): void {
    $this->expectError(\AssertionError::class);
    $field = $this->prophesize(NodeElement::class);
    $field->isChecked()->willReturn(TRUE);
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->checkboxChecked('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::checkboxNotChecked
   */
  public function testCheckboxNotChecked(): void {
    $this->expectError(\AssertionError::class);
    $field = $this->prophesize(NodeElement::class);
    $field->isChecked()->willReturn(FALSE);
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->checkboxNotChecked('qux', NULL, 'Extra argument');
  }

  /**
   * Returns a mocked behat session object.
   *
   * @return \Behat\Mink\Session
   *   The mocked session.
   */
  protected function getSession(): Session {
    return $this->session->reveal();
  }

}
