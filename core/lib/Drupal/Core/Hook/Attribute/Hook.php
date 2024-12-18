<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Attribute for defining a class method as a hook implementation.
 *
 * Hook implementations in classes need to be marked with this attribute,
 * using one of the following techniques:
 * - On a method, use this attribute with the hook name:
 *   @code
 *   #[Hook('user_cancel')]
 *   public method userCancel(...)
 *   @endcode
 * - On a class, specifying the method name:
 *   @code
 *   #[Hook('user_cancel', method: 'userCancel')]
 *   class Hooks {
 *     method userCancel(...) {}
 *   }
 *   @endcode
 * - On a class with an __invoke method, which is taken to be the hook
 *   implementation:
 *   @code
 *   #[Hook('user_cancel')]
 *   class Hooks {
 *     method __invoke(...) {}
 *   }
 *   @endcode
 *
 * Ordering hook implementations can be done by implementing
 * hook_module_implements_alter.
 *
 * Classes that use this annotation on the class or on their methods are
 * automatically registered as autowired services with the class name as the
 * service ID. If autowire does not suffice, they can be registered manually as
 * well.
 *
 * Implementing a hook on behalf of another module can be done by by specifying
 * the 'module' parameter in the attribute.
 *
 * @section sec_multiple_implementations Multiple implementations
 *
 * Multiple implementations are allowed on multiple axes:
 * - One method can implement multiple hooks by adding a Hook attribute for each
 *   method.
 * - One module can implement a particular hook multiple times in multiple
 *   classes, although see below for some exceptions. This allows, for example,
 *   adding hook_form_alter() implementations firing on other conditions than
 *   form ID without modifying any existing implementations.
 *
 * The following hooks may not have multiple implementations by a single module:
 * - hook_library_info_build()
 * - hook_mail()
 * - hook_help()
 * - hook_node_update_index()
 *
 * @section sec_procedural Procedural hooks
 *
 * The following hooks can only have procedural hook implementations:
 *
 * Legacy meta hooks:
 * - hook_hook_info()
 * - hook_module_implements_alter()
 *
 * Install hooks:
 * - hook_install()
 * - hook_install_tasks()
 * - hook_install_tasks_alter()
 * - hook_post_update_NAME()
 * - hook_schema()
 * - hook_uninstall()
 * - hook_update_last_removed()
 * - hook_update_N()
 *
 * Theme hooks:
 * - hook_preprocess_HOOK()
 *
 * @section sec_backwards_compatibility Backwards-compatibility
 *
 * To allow hook implementations to work on older versions of Drupal as well,
 * add both an attribute-based hook implementation and a procedural hook
 * implementation, with the \Drupal\Core\Hook\Attribute\LegacyHook attribute on
 * the procedural hook implementations.
 *
 * See \Drupal\Core\Hook\Attribute\LegacyHook for additional information.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Hook {

  /**
   * Constructs a Hook attribute object.
   *
   * @param string $hook
   *   The short hook name, without the 'hook_' prefix.
   * @param string $method
   *   (optional) The method name. If this attribute is on a method, this
   *   parameter is not required. If this attribute is on a class and this
   *   parameter is omitted, the class must have an __invoke() method, which is
   *   taken as the hook implementation.
   * @param string|null $module
   *   (optional) The module this implementation is for. This allows one module to
   *   implement a hook on behalf of another module. Defaults to the module the
   *   implementation is in.
   */
  public function __construct(
    public string $hook,
    public string $method = '',
    public ?string $module = NULL,
  ) {}

  /**
   * Set the method the hook should apply to.
   *
   * @param string $method
   *   The method that the hook attribute applies to.
   *   This only needs to be set when the attribute is on the class.
   */
  public function setMethod(string $method): static {
    $this->method = $method;
    return $this;
  }

}
