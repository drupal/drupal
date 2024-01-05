<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Path;

use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\PathValidator;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @coversDefaultClass \Drupal\Core\Path\PathValidator
 * @group Routing
 */
class PathValidatorTest extends UnitTestCase {

  /**
   * The mocked access aware router.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $accessAwareRouter;

  /**
   * The mocked access unaware router.
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $accessUnawareRouter;

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

  /**
   * The path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface|\PHPUnit\Framework\MockObject\MockObject
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
  protected function setUp(): void {
    parent::setUp();

    $this->accessAwareRouter = $this->createMock('Drupal\Core\Routing\AccessAwareRouterInterface');
    $this->accessUnawareRouter = $this->createMock('Symfony\Component\Routing\Matcher\UrlMatcherInterface');
    $this->account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->pathProcessor = $this->createMock('Drupal\Core\PathProcessor\InboundPathProcessorInterface');
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
   * Tests the isValid() method for <none> (used for jumplinks).
   *
   * @covers ::isValid
   */
  public function testIsValidWithNone() {
    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('<none>'));
  }

  /**
   * Tests the isValid() method for an external URL.
   *
   * @covers ::isValid
   */
  public function testIsValidWithExternalUrl() {
    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('https://www.drupal.org'));
  }

  /**
   * Tests the isValid() method with an invalid external URL.
   *
   * @covers ::isValid
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
   * @covers ::getPathAttributes
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
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new InputBag(['key' => 'value'])]);
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
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new InputBag(['key' => 'value'])]);
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
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new InputBag(['key' => 'value'])]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->with('/path-alias', $this->anything())
      ->willReturn('/test-path');

    $this->assertTrue($this->pathValidator->isValid('path-alias'));
  }

  /**
   * Tests the isValid() method with a user without access to the path.
   *
   * @covers ::isValid
   * @covers ::getPathAttributes
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
   * @covers ::isValid
   * @covers ::getPathAttributes
   */
  public function testIsValidWithResourceNotFound() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willThrowException(new ResourceNotFoundException());
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $this->assertFalse($this->pathValidator->isValid('test-path'));
  }

  /**
   * @covers ::isValid
   * @covers ::getPathAttributes
   */
  public function testIsValidWithParamNotConverted() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willThrowException(new ParamNotConvertedException());
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $this->assertFalse($this->pathValidator->isValid('test-path'));
  }

  /**
   * @covers ::isValid
   * @covers ::getPathAttributes
   */
  public function testIsValidWithMethodNotAllowed() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willThrowException(new MethodNotAllowedException([]));
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
   * Tests the isValid() method with a non-existent path.
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
   *
   * @covers ::getUrlIfValid
   * @covers ::getPathAttributes
   */
  public function testGetUrlIfValidWithAccess() {
    $this->account->expects($this->exactly(2))
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);

    $this->accessAwareRouter->expects($this->exactly(2))
      ->method('match')
      ->with('/test-path')
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new InputBag(['key' => 'value'])]);
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
   *
   * @covers ::getUrlIfValid
   */
  public function testGetUrlIfValidWithQuery() {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);

    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path?k=bar')
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new InputBag()]);
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
   *
   * @covers ::getUrlIfValid
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
   *
   * @covers ::getUrlIfValid
   */
  public function testGetUrlIfValidWithFrontPageAndQueryAndFragments() {
    $url = $this->pathValidator->getUrlIfValid('<front>?hei=sen#berg');
    $this->assertEquals('<front>', $url->getRouteName());
    $this->assertEquals(['hei' => 'sen'], $url->getOptions()['query']);
    $this->assertEquals('berg', $url->getOptions()['fragment']);
  }

  /**
   * Tests the getUrlIfValidWithoutAccessCheck() method.
   *
   * @covers ::getUrlIfValidWithoutAccessCheck
   * @covers ::getPathAttributes
   */
  public function testGetUrlIfValidWithoutAccessCheck() {
    $this->account->expects($this->never())
      ->method('hasPermission')
      ->with('link to any page');
    $this->accessAwareRouter->expects($this->never())
      ->method('match');
    $this->accessUnawareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willReturn([RouteObjectInterface::ROUTE_NAME => 'test_route', '_raw_variables' => new InputBag(['key' => 'value'])]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck('test-path');
    $this->assertInstanceOf('Drupal\Core\Url', $url);

    $this->assertEquals('test_route', $url->getRouteName());
    $this->assertEquals(['key' => 'value'], $url->getRouteParameters());
  }

}
