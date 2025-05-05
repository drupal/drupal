<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * This class will not have an effect until Drupal 11.1.0.
 *
 * This class is included in earlier Drupal versions to prevent phpstan errors
 * for modules implementing object oriented hooks using the #Hook and
 * #LegacyHook attributes.
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
   * @param int|null $priority
   *   (optional) The priority of this implementation relative to other
   *   implementations of this hook. Hook implementations with higher priority
   *   are executed first. If omitted, the module order is used to order the
   *   hook implementations.
   * @param string|null $module
   *   (optional) The module this implementation is for. This allows one module to
   *   implement a hook on behalf of another module. Defaults to the module the
   *   implementation is in.
   */
  public function __construct(
    public string $hook,
    public string $method = '',
    public ?int $priority = NULL,
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
