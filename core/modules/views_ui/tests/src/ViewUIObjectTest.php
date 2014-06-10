<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ViewUIObjectTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewExecutable;
use Drupal\views_ui\ViewUI;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the ViewUI class.
 *
 * @see \Drupal\views_ui\ViewUI
 */
class ViewUIObjectTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'View UI Object',
      'description' => 'Test the ViewUI class.',
      'group' => 'Views UI'
    );
  }

  /**
   * Tests entity method decoration.
   */
  public function testEntityDecoration() {
    $method_args = array();
    $method_args['setOriginalId'] = array(12);
    $method_args['setStatus'] = array(TRUE);
    $method_args['enforceIsNew'] = array(FALSE);
    $method_args['label'] = array(LanguageInterface::LANGCODE_NOT_SPECIFIED);

    $reflection = new \ReflectionClass('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $interface_methods = array();
    foreach ($reflection->getMethods() as $reflection_method) {
      $interface_methods[] = $reflection_method->getName();

      // EntityInterface::isNew() is missing from the list of methods, because it
      // calls id(), which breaks the ->expect($this->once()) call. Call it later.
      // EntityInterface::isSyncing() is only called during syncing process.
      // EntityInterface::isUninstalling() is only called during uninstallation
      // process. ConfigEntityInterface::getConfigDependencyName() and
      // ConfigEntityInterface::calculateDependencies() are only used for
      // dependency management.
      if (!in_array($reflection_method->getName(), ['isNew', 'isSyncing', 'isUninstalling', 'getConfigDependencyName', 'calculateDependencies'])) {
        if (count($reflection_method->getParameters()) == 0) {
          $method_args[$reflection_method->getName()] = array();
        }
      }
    }

    $storage = $this->getMock('Drupal\views\Entity\View', $interface_methods, array(array(), 'view'));
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->setConstructorArgs(array($storage))
      ->getMock();

    $view_ui = new ViewUI($storage, $executable);

    foreach ($method_args as $method => $args) {
      $method_mock = $storage->expects($this->once())
        ->method($method);
      foreach ($args as $arg) {
        $method_mock->with($this->equalTo($arg));
      }
      call_user_func_array(array($view_ui, $method), $args);
    }

    $storage->expects($this->once())
      ->method('isNew');
    $view_ui->isNew();
  }

  /**
   * Tests the isLocked method.
   */
  public function testIsLocked() {
    $storage = $this->getMock('Drupal\views\Entity\View', array(), array(array(), 'view'));
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->setConstructorArgs(array($storage))
      ->getMock();
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $account->expects($this->exactly(2))
      ->method('id')
      ->will($this->returnValue(1));

    $container = new ContainerBuilder();
    $container->set('current_user', $account);
    \Drupal::setContainer($container);

    $view_ui = new ViewUI($storage, $executable);

    // A view_ui without a lock object is not locked.
    $this->assertFalse($view_ui->isLocked());

    // Set the lock object with a different owner than the mocked account above.
    $lock = (object) array(
      'owner' => 2,
      'data' => array(),
      'updated' => REQUEST_TIME,
    );
    $view_ui->lock = $lock;
    $this->assertTrue($view_ui->isLocked());

    // Set a different lock object with the same object as the mocked account.
    $lock = (object) array(
      'owner' => 1,
      'data' => array(),
      'updated' => REQUEST_TIME,
    );
    $view_ui->lock = $lock;
    $this->assertFalse($view_ui->isLocked());
  }

}
