<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ViewUIObjectTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewExecutable;
use Drupal\views_ui\ViewUI;
use Symfony\Component\DependencyInjection\Container;

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
    $method_args['setOriginalID'] = array(12);
    $method_args['setStatus'] = array(TRUE);
    $method_args['enforceIsNew'] = array(FALSE);
    $method_args['label'] = array(Language::LANGCODE_NOT_SPECIFIED);

    $reflection = new \ReflectionClass('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $interface_methods = array();
    foreach ($reflection->getMethods() as $reflection_method) {
      $interface_methods[] = $reflection_method->getName();

      // EntityInterface::isNew() is missing from the list of methods, because it
      // calls id(), which breaks the ->expect($this->once()) call. Call it later.
      // EntityInterface::isSyncing() is only called during syncing process.
      if ($reflection_method->getName() != 'isNew' && $reflection_method->getName() != 'isSyncing') {
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

}
