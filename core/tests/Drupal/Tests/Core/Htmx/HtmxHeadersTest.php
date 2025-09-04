<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Htmx;

use Drupal\Core\GeneratedUrl;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Htmx\HtmxLocationResponseData;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test all header-related Htmx public methods.
 */
#[CoversClass(Htmx::class)]
#[Group('Htmx')]
class HtmxHeadersTest extends UnitTestCase {

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

  protected function apply(array $render = []): array {
    $this->htmx->applyTo($render);
    return $render;
  }

  /**
   * Test location header with simple URL object.
   */
  public function testLocationHeaderUrl(): void {
    $this->htmx->locationHeader($this->url);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-location', $value[0]);
    $this->assertEquals('https://www.example.test/common-test/destination', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test location header with complex data.
   */
  public function testLocationHeaderResponseData(): void {
    $data = new HtmxLocationResponseData(
      path: $this->url,
      source: 'source-value',
      event: 'event-value',
      handler: 'handler-value',
      target: 'target-value',
      swap: 'swap-value',
      values: ['one' => '1', 'two' => '2'],
      headers: ['Header-one' => 'one'],
      select: 'select-value',
    );
    $this->htmx->locationHeader($data);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-location', $value[0]);
    $this->assertEquals(
      '{"path":"https:\/\/www.example.test\/common-test\/destination","source":"source-value","event":"event-value","headers":{"Header-one":"one"},"handler":"handler-value","target":"target-value","swap":"swap-value","select":"select-value","values":{"one":"1","two":"2"}}',
      $value[1]
    );
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test push url header with a simple URL object.
   */
  public function testPushUrlHeader(): void {
    $this->htmx->pushUrlHeader($this->url);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-push-url', $value[0]);
    $this->assertEquals('https://www.example.test/common-test/destination', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test replace url header with a simple URL object.
   */
  public function testReplaceUrlHeader(): void {
    $this->htmx->replaceUrlHeader($this->url);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-replace-url', $value[0]);
    $this->assertEquals('https://www.example.test/common-test/destination', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test redirect header with simple URL object.
   */
  public function testRedirectHeader(): void {
    $this->htmx->redirectHeader($this->url);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-redirect', $value[0]);
    $this->assertEquals('https://www.example.test/common-test/destination', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test refresh header.
   */
  public function testRefreshHeader(): void {
    // TRUE case.
    $this->htmx->refreshHeader(TRUE);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-refresh', $value[0]);
    $this->assertEquals('true', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
    // FALSE case.
    $this->htmx->refreshHeader(FALSE);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-refresh', $value[0]);
    $this->assertEquals('false', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test re-swap header.
   */
  public function testReswapHeader(): void {
    $this->htmx->reswapHeader('foo');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-reswap', $value[0]);
    $this->assertEquals('foo', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test re-target header.
   */
  public function testRetargetHeader(): void {
    $this->htmx->retargetHeader('foo');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-retarget', $value[0]);
    $this->assertEquals('foo', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test re-select header.
   */
  public function testReselectHeader(): void {
    $this->htmx->reselectHeader('foo');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-reselect', $value[0]);
    $this->assertEquals('foo', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test trigger header with simple data.
   */
  public function testTriggerHeader(): void {
    $this->htmx->triggerHeader('foo');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-trigger', $value[0]);
    $this->assertEquals('foo', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test target header with complex data.
   */
  public function testTriggerHeaderComplex(): void {
    $this->htmx->triggerHeader([
      'showMessage' => [
        'level' => 'info',
        'message' => 'Trigger Set',
      ],
    ]);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-trigger', $value[0]);
    $this->assertEquals('{"showMessage":{"level":"info","message":"Trigger Set"}}', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test trigger header with simple data.
   */
  public function testTriggerAfterSettleHeader(): void {
    $this->htmx->triggerAfterSettleHeader('foo');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-trigger-after-settle', $value[0]);
    $this->assertEquals('foo', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test trigger after settle header is correctly set with provided parameters.
   */
  public function testTriggerAfterSettleHeaderComplex(): void {
    $this->htmx->triggerAfterSettleHeader([
      'showMessage' => [
        'level' => 'info',
        'message' => 'Trigger Set',
      ],
    ]);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-trigger-after-settle', $value[0]);
    $this->assertEquals('{"showMessage":{"level":"info","message":"Trigger Set"}}', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test trigger header with simple data.
   */
  public function testTriggerAfterSwapHeader(): void {
    $this->htmx->triggerAfterSwapHeader('foo');
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-trigger-after-swap', $value[0]);
    $this->assertEquals('foo', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

  /**
   * Test trigger after settle header is correctly set with provided parameters.
   */
  public function testTriggerAfterSwapHeaderComplex(): void {
    $this->htmx->triggerAfterSwapHeader([
      'showMessage' => [
        'level' => 'info',
        'message' => 'Trigger Set',
      ],
    ]);
    $render = $this->apply();
    $this->assertTrue(isset($render['#attached']['http_header']));
    $this->assertIsArray($render['#attached']['http_header']);
    $value = reset($render['#attached']['http_header']);
    $this->assertEquals('hx-trigger-after-swap', $value[0]);
    $this->assertEquals('{"showMessage":{"level":"info","message":"Trigger Set"}}', $value[1]);
    $this->assertEquals(TRUE, $value[2]);
  }

}
