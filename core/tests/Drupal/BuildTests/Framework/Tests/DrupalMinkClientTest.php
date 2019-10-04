<?php

namespace Drupal\BuildTests\Framework\Tests;

use Drupal\BuildTests\Framework\DrupalMinkClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\Response;

/**
 * Test \Drupal\BuildTests\Framework\DrupalMinkClient.
 *
 * This test is adapted from \Symfony\Component\BrowserKit\Tests\ClientTest.
 *
 * @coversDefaultClass \Drupal\BuildTests\Framework\DrupalMinkClient
 *
 * @group Build
 */
class DrupalMinkClientTest extends TestCase {

  /**
   * @dataProvider getTestsForMetaRefresh
   * @covers ::getMetaRefreshUrl
   */
  public function testFollowMetaRefresh(string $content, string $expectedEndingUrl, bool $followMetaRefresh = TRUE) {
    $client = new TestClient();
    $client->followMetaRefresh($followMetaRefresh);
    $client->setNextResponse(new Response($content));
    $client->request('GET', 'http://www.example.com/foo/foobar');
    $this->assertEquals($expectedEndingUrl, $client->getRequest()->getUri());
  }

  public function getTestsForMetaRefresh() {
    return [
      ['<html><head><meta http-equiv="Refresh" content="4" /><meta http-equiv="refresh" content="0; URL=http://www.example.com/redirected"/></head></html>', 'http://www.example.com/redirected'],
      ['<html><head><meta http-equiv="refresh" content="0;URL=http://www.example.com/redirected"/></head></html>', 'http://www.example.com/redirected'],
      ['<html><head><meta http-equiv="refresh" content="0;URL=\'http://www.example.com/redirected\'"/></head></html>', 'http://www.example.com/redirected'],
      ['<html><head><meta http-equiv="refresh" content=\'0;URL="http://www.example.com/redirected"\'/></head></html>', 'http://www.example.com/redirected'],
      ['<html><head><meta http-equiv="refresh" content="0; URL = http://www.example.com/redirected"/></head></html>', 'http://www.example.com/redirected'],
      ['<html><head><meta http-equiv="refresh" content="0;URL= http://www.example.com/redirected  "/></head></html>', 'http://www.example.com/redirected'],
      ['<html><head><meta http-equiv="refresh" content="0;url=http://www.example.com/redirected  "/></head></html>', 'http://www.example.com/redirected'],
      ['<html><head><noscript><meta http-equiv="refresh" content="0;URL=http://www.example.com/redirected"/></noscript></head></head></html>', 'http://www.example.com/redirected'],
      // Non-zero timeout should not result in a redirect.
      ['<html><head><meta http-equiv="refresh" content="4; URL=http://www.example.com/redirected"/></head></html>', 'http://www.example.com/foo/foobar'],
      ['<html><body></body></html>', 'http://www.example.com/foo/foobar'],
      // HTML 5 allows the meta tag to be placed in head or body.
      ['<html><body><meta http-equiv="refresh" content="0;url=http://www.example.com/redirected"/></body></html>', 'http://www.example.com/redirected'],
      // Valid meta refresh should not be followed if disabled.
      ['<html><head><meta http-equiv="refresh" content="0;URL=http://www.example.com/redirected"/></head></html>', 'http://www.example.com/foo/foobar', FALSE],
      'drupal-1' => ['<html><head><meta http-equiv="Refresh" content="0; URL=/update.php/start?id=2&op=do_nojs" /></body></html>', 'http://www.example.com/update.php/start?id=2&op=do_nojs'],
      'drupal-2' => ['<html><head><noscript><meta http-equiv="Refresh" content="0; URL=/update.php/start?id=2&op=do_nojs" /></noscript></body></html>', 'http://www.example.com/update.php/start?id=2&op=do_nojs'],
    ];
  }

  /**
   * @covers ::request
   */
  public function testBackForwardMetaRefresh() {
    $client = new TestClient();
    $client->followMetaRefresh();

    // First request.
    $client->request('GET', 'http://www.example.com/first-page');

    $content = '<html><head><meta http-equiv="Refresh" content="0; URL=/refreshed" /></body></html>';
    $client->setNextResponse(new Response($content, 200));
    $client->request('GET', 'http://www.example.com/refresh-from-here');

    $this->assertEquals('http://www.example.com/refreshed', $client->getRequest()->getUri());

    $client->back();
    $this->assertEquals('http://www.example.com/first-page', $client->getRequest()->getUri());

    $client->forward();
    $this->assertEquals('http://www.example.com/refreshed', $client->getRequest()->getUri());
  }

}

/**
 * Special client that can return a given response on the first doRequest().
 */
class TestClient extends DrupalMinkClient {

  protected $nextResponse = NULL;

  public function setNextResponse(Response $response) {
    $this->nextResponse = $response;
  }

  protected function doRequest($request) {
    if (NULL === $this->nextResponse) {
      return new Response();
    }

    $response = $this->nextResponse;
    $this->nextResponse = NULL;

    return $response;
  }

}
