<?php

namespace Drupal\Tests\views\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\views\ViewExecutableFactory
 * @group views
 */
class ViewExecutableFactoryTest extends UnitTestCase {

  /**
   * The mock user object.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
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
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface|\PHPUnit_Framework_MockObject_MockObject
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
   * @var \Drupal\views\ViewsData|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $viewsData;

  /**
   * The mocked route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->requestStack = new RequestStack();
    $this->view = $this->createMock('Drupal\views\ViewEntityInterface');
    $this->viewsData = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $this->routeProvider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->viewExecutableFactory = new ViewExecutableFactory($this->user, $this->requestStack, $this->viewsData, $this->routeProvider);
  }

  /**
   * Tests the get method.
   *
   * @covers ::get
   */
  public function testGet() {
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

}
