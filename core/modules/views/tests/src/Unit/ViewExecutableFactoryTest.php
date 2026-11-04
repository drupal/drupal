<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit;

use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\ViewsPluginManager;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutableFactory;
use Drupal\views\ViewsData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Drupal\views\ViewExecutableFactory.
 */
#[CoversClass(ViewExecutableFactory::class)]
#[Group('views')]
class ViewExecutableFactoryTest extends UnitTestCase {

  /**
   * The mock user object.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $user;

  /**
   * The mock request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The mock view entity.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $view;

  /**
   * The ViewExecutableFactory class under test.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewExecutableFactory;

  /**
   * The mocked views data.
   *
   * @var \Drupal\views\ViewsData|\PHPUnit\Framework\MockObject\Stub
   */
  protected $viewsData;

  /**
   * The mocked route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $routeProvider;

  /**
   * The display plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $displayPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->createStub(AccountInterface::class);
    $this->requestStack = new RequestStack();
    $this->view = $this->createStub(ViewEntityInterface::class);
    $this->viewsData = $this->createStub(ViewsData::class);
    $this->routeProvider = $this->createStub(RouteProviderInterface::class);
    $this->displayPluginManager = $this->createStub(ViewsPluginManager::class);
    $this->viewExecutableFactory = new ViewExecutableFactory($this->user, $this->requestStack, $this->viewsData, $this->routeProvider, $this->displayPluginManager);
  }

  /**
   * Tests the get method.
   */
  public function testGet(): void {
    $request_1 = new Request();
    $request_2 = new Request();

    $this->requestStack->push($request_1);

    $executable = $this->viewExecutableFactory->get($this->view);

    $this->assertInstanceOf('Drupal\views\ViewExecutable', $executable);
    $this->assertSame($executable->getRequest(), $request_1);
    $this->assertSame($executable->getUser(), $this->user);

    // Call get() again to ensure a new executable is created with the other
    // request object.
    $this->requestStack->push($request_2);
    $executable = $this->viewExecutableFactory->get($this->view);

    $this->assertInstanceOf('Drupal\views\ViewExecutable', $executable);
    $this->assertSame($executable->getRequest(), $request_2);
    $this->assertSame($executable->getUser(), $this->user);
  }

  /**
   * Tests the get method when current request is null.
   *
   * @see ::get
   */
  public function testGetNoRequest(): void {
    $executable = $this->viewExecutableFactory->get($this->view);

    $this->assertInstanceOf('Drupal\views\ViewExecutable', $executable);
    $this->assertSame($executable->getUser(), $this->user);
    $this->assertSame($executable->getRequest(), NULL);
  }

}
