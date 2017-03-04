<?php

namespace Drupal\Tests\views_ui\Unit;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views_ui\ViewUI;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views_ui\ViewUI
 * @group views_ui
 */
class ViewUIObjectTest extends UnitTestCase {

  /**
   * Tests entity method decoration.
   */
  public function testEntityDecoration() {
    $method_args = [];
    $method_args['setOriginalId'] = [12];
    $method_args['setStatus'] = [TRUE];
    $method_args['enforceIsNew'] = [FALSE];
    $method_args['label'] = [LanguageInterface::LANGCODE_NOT_SPECIFIED];

    $reflection = new \ReflectionClass('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $interface_methods = [];
    foreach ($reflection->getMethods() as $reflection_method) {
      $interface_methods[] = $reflection_method->getName();

      // EntityInterface::isNew() is missing from the list of methods, because it
      // calls id(), which breaks the ->expect($this->once()) call. Call it later.
      // EntityInterface::isSyncing() is only called during syncing process.
      // EntityInterface::isUninstalling() is only called during uninstallation
      // process. EntityInterface::getConfigDependencyName() and
      // ConfigEntityInterface::calculateDependencies() are only used for
      // dependency management.
      if (!in_array($reflection_method->getName(), ['isNew', 'isSyncing', 'isUninstalling', 'getConfigDependencyKey', 'getConfigDependencyName', 'calculateDependencies'])) {
        if (count($reflection_method->getParameters()) == 0) {
          $method_args[$reflection_method->getName()] = [];
        }
      }
    }

    $storage = $this->getMock('Drupal\views\Entity\View', $interface_methods, [[], 'view']);
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->setConstructorArgs([$storage])
      ->getMock();
    $storage->set('executable', $executable);

    $view_ui = new ViewUI($storage);

    foreach ($method_args as $method => $args) {
      $method_mock = $storage->expects($this->once())
        ->method($method);
      foreach ($args as $arg) {
        $method_mock->with($this->equalTo($arg));
      }
      call_user_func_array([$view_ui, $method], $args);
    }

    $storage->expects($this->once())
      ->method('isNew');
    $view_ui->isNew();
  }

  /**
   * Tests the isLocked method.
   */
  public function testIsLocked() {
    $storage = $this->getMock('Drupal\views\Entity\View', [], [[], 'view']);
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->setConstructorArgs([$storage])
      ->getMock();
    $storage->set('executable', $executable);
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $account->expects($this->exactly(2))
      ->method('id')
      ->will($this->returnValue(1));

    $container = new ContainerBuilder();
    $container->set('current_user', $account);
    \Drupal::setContainer($container);

    $view_ui = new ViewUI($storage);

    // A view_ui without a lock object is not locked.
    $this->assertFalse($view_ui->isLocked());

    // Set the lock object with a different owner than the mocked account above.
    $lock = (object) [
      'owner' => 2,
      'data' => [],
      'updated' => (int) $_SERVER['REQUEST_TIME'],
    ];
    $view_ui->lock = $lock;
    $this->assertTrue($view_ui->isLocked());

    // Set a different lock object with the same object as the mocked account.
    $lock = (object) [
      'owner' => 1,
      'data' => [],
      'updated' => (int) $_SERVER['REQUEST_TIME'],
    ];
    $view_ui->lock = $lock;
    $this->assertFalse($view_ui->isLocked());
  }

  /**
   * Tests serialization of the ViewUI object.
   */
  public function testSerialization() {
    // Set a container so the DependencySerializationTrait has it.
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $storage = new View([], 'view');
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->setConstructorArgs([$storage])
      ->getMock();
    $storage->set('executable', $executable);

    $view_ui = new ViewUI($storage);

    // Make sure the executable is returned before serializing.
    $this->assertInstanceOf('Drupal\views\ViewExecutable', $view_ui->getExecutable());

    $serialized = serialize($view_ui);

    // Make sure the ViewExecutable class is not found in the serialized string.
    $this->assertSame(strpos($serialized, '"Drupal\views\ViewExecutable"'), FALSE);

    $unserialized = unserialize($serialized);
    $this->assertInstanceOf('Drupal\views_ui\ViewUI', $unserialized);
  }

}
