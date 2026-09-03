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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Stub;

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
  protected Url&Stub $url;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->htmx = new Htmx();
    $generated = new GeneratedUrl();
    $generated->setGeneratedUrl('https://www.example.test/common-test/destination');
    $this->url = $this->createStub(Url::class);
    $this->url
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
  public function testHxOn(string|array $event, string $action, string $name, string $expected): void {
    $this->htmx->on($event, $action);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes'][$name]));
    $this->assertEquals($expected, $render['#attributes'][$name]);
  }

  /**
   * Provides data to self::testHxOn.
   *
   * @return array<int, string[]>
   *   Array of event, expected.
   */
  public static function hxOnDataProvider(): array {
    return [
      [
        'simple',
        'someAction(this)',
        'data-hx-on:simple',
        'someAction(this)',
      ],
      [
        '::before:request',
        'someAction(event)',
        'data-hx-on::before:request',
        'someAction(event)',
      ],
      [
        ['singleEvent' => 'someAction()'],
        '',
        'data-hx-on',
        'singleEvent -> someAction()',
      ],
      [
        [
          'load' => 'this.showModal()',
          'click from:self, closeDialog from:body' => 'this.close()',
        ],
        '',
        'data-hx-on',
        'load -> this.showModal(); click from:self, closeDialog from:body -> this.close()',
      ],
      [
        [
          'close' => "this.remove(); log('dialog removed')",
        ],
        '',
        'data-hx-on',
        "close -> this.remove(); log('dialog removed')",
      ],
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
   * Provides data to self::testHxPushUrl and self::testHxReplaceUrl.
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
   * Provides data to self::testHxSwapOob.
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
   * Test config method.
   */
  public function testHxConfig(): void {
    $values = ['timeout' => 100, 'credentials' => 'include'];
    $this->htmx->config($values);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-config']));
    $this->assertEquals('{"timeout":100,"credentials":"include"}', $render['#attributes']['data-hx-config']);
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
   * Provides data to self::testHxValidate.
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
   * Test disable method.
   */
  #[TestWith([FALSE])]
  #[TestWith([TRUE])]
  public function testHxDisable(bool $useMerge): void {
    $selector = 'div.some-class';
    $expectedName = $useMerge ? 'data-hx-disable:merge' : 'data-hx-disable';
    $this->htmx->disable($selector, $useMerge);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes'][$expectedName]));
    $this->assertEquals($selector, $render['#attributes'][$expectedName]);
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
   * Test status method.
   */
  public function testStatus(): void {
    // Test with a placeholder status code.
    $this->htmx->status('status-code', 'swap:innerHTML target:#errors');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-status:status-code']));
    $this->assertEquals('swap:innerHTML target:#errors', $render['#attributes']['data-hx-status:status-code']);
  }

  public function testInvalidMethod(): void {
    // Test an invalid string.
    $this->expectException(\ValueError::class);
    $this->htmx->method('invalid');
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
   * Provides data to self::testHxSimpleStringAttributes.
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
      ['ignore', NULL, 'data-hx-ignore', TRUE],
      ['historyElt', NULL, 'data-hx-history-elt', TRUE],
      ['encoding', NULL, 'data-hx-encoding', 'multipart/form-data'],
      ['encoding', 'application/x-www-form-urlencoded', 'data-hx-encoding', 'application/x-www-form-urlencoded'],
      ['include', 'descriptor', 'data-hx-include', 'descriptor'],
      ['indicator', 'descriptor', 'data-hx-indicator', 'descriptor'],
      ['sync', 'closest form:abort', 'data-hx-sync', 'closest form:abort'],
      ['method', 'get', 'data-hx-method', 'get'],
      ['method', 'post', 'data-hx-method', 'post'],
      ['method', 'PUT', 'data-hx-method', 'PUT'],
      ['method', 'patch', 'data-hx-method', 'patch'],
      ['method', 'delete', 'data-hx-method', 'delete'],
    ];
  }

  /**
   * Tests modifiers.
   */
  public function testModifiers(): void {
    // No modifier is the default.  Including for completeness.
    $this->htmx->select('selector', Htmx::NO_MODIFIER);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-select']));
    $this->assertEquals('selector', $render['#attributes']['data-hx-select']);
    // Inherited.
    $this->htmx->select('selector', Htmx::INHERITED);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-select']));
    $this->assertEquals('selector', $render['#attributes']['data-hx-select:inherited']);
    // Append.
    $this->htmx->select('selector', Htmx::APPEND);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-select']));
    $this->assertEquals('selector', $render['#attributes']['data-hx-select:append']);
    // Combined.
    $this->htmx->select('selector', Htmx::INHERITED | Htmx::APPEND);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attributes']['data-hx-select']));
    $this->assertEquals('selector', $render['#attributes']['data-hx-select:inherited:append']);
  }

}
