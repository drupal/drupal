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
   * Tests the getContentType() method when a priority format is found.
   *
   * @dataProvider priorityFormatProvider
   * @covers ::getContentType
   */
  public function testAPriorityFormatIsFound($priority, $format) {
    $request = new Request();
    $request->setFormat($format['format'], $format['mime_type']);
    $request->headers->set('Accept', sprintf('%s,application/json', $format['mime_type']));

    $this->assertSame($priority, $this->contentNegotiation->getContentType($request));
  }

  public function priorityFormatProvider()
  {
    return [
      ['html', ['format' => 'html', 'mime_type' => 'text/html']],
    ];
  }

  /**
   * Tests the getContentType() method when no priority format is found but a valid one is found.
   *
   * @covers ::getContentType
   */
  public function testNoPriorityFormatIsFoundButReturnsTheFirstValidOne() {
    $request = new Request();
    $request->headers->set('Accept', 'application/rdf+xml');

    $this->assertSame('rdf', $this->contentNegotiation->getContentType($request));
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
