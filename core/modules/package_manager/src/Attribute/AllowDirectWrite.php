<?php

declare(strict_types=1);

namespace Drupal\package_manager\Attribute;

/**
 * Identifies sandbox managers which can operate on the running code base.
 *
 * Package Manager normally creates and operates on a fully separate, sandboxed
 * copy of the site. This is pretty safe, but not always necessary for certain
 * kinds of operations (e.g., adding a new module to the site).
 * SandboxManagerBase subclasses with this attribute are allowed to skip the
 * sandboxing and operate directly on the live site, but ONLY if the
 * `package_manager_allow_direct_write` setting is set to TRUE.
 *
 * @see \Drupal\package_manager\SandboxManagerBase::isDirectWrite()
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AllowDirectWrite {
}
