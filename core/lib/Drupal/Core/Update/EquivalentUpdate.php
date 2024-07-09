<?php

namespace Drupal\Core\Update;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Value object to hold information about an equivalent update.
 *
 * @see module.api.php
 */
final class EquivalentUpdate {

  /**
   * Constructs a EquivalentUpdate object.
   *
   * @param string $module
   *   The module the update is for.
   * @param int $future_update
   *   The equivalent future update.
   * @param int $ran_update
   *   The update that already ran and registered the equivalent update.
   * @param string $future_version
   *   The future version that has the expected update.
   */
  public function __construct(
    public readonly string $module,
    public readonly int $future_update,
    public readonly int $ran_update,
    public readonly string $future_version,
  ) {
  }

  /**
   * Creates a message to explain why an update has been skipped.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An message explaining why an update has been skipped.
   */
  public function toSkipMessage(): TranslatableMarkup {
    return new TranslatableMarkup(
      'Update @number for the @module module has been skipped because the equivalent change was already made in update @ran_update.',
      ['@number' => $this->future_update, '@module' => $this->module, '@ran_update' => $this->ran_update]
    );
  }

}
