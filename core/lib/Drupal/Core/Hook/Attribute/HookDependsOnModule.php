<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Makes hook registration dependent on a module being installed.
 *
 * If this attribute is added to a hook class or method, that class or method
 * will be skipped from registration when the module is not installed.
 *
 * Any module can be set; it does not need to be the module that invokes the
 * hook.
 *
 * Apart from performance benefits, when this attribute is applied to a class,
 * services provided by the module dependency can be injected as required
 * properties.
 *
 * @see \Drupal\node\Hook\NodeSearchHooks
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class HookDependsOnModule implements HookAttributeInterface {

  /**
   * Constructs HookDependsOnModule attribute object.
   *
   * @param string $module
   *   The name of the module that the hook depends on.
   */
  public function __construct(
    public readonly string $module,
  ) {

  }

}
