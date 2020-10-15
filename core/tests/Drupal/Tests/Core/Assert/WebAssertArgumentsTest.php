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
 * NOTE: Do not drop this test class in Drupal 10, convert the tests to check
 * that \InvalidArgumentException are thrown instead of deprecations.
 *
 * @coversDefaultClass \Drupal\Tests\WebAssert
 * @group Assert
 * @group legacy
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
  public function setUp(): void {
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
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::buttonExists with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->findButton(Argument::any())->willReturn('bar');
    $this->webAssert->buttonExists('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::buttonNotExists
   */
  public function testButtonNotExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::buttonNotExists with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->findButton(Argument::any())->willReturn(NULL);
    $this->webAssert->buttonNotExists('qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::selectExists
   */
  public function testSelectExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::selectExists with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->find(Argument::any(), Argument::any())->willReturn('bar');
    $this->webAssert->selectExists('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::optionExists
   */
  public function testOptionExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::optionExists with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $select = $this->prophesize(Element::class);
    $select->find(Argument::any(), Argument::any())->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($select->reveal());
    $this->webAssert->optionExists('foo', 'bar', NULL, 'Extra argument');
  }

  /**
   * @covers ::optionNotExists
   */
  public function testOptionNotExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::optionNotExists with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $select = $this->prophesize(Element::class);
    $select->find(Argument::any(), Argument::any())->willReturn(NULL);
    $this->page->find(Argument::any(), Argument::any())->willReturn($select->reveal());
    $this->webAssert->optionNotExists('foo', 'qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::titleEquals
   */
  public function testTitleEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::titleEquals with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $title = $this->prophesize(Element::class);
    $title->getText()->willReturn('foo');
    $this->page->find(Argument::any(), Argument::any())->willReturn($title->reveal());
    $this->webAssert->titleEquals('foo', 'Extra argument');
  }

  /**
   * @covers ::assertNoEscaped
   */
  public function testAssertNoEscaped(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::assertNoEscaped with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->assertNoEscaped('qux', 'Extra argument');
  }

  /**
   * @covers ::assertEscaped
   */
  public function testAssertEscaped(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::assertEscaped with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->assertEscaped('foo', 'Extra argument');
  }

  /**
   * @covers ::responseContains
   */
  public function testResponseContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseContains with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->responseContains('foo', 'Extra argument');
  }

  /**
   * @covers ::responseNotContains
   */
  public function testResponseNotContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseNotContains with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->responseNotContains('qux', 'Extra argument');
  }

  /**
   * @covers ::fieldDisabled
   */
  public function testFieldDisabled(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::fieldDisabled with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $field = $this->prophesize(NodeElement::class);
    $field->hasAttribute('disabled')->willReturn(TRUE);
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->fieldDisabled('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::fieldEnabled
   */
  public function testFieldEnabled(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::fieldEnabled with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $field = $this->prophesize(NodeElement::class);
    $field->hasAttribute('disabled')->willReturn(FALSE);
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->fieldEnabled('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::hiddenFieldExists
   */
  public function testHiddenFieldExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::hiddenFieldExists with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->find(Argument::any(), Argument::any())->willReturn('bar');
    $this->webAssert->hiddenFieldExists('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::hiddenFieldNotExists
   */
  public function testHiddenFieldNotExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::hiddenFieldNotExists with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->find(Argument::any(), Argument::any())->willReturn(NULL);
    $this->webAssert->hiddenFieldNotExists('qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::hiddenFieldValueEquals
   */
  public function testHiddenFieldValueEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::hiddenFieldValueEquals with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $field = $this->prophesize(NodeElement::class);
    $field->getValue()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($field->reveal());
    $this->webAssert->hiddenFieldValueEquals('foo', 'bar', NULL, 'Extra argument');
  }

  /**
   * @covers ::hiddenFieldValueNotEquals
   */
  public function testHiddenFieldValueNotEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::hiddenFieldValueNotEquals with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $field = $this->prophesize(NodeElement::class);
    $field->getValue()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($field->reveal());
    $this->webAssert->hiddenFieldValueNotEquals('foo', 'qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::pageTextContainsOnce
   */
  public function testPageTextContainsOnce(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::pageTextContainsOnce with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextContainsOnce('foo', 'Extra argument');
  }

  /**
   * @covers ::addressEquals
   */
  public function testAddressEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::addressEquals with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getCurrentUrl()->willReturn('foo');
    $this->webAssert->addressEquals('foo', 'Extra argument');
  }

  /**
   * @covers ::addressNotEquals
   */
  public function testAddressNotEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::addressNotEquals with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getCurrentUrl()->willReturn('foo');
    $this->webAssert->addressNotEquals('qux', 'Extra argument');
  }

  /**
   * @covers ::addressMatches
   */
  public function testAddressMatches(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::addressMatches with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getCurrentUrl()->willReturn('foo');
    $this->webAssert->addressMatches('/foo/', 'Extra argument');
  }

  /**
   * @covers ::cookieEquals
   */
  public function testCookieEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::cookieEquals with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getCookie('foo')->willReturn('bar');
    $this->webAssert->cookieEquals('foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::cookieExists
   */
  public function testCookieExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::cookieExists with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getCookie('foo')->willReturn('bar');
    $this->webAssert->cookieExists('foo', 'Extra argument');
  }

  /**
   * @covers ::statusCodeEquals
   */
  public function testStatusCodeEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::statusCodeEquals with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getStatusCode()->willReturn(200);
    $this->webAssert->statusCodeEquals(200, 'Extra argument');
  }

  /**
   * @covers ::statusCodeNotEquals
   */
  public function testStatusCodeNotEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::statusCodeNotEquals with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getStatusCode()->willReturn(200);
    $this->webAssert->statusCodeNotEquals(403, 'Extra argument');
  }

  /**
   * @covers ::responseHeaderEquals
   */
  public function testResponseHeaderEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseHeaderEquals with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderEquals('foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderNotEquals
   */
  public function testResponseHeaderNotEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseHeaderNotEquals with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderNotEquals('foo', 'qux', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderContains
   */
  public function testResponseHeaderContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseHeaderContains with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderContains('foo', 'ar', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderNotContains
   */
  public function testResponseHeaderNotContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseHeaderNotContains with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderNotContains('foo', 'qu', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderMatches
   */
  public function testResponseHeaderMatches(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseHeaderMatches with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderMatches('foo', '/bar/', 'Extra argument');
  }

  /**
   * @covers ::responseHeaderNotMatches
   */
  public function testResponseHeaderNotMatches(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseHeaderNotMatches with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->session->getResponseHeader('foo')->willReturn('bar');
    $this->webAssert->responseHeaderNotMatches('foo', '/qux/', 'Extra argument');
  }

  /**
   * @covers ::pageTextContains
   */
  public function testPageTextContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::pageTextContains with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextContains('foo', 'Extra argument');
  }

  /**
   * @covers ::pageTextNotContains
   */
  public function testPageTextNotContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::pageTextNotContains with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextNotContains('qux', 'Extra argument');
  }

  /**
   * @covers ::pageTextMatches
   */
  public function testPageTextMatches(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::pageTextMatches with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextMatches('/foo/', 'Extra argument');
  }

  /**
   * @covers ::pageTextNotMatches
   */
  public function testPageTextNotMatches(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::pageTextNotMatches with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getText()->willReturn('foo bar bar');
    $this->webAssert->pageTextNotMatches('/qux/', 'Extra argument');
  }

  /**
   * @covers ::responseMatches
   */
  public function testResponseMatches(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseMatches with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->responseMatches('/foo/', 'Extra argument');
  }

  /**
   * @covers ::responseNotMatches
   */
  public function testResponseNotMatches(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::responseNotMatches with more than one argument is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->getContent()->willReturn('foo bar bar');
    $this->webAssert->responseNotMatches('/qux/', 'Extra argument');
  }

  /**
   * @covers ::elementsCount
   */
  public function testElementsCount(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementsCount with more than four arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->findAll(Argument::any(), Argument::any())->willReturn(['bar']);
    $this->webAssert->elementsCount('xpath', '//foo', 1, NULL, 'Extra argument');
  }

  /**
   * @covers ::elementExists
   */
  public function testElementExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementExists with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->find(Argument::any(), Argument::any())->willReturn('bar');
    $this->webAssert->elementExists('xpath', '//foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::elementNotExists
   */
  public function testElementNotExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementNotExists with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->find(Argument::any(), Argument::any())->willReturn(NULL);
    $this->webAssert->elementNotExists('xpath', '//foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::elementTextContains
   */
  public function testElementTextContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementTextContains with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $element = $this->prophesize(NodeElement::class);
    $element->getText()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementTextContains('xpath', '//foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::elementTextNotContains
   */
  public function testElementTextNotContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementTextNotContains with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $element = $this->prophesize(NodeElement::class);
    $element->getText()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementTextNotContains('xpath', '//foo', 'qux', 'Extra argument');
  }

  /**
   * @covers ::elementContains
   */
  public function testElementContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementContains with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $element = $this->prophesize(NodeElement::class);
    $element->getHtml()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementContains('xpath', '//foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::elementNotContains
   */
  public function testElementNotContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementNotContains with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $element = $this->prophesize(NodeElement::class);
    $element->getHtml()->willReturn('bar');
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementNotContains('xpath', '//foo', 'qux', 'Extra argument');
  }

  /**
   * @covers ::elementAttributeExists
   */
  public function testElementAttributeExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementAttributeExists with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $element = $this->prophesize(NodeElement::class);
    $element->hasAttribute('bar')->willReturn(TRUE);
    $this->page->find(Argument::any(), Argument::any())->willReturn($element->reveal());
    $this->webAssert->elementAttributeExists('xpath', '//foo', 'bar', 'Extra argument');
  }

  /**
   * @covers ::elementAttributeContains
   */
  public function testElementAttributeContains(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementAttributeContains with more than four arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
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
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::elementAttributeNotContains with more than four arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
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
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::fieldExists with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->findField(Argument::any())->willReturn('bar');
    $this->webAssert->fieldExists('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::fieldNotExists
   */
  public function testFieldNotExists(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::fieldNotExists with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $this->page->findField(Argument::any())->willReturn();
    $this->webAssert->fieldNotExists('qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::fieldValueEquals
   */
  public function testFieldValueEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::fieldValueEquals with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $field = $this->prophesize(NodeElement::class);
    $field->getValue()->willReturn('bar');
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->fieldValueEquals('foo', 'bar', NULL, 'Extra argument');
  }

  /**
   * @covers ::fieldValueNotEquals
   */
  public function testFieldValueNotEquals(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::fieldValueNotEquals with more than three arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $field = $this->prophesize(NodeElement::class);
    $field->getValue()->willReturn('bar');
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->fieldValueNotEquals('foo', 'qux', NULL, 'Extra argument');
  }

  /**
   * @covers ::checkboxChecked
   */
  public function testCheckboxChecked(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::checkboxChecked with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
    $field = $this->prophesize(NodeElement::class);
    $field->isChecked()->willReturn(TRUE);
    $this->page->findField(Argument::any())->willReturn($field->reveal());
    $this->webAssert->checkboxChecked('foo', NULL, 'Extra argument');
  }

  /**
   * @covers ::checkboxNotChecked
   */
  public function testCheckboxNotChecked(): void {
    $this->expectDeprecation('Calling Drupal\Tests\WebAssert::checkboxNotChecked with more than two arguments is deprecated in drupal:9.1.0 and will throw an \InvalidArgumentException in drupal:10.0.0. See https://www.drupal.org/node/3162537');
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
