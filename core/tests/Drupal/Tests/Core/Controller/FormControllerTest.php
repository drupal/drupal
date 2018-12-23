<?php

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Controller\ControllerResolver;
use Drupal\Core\Controller\HtmlFormController;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FormController class.
 *
 * @group Controller
 */
class FormControllerTest extends UnitTestCase {

  /**
   * @expectedDeprecation Using the 'controller_resolver' service as the first argument is deprecated, use the 'http_kernel.controller.argument_resolver' instead. If your subclass requires the 'controller_resolver' service add it as an additional argument. See https://www.drupal.org/node/2959408.
   * @group legacy
   */
  public function testControllerResolverDeprecation() {
    if (!in_array('Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface', class_implements('Symfony\Component\HttpKernel\Controller\ControllerResolver'))) {
      $this->markTestSkipped("Do not test ::getArguments() method when it is not implemented by Symfony's ControllerResolver.");
    }
    $controller_resolver = $this->getMockBuilder(ControllerResolver::class)->disableOriginalConstructor()->getMock();
    $form_builder = $this->getMockBuilder(FormBuilderInterface::class)->getMock();
    $class_resolver = $this->getMockBuilder(ClassResolverInterface::class)->getMock();
    // Use the HtmlFormController as FormController is abstract.
    new HtmlFormController($controller_resolver, $form_builder, $class_resolver);
  }

}
