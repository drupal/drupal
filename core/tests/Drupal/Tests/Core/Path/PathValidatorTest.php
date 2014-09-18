<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Path\PathValidatorTest.
 */

namespace Drupal\Tests\Core\Path;

use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\PathValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @coversDefaultClass \Drupal\Core\Path\PathValidator
 * @group Routing
 */
class PathValidatorTest extends UnitTestCase {

  /**
   * The mocked access aware router.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessAwareRouter;

  /**
   * The mocked access unaware router.
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessUnawareRouter;

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * The path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pathProcessor;

  /**
   * The tested path validator.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessAwareRouter = $this->getMock('Drupal\Core\Routing\AccessAwareRouterInterface');
    $this->accessUnawareRouter = $this->getMock('Symfony\Component\Routing\Matcher\UrlMatcherInterface');
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->pathProcessor = $this->getMock('Drupal\Core\PathProcessor\InboundPathProcessorInterface');
    $this->pathValidator = new PathValidator($this->accessAwareRouter, $this->accessUnawareRouter, $this->account, $this->pathProcessor);
  }

  /**
   * Tests the isValid() method for the frontpage.
   *
   * @covers ::isValid
   */
  public function testIsValidWithFrontpage() {
    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('<front>'));
  }

  /**
   * Tests the isValid() method for an external URL.
   *
   * @covers ::isValid
   */
  public function testIsValidWithExternalUrl() {
    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('https://drupal.org'));
  }

  /**
   * Tests the isValid() method with an invalid external URL.
   */
  public function testIsValidWithInvalidExternalUrl() {
    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertFalse($this->pathValidator->isValid('http://'));
  }

  /**
   * Tests the isValid() method with a 'link to any page' permission.
   *
   * @covers ::isValid
   */
  public function testIsValidWithLinkToAnyPageAccount() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(TRUE);
    $this->accessAwareRouter->expects($this->never())
      ->method('match');
    $this->accessUnawareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new ParameterBag(['key' => 'value'])]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);


    $this->assertTrue($this->pathValidator->isValid('test-path'));
  }

  /**
   * Tests the isValid() method without the 'link to any page' permission.
   *
   * @covers ::isValid
   */
  public function testIsValidWithoutLinkToAnyPageAccount() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new ParameterBag(['key' => 'value'])]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $this->assertTrue($this->pathValidator->isValid('test-path'));
  }

  /**
   * Tests the isValid() method with a path alias.
   *
   * @covers ::isValid
   */
  public function testIsValidWithPathAlias() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new ParameterBag(['key' => 'value'])]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->with('path-alias', $this->anything())
      ->willReturn('test-path');

    $this->assertTrue($this->pathValidator->isValid('path-alias'));
  }

  /**
   * Tests the isValid() method with a user without access to the path.
   *
   * @covers ::isValid
   */
  public function testIsValidWithAccessDenied() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willThrowException(new AccessDeniedHttpException());
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $this->assertFalse($this->pathValidator->isValid('test-path'));
  }

  /**
   * Tests the isValid() method with a not working param converting.
   *
   * @covers ::isValid
   */
  public function testIsValidWithFailingParameterConverting() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/entity-test/1')
      ->willThrowException(new ParamNotConvertedException());
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $this->assertFalse($this->pathValidator->isValid('entity-test/1'));
  }

  /**
   * Tests the isValid() method with a not existing path.
   *
   * @covers ::isValid
   */
  public function testIsValidWithNotExistingPath() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/not-existing-path')
      ->willThrowException(new ResourceNotFoundException());
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $this->assertFalse($this->pathValidator->isValid('not-existing-path'));
  }

  /**
   * Tests the getUrlIfValid() method when there is access.
   */
  public function testGetUrlIfValidWithAccess() {
    $this->account->expects($this->exactly(2))
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);

    $this->accessAwareRouter->expects($this->exactly(2))
      ->method('match')
      ->with('/test-path')
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new ParameterBag(['key' => 'value'])]);
    $this->pathProcessor->expects($this->exactly(2))
      ->method('processInbound')
      ->willReturnArgument(0);

    $url = $this->pathValidator->getUrlIfValid('test-path');
    $this->assertInstanceOf('Drupal\Core\Url', $url);

    $this->assertEquals('test_route', $url->getRouteName());
    $this->assertEquals(['key' => 'value'], $url->getRouteParameters());

    // Test with leading /.
    $url = $this->pathValidator->getUrlIfValid('/test-path');
    $this->assertInstanceOf('Drupal\Core\Url', $url);

    $this->assertEquals('test_route', $url->getRouteName());
    $this->assertEquals(['key' => 'value'], $url->getRouteParameters());
  }

  /**
   * Tests the getUrlIfValid() method with a query in the path.
   */
  public function testGetUrlIfValidWithQuery() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);

    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path?k=bar')
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new ParameterBag()]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $url = $this->pathValidator->getUrlIfValid('test-path?k=bar');
    $this->assertInstanceOf('Drupal\Core\Url', $url);

    $this->assertEquals('test_route', $url->getRouteName());
    $this->assertEquals(['k' => 'bar'], $url->getOptions()['query']);
  }

  /**
   * Tests the getUrlIfValid() method where there is no access.
   */
  public function testGetUrlIfValidWithoutAccess() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);

    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willThrowException(new AccessDeniedHttpException());

    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $url = $this->pathValidator->getUrlIfValid('test-path');
    $this->assertFalse($url);
  }

  /**
   * Tests the getUrlIfValid() method with a front page + query + fragments.
   */
  public function testGetUrlIfValidWithFrontPageAndQueryAndFragments() {
    $url = $this->pathValidator->getUrlIfValid('<front>?hei=sen#berg');
    $this->assertEquals('<front>', $url->getRouteName());
    $this->assertEquals(['hei' => 'sen'], $url->getOptions()['query']);
    $this->assertEquals('berg', $url->getOptions()['fragment']);
  }

}

