<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\VIewExecutableFactoryTest.
 */

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->requestStack = new RequestStack();
    $this->view = $this->getMock('Drupal\views\ViewStorageInterface');
    $this->viewExecutableFactory = new ViewExecutableFactory($this->user, $this->requestStack);
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
