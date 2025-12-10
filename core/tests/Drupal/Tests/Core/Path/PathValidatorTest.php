<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Path;

use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Tests Drupal\Core\Path\PathValidator.
 */
#[CoversClass(PathValidator::class)]
#[Group('Routing')]
class PathValidatorTest extends UnitTestCase {

  /**
   * The mocked access aware router.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $accessAwareRouter;

  /**
   * The mocked access unaware router.
   *
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
   * @legacy-covers ::isValid
   */
  public function testIsValidWithFrontpage(): void {
    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('<front>'));
  }

  /**
   * Tests the isValid() method for <none> (used for jump links).
   *
   * @legacy-covers ::isValid
   */
  public function testIsValidWithNone(): void {
    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('<none>'));
  }

  /**
   * Tests the isValid() method for an external URL.
   *
   * @legacy-covers ::isValid
   */
  public function testIsValidWithExternalUrl(): void {
    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('https://www.drupal.org'));
  }

  /**
   * Tests the isValid() method with an invalid external URL.
   *
   * @legacy-covers ::isValid
   */
  public function testIsValidWithInvalidExternalUrl(): void {
    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertFalse($this->pathValidator->isValid('http://'));
  }

  /**
   * Tests the isValid() method with a 'link to any page' permission.
   *
   * @legacy-covers ::isValid
   * @legacy-covers ::getPathAttributes
   */
  public function testIsValidWithLinkToAnyPageAccount(): void {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(TRUE);
    $this->accessAwareRouter->expects($this->never())
      ->method('match');
    $this->accessUnawareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willReturn([
        RouteObjectInterface::ROUTE_NAME => 'test_route',
        '_raw_variables' => new InputBag(['key' => 'value']),
      ]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $this->assertTrue($this->pathValidator->isValid('test-path'));
  }

  /**
   * Tests the isValid() method without the 'link to any page' permission.
   *
   * @legacy-covers ::isValid
   */
  public function testIsValidWithoutLinkToAnyPageAccount(): void {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willReturn([
        RouteObjectInterface::ROUTE_NAME => 'test_route',
        '_raw_variables' => new InputBag(['key' => 'value']),
      ]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $this->assertTrue($this->pathValidator->isValid('test-path'));
  }

  /**
   * Tests the isValid() method with a path alias.
   *
   * @legacy-covers ::isValid
   */
  public function testIsValidWithPathAlias(): void {
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);
    $this->accessUnawareRouter->expects($this->never())
      ->method('match');
    $this->accessAwareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willReturn([
        RouteObjectInterface::ROUTE_NAME => 'test_route',
        '_raw_variables' => new InputBag(['key' => 'value']),
      ]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->with('/path-alias', $this->anything())
      ->willReturn('/test-path');

    $this->assertTrue($this->pathValidator->isValid('path-alias'));
  }

  /**
   * Tests the isValid() method with a user without access to the path.
   *
   * @legacy-covers ::isValid
   * @legacy-covers ::getPathAttributes
   */
  public function testIsValidWithAccessDenied(): void {
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
   * Tests is valid with resource not found.
   *
   * @legacy-covers ::isValid
   * @legacy-covers ::getPathAttributes
   */
  public function testIsValidWithResourceNotFound(): void {
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
   * Tests is valid with param not converted.
   *
   * @legacy-covers ::isValid
   * @legacy-covers ::getPathAttributes
   */
  public function testIsValidWithParamNotConverted(): void {
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
   * Tests is valid with method not allowed.
   *
   * @legacy-covers ::isValid
   * @legacy-covers ::getPathAttributes
   */
  public function testIsValidWithMethodNotAllowed(): void {
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
   * @legacy-covers ::isValid
   */
  public function testIsValidWithFailingParameterConverting(): void {
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
   * @legacy-covers ::isValid
   */
  public function testIsValidWithNotExistingPath(): void {
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
   * @legacy-covers ::getUrlIfValid
   * @legacy-covers ::getPathAttributes
   */
  public function testGetUrlIfValidWithAccess(): void {
    $this->account->expects($this->exactly(2))
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(FALSE);

    $this->accessAwareRouter->expects($this->exactly(2))
      ->method('match')
      ->with('/test-path')
      ->willReturn([
        RouteObjectInterface::ROUTE_NAME => 'test_route',
        '_raw_variables' => new InputBag(['key' => 'value']),
      ]);
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
   * @legacy-covers ::getUrlIfValid
   */
  public function testGetUrlIfValidWithQuery(): void {
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
   * @legacy-covers ::getUrlIfValid
   */
  public function testGetUrlIfValidWithoutAccess(): void {
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
   * @legacy-covers ::getUrlIfValid
   */
  public function testGetUrlIfValidWithFrontPageAndQueryAndFragments(): void {
    $url = $this->pathValidator->getUrlIfValid('<front>?hei=sen#berg');
    $this->assertEquals('<front>', $url->getRouteName());
    $this->assertEquals(['hei' => 'sen'], $url->getOptions()['query']);
    $this->assertEquals('berg', $url->getOptions()['fragment']);
  }

  /**
   * Tests the getUrlIfValidWithoutAccessCheck() method.
   *
   * @legacy-covers ::getUrlIfValidWithoutAccessCheck
   * @legacy-covers ::getPathAttributes
   */
  public function testGetUrlIfValidWithoutAccessCheck(): void {
    $this->account->expects($this->never())
      ->method('hasPermission')
      ->with('link to any page');
    $this->accessAwareRouter->expects($this->never())
      ->method('match');
    $this->accessUnawareRouter->expects($this->once())
      ->method('match')
      ->with('/test-path')
      ->willReturn([
        RouteObjectInterface::ROUTE_NAME => 'test_route',
        '_raw_variables' => new InputBag(['key' => 'value']),
      ]);
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);

    $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck('test-path');
    $this->assertInstanceOf('Drupal\Core\Url', $url);

    $this->assertEquals('test_route', $url->getRouteName());
    $this->assertEquals(['key' => 'value'], $url->getRouteParameters());
  }

  /**
   * Tests the getUrlIfValidWithoutAccessCheck() method with an invalid path.
   *
   * @legacy-covers ::getUrlIfValidWithoutAccessCheck
   * @legacy-covers ::getUrl
   */
  public function testGetUrlIfValidWithoutAccessCheckWithInvalidPath(): void {
    // URLs must not start nor end with ASCII control characters or spaces.
    $this->assertFalse($this->pathValidator->getUrlIfValidWithoutAccessCheck('foo '));
    // Also check URL-encoded variant.
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);
    $this->assertFalse($this->pathValidator->getUrlIfValidWithoutAccessCheck('foo%20'));
  }

}
