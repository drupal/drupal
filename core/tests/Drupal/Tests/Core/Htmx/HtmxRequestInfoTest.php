<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Htmx;

use Drupal\Core\Htmx\HtmxRequestInfoTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test all HtmxRequestInfoTrait methods.
 */
#[CoversClass(HtmxRequestInfoTrait::class)]
#[Group('Htmx')]
class HtmxRequestInfoTest extends UnitTestCase {

  use HtmxRequestInfoTrait;

  /**
   * A simulated request.
   */
  protected Request $request;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->request = new Request();
  }

  /**
   * Tests the isHtmxRequest method.
   */
  public function testIsHtmxRequest(): void {
    // Test with the header not present.
    $this->assertFalse($this->isHtmxRequest());

    // Test with the header present.
    $this->request->headers->set('HX-Request', 'true');
    $this->assertTrue($this->isHtmxRequest());
  }

  /**
   * Tests the isHtmxBoosted method.
   */
  public function testIsHtmxBoosted(): void {
    // Test with the header not present.
    $this->assertFalse($this->isHtmxBoosted());

    // Test with the header present.
    $this->request->headers->set('HX-Boosted', 'true');
    $this->assertTrue($this->isHtmxBoosted());
  }

  /**
   * Tests the getHtmxCurrentUrl method.
   */
  public function testGetHtmxCurrentUrl(): void {
    // Test with the header not present.
    $this->assertEquals('', $this->getHtmxCurrentUrl());

    // Test with the header present.
    $this->request->headers->set('HX-Current-URL', 'https://example.com/page');
    $this->assertEquals('https://example.com/page', $this->getHtmxCurrentUrl());
  }

  /**
   * Tests the isHtmxHistoryRestoration method.
   */
  public function testIsHtmxHistoryRestoration(): void {
    // Test with the header not present.
    $this->assertFalse($this->isHtmxHistoryRestoration());

    // Test with the header present.
    $this->request->headers->set('HX-History-Restore-Request', 'true');
    $this->assertTrue($this->isHtmxHistoryRestoration());
  }

  /**
   * Tests the getHtmxTarget method.
   */
  public function testGetHtmxTarget(): void {
    // Test with the header not present.
    $this->assertEquals('', $this->getHtmxTarget());

    // Test with the header present.
    $this->request->headers->set('HX-Target', 'input#submit-button');
    $this->assertEquals('input#submit-button', $this->getHtmxTarget());
  }

  /**
   * Tests the getHtmxTrigger method.
   */
  public function testGetHtmxSource(): void {
    // Test with the header not present.
    $this->assertEquals('', $this->getHtmxSource());

    // Test with the header present.
    $this->request->headers->set('HX-Source', 'input#submit-button');
    $this->assertEquals('input#submit-button', $this->getHtmxSource());
  }

  /**
   * Tests the getHtmxTriggerName method.
   */
  public function testGetHtmxTriggerName(): void {
    // Test with the header not present.
    $this->assertEquals('', $this->getHtmxTriggerName());

    // Test with the header present.
    $this->request->headers->set('HX-Source', 'input[name="submit-button"]');
    $this->assertEquals('submit-button', $this->getHtmxTriggerName());
  }

  /**
   * Tests the getHtmxRequestType method.
   */
  public function testGetHtmxRequestType(): void {
    // Test with the header not present.
    $this->assertEquals('', $this->getHtmxRequestType());

    // Test with the header present.
    $this->request->headers->set('HX-Request-Type', 'request-type');
    $this->assertEquals('request-type', $this->getHtmxRequestType());
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequest(): Request {
    return $this->request;
  }

}
