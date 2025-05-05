<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

use Drupal\Core\Hook\Order\OrderInterface;

/**
 * This class will not have an effect until Drupal 11.1.0.
 *
 * This class is included in earlier Drupal versions to prevent phpstan errors
 * for modules implementing object oriented hooks using the #Hook and
 * #LegacyHook attributes.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Hook implements HookAttributeInterface {
  /**
   * The hook prefix such as `form`.
   *
   * @var string
   */
  public const string PREFIX = '';

  /**
   * The hook suffix such as `alter`.
   *
   * @var string
   */
  public const string SUFFIX = '';

  /**
   * Constructs a Hook attribute object.
   *
   * @param string $hook
   *   The short hook name, without the 'hook_' prefix.
   *   $hook is only optional when Hook is extended and a PREFIX or SUFFIX is
   *   defined. When using the [#Hook] attribute directly $hook is required.
   *   See Drupal\Core\Hook\Attribute\Preprocess.
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
   *   (optional) The module this implementation is for. This allows one module
   *   to implement a hook on behalf of another module. Defaults to the module
   *   the implementation is in.
   * @param \Drupal\Core\Hook\Order\OrderInterface|null $order
   *   (optional) Set the order of the implementation. This parameter is
   *   supported in Drupal 11.2 and greater. It will have no affect in Drupal
   *   11.1.
   */
  public function __construct(
    public string $hook = '',
    public string $method = '',
    public ?int $priority = NULL,
    public ?string $module = NULL,
    public OrderInterface|null $order = NULL,
  ) {
    $this->hook = implode('_', array_filter([static::PREFIX, $hook, static::SUFFIX]));
    if ($this->hook === '') {
      throw new \LogicException('The Hook attribute or an attribute extending the Hook attribute must provide the $hook parameter, a PREFIX or a SUFFIX.');
    }
  }

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
