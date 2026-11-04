<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Path;

use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Tests Drupal\Core\Path\PathValidator.
 */
#[CoversClass(PathValidator::class)]
#[Group('Routing')]
class PathValidatorTest extends UnitTestCase {

  /**
   * The access aware router stub.
   */
  protected AccessAwareRouterInterface&Stub $accessAwareRouter;

  /**
   * The access unaware router stub.
   */
  protected UrlMatcherInterface&Stub $accessUnawareRouter;

  /**
   * The account stub.
   */
  protected AccountInterface&Stub $account;

  /**
   * The path processor stub.
   */
  protected InboundPathProcessorInterface&Stub $pathProcessor;

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

    $this->accessAwareRouter = $this->createStub(AccessAwareRouterInterface::class);
    $this->accessUnawareRouter = $this->createStub(UrlMatcherInterface::class);
    $this->account = $this->createStub(AccountInterface::class);
    $this->pathProcessor = $this->createStub(InboundPathProcessorInterface::class);
    $this->pathValidator = new PathValidator($this->accessAwareRouter, $this->accessUnawareRouter, $this->account, $this->pathProcessor);
  }

  /**
   * Reinitializes the access aware router as a mock object.
   */
  protected function setUpMockAccessAwareRouter(): void {
    $this->accessAwareRouter = $this->createMock(AccessAwareRouterInterface::class);
    $reflection = new \ReflectionProperty($this->pathValidator, 'accessAwareRouter');
    $reflection->setValue($this->pathValidator, $this->accessAwareRouter);
  }

  /**
   * Reinitializes the access unaware router as a mock object.
   */
  protected function setUpMockAccessUnawareRouter(): void {
    $this->accessUnawareRouter = $this->createMock(UrlMatcherInterface::class);
    $reflection = new \ReflectionProperty($this->pathValidator, 'accessUnawareRouter');
    $reflection->setValue($this->pathValidator, $this->accessUnawareRouter);
  }

  /**
   * Reinitializes the account as a mock object.
   */
  protected function setUpMockAccount(): void {
    $this->account = $this->createMock(AccountInterface::class);
    $reflection = new \ReflectionProperty($this->pathValidator, 'account');
    $reflection->setValue($this->pathValidator, $this->account);
  }

  /**
   * Reinitializes the inbound path processor as a mock object.
   */
  protected function setUpMockInboundPathProcessor(): void {
    $this->pathProcessor = $this->createMock(InboundPathProcessorInterface::class);
    $reflection = new \ReflectionProperty($this->pathValidator, 'pathProcessor');
    $reflection->setValue($this->pathValidator, $this->pathProcessor);
  }

  /**
   * Tests the isValid() method for the frontpage.
   */
  public function testIsValidWithFrontpage(): void {
    $this->setUpMockAccessAwareRouter();

    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('<front>'));
  }

  /**
   * Tests the isValid() method for <none> (used for jump links).
   */
  public function testIsValidWithNone(): void {
    $this->setUpMockAccessAwareRouter();

    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('<none>'));
  }

  /**
   * Tests the isValid() method for an external URL.
   */
  public function testIsValidWithExternalUrl(): void {
    $this->setUpMockAccessAwareRouter();

    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertTrue($this->pathValidator->isValid('https://www.drupal.org'));
  }

  /**
   * Tests the isValid() method with an invalid external URL.
   */
  public function testIsValidWithInvalidExternalUrl(): void {
    $this->setUpMockAccessAwareRouter();

    $this->accessAwareRouter->expects($this->never())
      ->method('match');

    $this->assertFalse($this->pathValidator->isValid('http://'));
  }

  /**
   * Tests the isValid() method with a 'link to any page' permission.
   *
   * @see ::isValid
   * @see ::getPathAttributes
   */
  public function testIsValidWithLinkToAnyPageAccount(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   */
  public function testIsValidWithoutLinkToAnyPageAccount(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   */
  public function testIsValidWithPathAlias(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   * @see ::isValid
   * @see ::getPathAttributes
   */
  public function testIsValidWithAccessDenied(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   * @see ::isValid
   * @see ::getPathAttributes
   */
  public function testIsValidWithResourceNotFound(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   * @see ::isValid
   * @see ::getPathAttributes
   */
  public function testIsValidWithParamNotConverted(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   * @see ::isValid
   * @see ::getPathAttributes
   */
  public function testIsValidWithMethodNotAllowed(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   */
  public function testIsValidWithFailingParameterConverting(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   */
  public function testIsValidWithNotExistingPath(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   * @see ::getUrlIfValid
   * @see ::getPathAttributes
   */
  public function testGetUrlIfValidWithAccess(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   */
  public function testGetUrlIfValidWithQuery(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   */
  public function testGetUrlIfValidWithoutAccess(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
  public function testGetUrlIfValidWithFrontPageAndQueryAndFragments(): void {
    $url = $this->pathValidator->getUrlIfValid('<front>?hei=sen#berg');
    $this->assertEquals('<front>', $url->getRouteName());
    $this->assertEquals(['hei' => 'sen'], $url->getOptions()['query']);
    $this->assertEquals('berg', $url->getOptions()['fragment']);
  }

  /**
   * Tests the getUrlIfValidWithoutAccessCheck() method.
   *
   * @see ::getUrlIfValidWithoutAccessCheck
   * @see ::getPathAttributes
   */
  public function testGetUrlIfValidWithoutAccessCheck(): void {
    $this->setUpMockAccessAwareRouter();
    $this->setUpMockAccessUnawareRouter();
    $this->setUpMockAccount();
    $this->setUpMockInboundPathProcessor();

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
   * @see ::getUrlIfValidWithoutAccessCheck
   * @see ::getUrl
   */
  public function testGetUrlIfValidWithoutAccessCheckWithInvalidPath(): void {
    $this->setUpMockInboundPathProcessor();

    // URLs must not start nor end with ASCII control characters or spaces.
    $this->assertFalse($this->pathValidator->getUrlIfValidWithoutAccessCheck('foo '));
    // Also check URL-encoded variant.
    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->willReturnArgument(0);
    $this->assertFalse($this->pathValidator->getUrlIfValidWithoutAccessCheck('foo%20'));
  }

}
