<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Defines a ProceduralHookScanStop attribute object.
 *
 * This allows contrib and core to mark when a file has no more
 * procedural hooks to be gathered. Any procedural hooks in the file should
 * be placed before the function with this attribute. This includes all hooks
 * that can be converted to object oriented hooks and also includes:
 * - hook_hook_info()
 * - hook_module_implements_alter()
 * - hook_requirements()
 * - hook_preprocess()
 * - hook_preprocess_HOOK()
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class ProceduralHookScanStop {

}
