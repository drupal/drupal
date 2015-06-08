<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\ContentNegotiationTest.
 */

namespace Drupal\Tests\Core;

use Drupal\Core\ContentNegotiation;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\ContentNegotiation
 * @group ContentNegotiation
 */
class ContentNegotiationTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $contentNegotiation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->contentNegotiation = new ContentNegotiation;
  }

  /**
   * Tests the getContentType() method with AJAX iframe upload.
   *
   * @covers ::getContentType
   */
  public function testAjaxIframeUpload() {
    $request = new Request();
    $request->attributes->set('ajax_iframe_upload', '1');

    $this->assertSame('iframeupload', $this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests the specifying a format via query parameters gets used.
   */
  public function testFormatViaQueryParameter() {
    $request = new Request();
    $request->query->set('_format', 'bob');

    $this->assertSame('bob', $this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests the getContentType() method when no priority format is found.
   *
   * @covers ::getContentType
   */
  public function testUnknowContentTypeReturnsHtmlByDefault() {
    $request = new Request();

    $this->assertSame('html', $this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests the getContentType() method when no priority format is found but it's an AJAX request.
   *
   * @covers ::getContentType
   */
  public function testUnknowContentTypeButAjaxRequest() {
    $request = new Request();
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $this->assertSame('ajax', $this->contentNegotiation->getContentType($request));
  }

}
