<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewsTest.
 */

namespace Drupal\views\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Views;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutableFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\views\Views
 */
class ViewsTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Views test',
      'description' => 'Tests the Drupal\views\Views class.',
      'group' => 'Views',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    $user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $container->set('views.executable', new ViewExecutableFactory($user, $request_stack));

    $this->view = new View(array('id' => 'test_view'), 'view');

    $view_storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $view_storage->expects($this->once())
      ->method('load')
      ->with('test_view')
      ->will($this->returnValue($this->view));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('view')
      ->will($this->returnValue($view_storage));
    $container->set('entity.manager', $entity_manager);

    \Drupal::setContainer($container);
  }

  /**
   * Tests the getView() method.
   *
   * @covers ::getView
   */
  public function testGetView() {
    $executable = Views::getView('test_view');
    $this->assertInstanceOf('Drupal\views\ViewExecutable', $executable);
    $this->assertEquals($this->view->id(), $executable->storage->id());
    $this->assertEquals(spl_object_hash($this->view), spl_object_hash($executable->storage));
  }

}
