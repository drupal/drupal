<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Core\Form\FormState;
use Drupal\element_info_test\Element\DeprecatedExtendsFormElement;
use Drupal\element_info_test\Element\DeprecatedExtendsRenderElement;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Render
 */
class DeprecatedElementTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['element_info_test'];

  /**
   * Tests that render elements can trigger deprecations in their constructor.
   */
  public function testBuildInfo(): void {
    $info_manager = $this->container->get('plugin.manager.element_info');
    $element_names = [
      'deprecated',
      'deprecated_extends_render',
    ];
    foreach ($element_names as $element_name) {
      $this->assertSame([
        '#type' => $element_name,
        '#defaults_loaded' => TRUE,
      ], $info_manager->getInfo($element_name));
    }

    $this->assertSame([
      '#input' => TRUE,
      '#value_callback' => [DeprecatedExtendsFormElement::class, 'valueCallback'],
      '#type' => 'deprecated_extends_form',
      '#defaults_loaded' => TRUE,
    ], $info_manager->getInfo('deprecated_extends_form'));

    // Ensure the constructor is triggering a deprecation error.
    $previous_error_handler = set_error_handler(function ($severity, $message, $file, $line) use (&$previous_error_handler) {
      // Convert deprecation error into a catchable exception.
      if ($severity === E_USER_DEPRECATED) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
      }
      if ($previous_error_handler) {
        return $previous_error_handler($severity, $message, $file, $line);
      }
    });

    try {
      $info_manager->createInstance('deprecated');
      $this->fail('No deprecation error triggered.');
    }
    catch (\ErrorException $e) {
      $this->assertSame('Drupal\element_info_test\Element\Deprecated is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3068104', $e->getMessage());
    }

    try {
      $info_manager->createInstance('deprecated_extends_form');
      $this->fail('No deprecation error triggered.');
    }
    catch (\ErrorException $e) {
      $this->assertSame('\Drupal\Core\Render\Element\FormElement is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase instead. See https://www.drupal.org/node/3436275', $e->getMessage());
    }

    try {
      $info_manager->createInstance('deprecated_extends_render');
      $this->fail('No deprecation error triggered.');
    }
    catch (\ErrorException $e) {
      $this->assertSame('\Drupal\Core\Render\Element\RenderElement is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\RenderElementBase instead. See https://www.drupal.org/node/3436275', $e->getMessage());
    }

    restore_error_handler();
  }

  /**
   * Test use of static methods trigger deprecations.
   */
  public function testDeprecatedStaticMethods(): void {
    $previous_error_handler = set_error_handler(function ($severity, $message, $file, $line) use (&$previous_error_handler) {
      // Convert deprecation error into a catchable exception.
      if ($severity === E_USER_DEPRECATED) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
      }
      if ($previous_error_handler) {
        return $previous_error_handler($severity, $message, $file, $line);
      }
    });

    $element = [];
    $form_state = new FormState();
    $complete_form = [];
    $static_methods_render = [
      'setAttributes' => [$element],
      'preRenderGroup' => [$element],
      'processAjaxForm' => [$element, $form_state, $complete_form],
      'preRenderAjaxForm' => [$element],
      'processGroup' => [$element, $form_state, $complete_form],
    ];

    $static_methods_form = [
      'valueCallback' => [$element, FALSE, $form_state],
      'processPattern' => [$element, $form_state, $complete_form],
      'validatePattern' => [$element, $form_state, $complete_form],
      'processAutocomplete' => [$element, $form_state, $complete_form],
    ];

    $class_names = [
      DeprecatedExtendsRenderElement::class,
      DeprecatedExtendsFormElement::class,
    ];
    foreach ($class_names as $class_name) {
      foreach ($static_methods_render as $method_name => $arguments) {
        try {
          $class_name::$method_name(...$arguments);
          $this->fail('No deprecation error triggered.');
        }
        catch (\ErrorException $e) {
          $parent_class = get_parent_class($class_name);
          $this->assertSame("\\{$parent_class}::{$method_name}() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \\{$parent_class}Base::{$method_name}() instead. See https://www.drupal.org/node/3436275", $e->getMessage());
        }
      }
    }
    foreach ($static_methods_form as $method_name => $arguments) {
      try {
        DeprecatedExtendsFormElement::$method_name(...$arguments);
        $this->fail('No deprecation error triggered.');
      }
      catch (\ErrorException $e) {
        $this->assertSame("\\Drupal\\Core\\Render\\Element\\FormElement::{$method_name}() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \\Drupal\\Core\\Render\\Element\\FormElementBase::{$method_name}() instead. See https://www.drupal.org/node/3436275", $e->getMessage());
      }
    }

    restore_error_handler();
  }

}
