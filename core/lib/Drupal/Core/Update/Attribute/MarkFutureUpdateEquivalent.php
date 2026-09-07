<?php

declare(strict_types=1);

namespace Drupal\Core\Update\Attribute;

/**
 * Attribute used by hook_update_N() to register an equivalent update.
 *
 * Updates can be registered as equivalent when they are backported to a
 * previous, but still supported, major version. Update functions using this
 * attribute will register the update as equivalent to the given future update.
 * The future update is specified by future hook_update_N number and the future
 * module version. The update itself can be no-op.
 *
 * By itself, hook_update_N() hooks do not run on module install. However, the
 * presence of the attribute on a hook_update_N() will register the equivalent
 * update when the module is installed.
 *
 * Example:
 *
 * @code
 * use Drupal\Core\Update\Attribute\MarkFutureUpdateEquivalent;
 *
 * #[MarkFutureUpdateEquivalent(2005, '2.10')]
 * function MODULE_update_1040(): void {
 * }
 * @endcode
 * Here, database update MODULE_update_2005() is added to version 2.x of MODULE.
 * When that same update is backported to 1.x, it is given its own update
 * number, MODULE_update_1040(). This ensures that a site that has run this
 * MODULE_update_1040() does not also run MODULE_update_2005().
 *
 * @see update_api
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
readonly class MarkFutureUpdateEquivalent {

  public function __construct(
    public int $futureUpdateNumber,
    public string $futureVersionString,
  ) {}

}
