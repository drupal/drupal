<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Htmx;

use Drupal\Core\GeneratedUrl;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test all attribute-related Htmx public methods.
 */
#[CoversClass(Htmx::class)]
#[Group('Htmx')]
class HtmxAttributesTest extends UnitTestCase {

  /**
   * Class under test.
   */
  protected Htmx $htmx;

  /**
   * Mocked Url object.
   */
  protected Url $url;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->htmx = new Htmx();
    $generated = new GeneratedUrl();
    $generated->setGeneratedUrl('https://www.example.test/common-test/destination');
    $this->url = $this->getMockBuilder(Url::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['toString'])
      ->getMock();
    $this->url->expects($this->any())
      ->method('toString')
      ->willReturn($generated);
  }

  /**
   * Applies the Htmx attributes to a render array.
   */
  protected function apply(): array {
    $render = [];
    $this->htmx->applyTo($render);
    return $render;
  }

  /**
   * Test on method.
   */
  #[DataProvider('hxOnDataProvider')]
  public function testHxOn(string $event, string $expected): void {
    $this->htmx->on($event, 'someAction');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes'][$expected]));
    $this->assertEquals('someAction', $render['#attributes'][$expected]);
  }

  /**
   * Provides data to ::testHxOn.
   *
   * @return array<int, string[]>
   *   Array of event, expected.
   */
  public static function hxOnDataProvider(): array {
    return [
      ['lowercase', 'data-hx-on-lowercase'],
      ['already-kebab-case', 'data-hx-on-already-kebab-case'],
      ['snake_case', 'data-hx-on-snake-case'],
      ['camelCaseEvent', 'data-hx-on-camel-case-event'],
      ['htmx:beforeRequest', 'data-hx-on-htmx-before-request'],
      ['::beforeRequest', 'data-hx-on--before-request'],
    ];
  }

  /**
   * Test pushUrl method.
   */
  #[DataProvider('booleanStringDataProvider')]
  public function testHxPushUrl(bool|Url $value, string $attributeValue): void {
    $this->htmx->pushUrl($value);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-push-url']));
    $this->assertEquals($attributeValue, $render['#attributes']['data-hx-push-url']);
  }

  /**
   * Test replaceUrl method.
   */
  #[DataProvider('booleanStringDataProvider')]
  public function testHxReplaceUrl(bool|Url $value, string $attributeValue): void {
    $this->htmx->replaceUrl($value);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-replace-url']));
    $this->assertEquals($attributeValue, $render['#attributes']['data-hx-replace-url']);
  }

  /**
   * Provides data to ::testHxPushUrl and :testHxReplaceUrl.
   *
   * @return array{bool, string}[]
   *   Array of <bool, string> expected.
   */
  public static function booleanStringDataProvider(): array {
    return [
      [TRUE, 'true'],
      [FALSE, 'false'],
    ];
  }

  /**
   * Test pushUrl method with a Url object.
   */
  public function testHxPushUrlAbsolute(): void {
    $this->htmx->pushUrl($this->url);
    $render = $this->apply();
    $this->assertStringEndsWith('/common-test/destination', $render['#attributes']['data-hx-push-url']);
  }

  /**
   * Test replaceUrl method with a Url object.
   */
  public function testHxReplaceUrlAbsolute(): void {
    $this->htmx->replaceUrl($this->url);
    $render = $this->apply();
    $this->assertStringEndsWith('/common-test/destination', $render['#attributes']['data-hx-replace-url']);
  }

  /**
   * Test swapOob method.
   */
  #[DataProvider('hxSwapOobDataProvider')]
  public function testHxSwapOob(true|string $value, string $expected): void {
    $this->htmx->swapOob($value);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-swap-oob']));
    $this->assertEquals($expected, $render['#attributes']['data-hx-swap-oob']);
  }

  /**
   * Provides data to ::testHxSwapOob.
   *
   * @return array{true|string, string}[]
   *   Array of true|string, expected.
   */
  public static function hxSwapOobDataProvider(): array {
    return [
      [TRUE, 'true'],
      ['body:beforeend', 'body:beforeend'],
    ];
  }

  /**
   * Test vals method.
   */
  public function testHxVals(): void {
    $values = ['myValue' => 'My Value'];
    $this->htmx->vals($values);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-vals']));
    $this->assertEquals('{"myValue":"My Value"}', $render['#attributes']['data-hx-vals']);
  }

  /**
   * Test boost method.
   */
  #[DataProvider('booleanStringDataProvider')]
  public function testHxBoost(bool $value, string $expected): void {
    $this->htmx->boost($value);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-boost']));
    $this->assertEquals($expected, $render['#attributes']['data-hx-boost']);
  }

  /**
   * Test headers method.
   */
  public function testHxHeaders(): void {
    $values = ['myValue' => 'My Value'];
    $this->htmx->headers($values);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-headers']));
    $this->assertEquals('{"myValue":"My Value"}', $render['#attributes']['data-hx-headers']);
  }

  /**
   * Test request method.
   */
  public function testHxRequest(): void {
    $values = ['timeout' => 100, 'credentials' => FALSE];
    $this->htmx->request($values);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-request']));
    $this->assertEquals('{"timeout":100,"credentials":false}', $render['#attributes']['data-hx-request']);
  }

  /**
   * Test validate method.
   */
  #[DataProvider('hxValidateDataProvider')]
  public function testHxValidate(?bool $value, string $expected): void {
    if (is_null($value)) {
      $this->htmx->validate();
    }
    else {
      $this->htmx->validate($value);
    }
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-validate']));
    $this->assertEquals($expected, $render['#attributes']['data-hx-validate']);
  }

  /**
   * Provides data to ::testHxValidate.
   *
   * @return array{?bool, string}[]
   *   Array of null|bool, string, expected.
   */
  public static function hxValidateDataProvider(): array {
    return [
      [TRUE, 'true'],
      [FALSE, 'false'],
      [NULL, 'true'],
    ];
  }

  /**
   * Test swap method.
   */
  public function testSwap(): void {
    // Simple case.
    $this->htmx->swap('afterbegin');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-swap']));
    $this->assertEquals('afterbegin ignoreTitle:true', $render['#attributes']['data-hx-swap']);
    // Don't ignore the title.
    $this->htmx->swap('afterbegin', '', FALSE);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-swap']));
    $this->assertEquals('afterbegin', $render['#attributes']['data-hx-swap']);
    // Use a modifier.
    $this->htmx->swap('beforeend', 'scroll:bottom');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-swap']));
    $this->assertEquals('beforeend scroll:bottom ignoreTitle:true', $render['#attributes']['data-hx-swap']);
  }

  /**
   * Test remaining methods.
   */
  #[DataProvider('hxSimpleStringAttributesDataProvider')]
  public function testHxSimpleAttributes(string $method, null|string|array $value, string $attribute, string|bool $expected): void {
    if (is_null($value)) {
      $this->htmx->$method();
    }
    else {
      $this->htmx->$method($value);
    }
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes'][$attribute]));
    $this->assertEquals($expected, $render['#attributes'][$attribute]);
  }

  /**
   * Provides data to ::testHxSimpleStringAttributes.
   *
   * @return array{string, ?string, string, string|bool}[]
   *   Array of method, value, attribute, expected.
   */
  public static function hxSimpleStringAttributesDataProvider(): array {
    return [
      ['select', '#info-details', 'data-hx-select', '#info-details'],
      // phpcs:ignore Drupal.Arrays.Array.LongLineDeclaration
      ['select', 'info[data-drupal-selector="edit-select"]', 'data-hx-select', 'info[data-drupal-selector="edit-select"]'],
      ['selectOob', '#info-details', 'data-hx-select-oob', '#info-details'],
      ['selectOob', ['#info-details:afterbegin', '#alert'], 'data-hx-select-oob', '#info-details:afterbegin,#alert'],
      ['target', 'descriptor', 'data-hx-target', 'descriptor'],
      ['trigger', 'event', 'data-hx-trigger', 'event'],
      ['trigger', ['load', 'click delay:1s'], 'data-hx-trigger', 'load,click delay:1s'],
      ['confirm', 'A confirmation message', 'data-hx-confirm', 'A confirmation message'],
      ['disable', NULL, 'data-hx-disable', TRUE],
      ['disabledElt', 'descriptor', 'data-hx-disabled-elt', 'descriptor'],
      ['disinherit', 'descriptor', 'data-hx-disinherit', 'descriptor'],
      ['encoding', NULL, 'data-hx-encoding', 'multipart/form-data'],
      ['encoding', 'application/x-www-form-urlencoded', 'data-hx-encoding', 'application/x-www-form-urlencoded'],
      ['ext', 'name, name', 'data-hx-ext', 'name, name'],
      ['historyElt', NULL, 'data-hx-history-elt', TRUE],
      ['include', 'descriptor', 'data-hx-include', 'descriptor'],
      ['indicator', 'descriptor', 'data-hx-indicator', 'descriptor'],
      ['inherit', 'descriptor', 'data-hx-inherit', 'descriptor'],
      ['params', '*', 'data-hx-params', '*'],
      ['params', ['not param1', 'param2', 'param3'], 'data-hx-params', 'not param1,param2,param3'],
      ['params', ['param1', 'param2', 'param3'], 'data-hx-params', 'param1,param2,param3'],
      ['preserve', NULL, 'data-hx-preserve', TRUE],
      ['history', NULL, 'data-hx-history', 'false'],
      ['prompt', 'A prompt message', 'data-hx-prompt', 'A prompt message'],
      ['sync', 'closest form:abort', 'data-hx-sync', 'closest form:abort'],
    ];
  }

}
