<?php

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\DrupalTestBrowser;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\BrowserTestBase;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Session;

/**
 * @coversDefaultClass \Drupal\Tests\BrowserTestBase
 * @group Test
 */
class BrowserTestBaseTest extends UnitTestCase {

  protected function mockBrowserTestBaseWithDriver($driver) {
    $session = $this->getMockBuilder(Session::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getDriver'])
      ->getMock();
    $session->expects($this->any())
      ->method('getDriver')
      ->willReturn($driver);

    $btb = $this->getMockBuilder(BrowserTestBase::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getSession'])
      ->getMockForAbstractClass();
    $btb->expects($this->any())
      ->method('getSession')
      ->willReturn($session);

    return $btb;
  }

  /**
   * @covers ::getHttpClient
   */
  public function testGetHttpClient() {
    // Our stand-in for the Guzzle client object.
    $expected = new \stdClass();

    $browserkit_client = $this->getMockBuilder(DrupalTestBrowser::class)
      ->onlyMethods(['getClient'])
      ->getMockForAbstractClass();
    $browserkit_client->expects($this->once())
      ->method('getClient')
      ->willReturn($expected);

    // Because the driver is a BrowserKitDriver, we'll get back a client.
    $driver = new BrowserKitDriver($browserkit_client);
    $btb = $this->mockBrowserTestBaseWithDriver($driver);

    $ref_gethttpclient = new \ReflectionMethod($btb, 'getHttpClient');

    $this->assertSame(get_class($expected), get_class($ref_gethttpclient->invoke($btb)));
  }

  /**
   * @covers ::getHttpClient
   */
  public function testGetHttpClientException() {
    // A driver type that isn't BrowserKitDriver. This should cause a
    // RuntimeException.
    $btb = $this->mockBrowserTestBaseWithDriver(new \stdClass());

    $ref_gethttpclient = new \ReflectionMethod($btb, 'getHttpClient');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The Mink client type stdClass does not support getHttpClient().');
    $ref_gethttpclient->invoke($btb);
  }

  /**
   * Tests that tearDown doesn't call cleanupEnvironment if setUp is not called.
   *
   * @covers ::tearDown
   */
  public function testTearDownWithoutSetUp() {
    $method = 'cleanupEnvironment';
    $this->assertTrue(method_exists(BrowserTestBase::class, $method));
    $btb = $this->getMockBuilder(BrowserTestBase::class)
      ->disableOriginalConstructor()
      ->onlyMethods([$method])
      ->getMockForAbstractClass();
    $btb->expects($this->never())->method($method);
    $ref_tearDown = new \ReflectionMethod($btb, 'tearDown');
    $ref_tearDown->invoke($btb);
  }

}
