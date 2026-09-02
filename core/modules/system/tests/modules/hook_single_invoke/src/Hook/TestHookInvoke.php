<?php

declare(strict_types=1);

namespace Drupal\hook_single_invoke\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Add four hook implementations for two hooks, one returns arrays one string.
 */
class TestHookInvoke {

  /**
   * This hook returns an array.
   */
  #[Hook('custom_hook_invoke_array')]
  public function hookInvokeSingleArrayOne(): array {
    return [__METHOD__];
  }

  /**
   * This hook returns an array.
   */
  #[Hook('custom_hook_invoke_array')]
  public function hookInvokeSingleArrayTwo(): array {
    return [__METHOD__];
  }

  /**
   * This hook returns an ArrayObject.
   *
   * @return \ArrayObject< int, string >
   *   ArrayObject of the method.
   */
  #[Hook('custom_hook_invoke_array_object')]
  public function hookInvokeSingleArrayObjectOne(): \ArrayObject {
    return new \ArrayObject([__METHOD__]);
  }

  /**
   * This hook returns an ArrayObject.
   *
   * @return \ArrayObject< int, string >
   *   ArrayObject of the method.
   */
  #[Hook('custom_hook_invoke_array_object')]
  public function hookInvokeSingleArrayObjectTwo(): \ArrayObject {
    return new \ArrayObject([__METHOD__]);
  }

  /**
   * This hook returns a string.
   */
  #[Hook('custom_hook_invoke_string')]
  public function hookInvokeSingleStringOne(): string {
    return __METHOD__;
  }

  /**
   * This hook returns a string.
   */
  #[Hook('custom_hook_invoke_string')]
  public function hookInvokeSingleStringTwo(): string {
    return __METHOD__;
  }

  /**
   * This hook returns a stdClass.
   */
  #[Hook('custom_hook_invoke_class')]
  public function hookInvokeSingleClassOne(): \stdClass {
    return new \stdClass();
  }

  /**
   * This hook returns a stdClass.
   */
  #[Hook('custom_hook_invoke_class')]
  public function hookInvokeSingleClassTwo(): \stdClass {
    return new \stdClass();
  }

}
