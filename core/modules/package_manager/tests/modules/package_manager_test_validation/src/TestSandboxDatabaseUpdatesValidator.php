<?php

declare(strict_types=1);

namespace Drupal\package_manager_test_validation;

use Drupal\package_manager\Validator\SandboxDatabaseUpdatesValidator as BaseValidator;
use Drupal\Core\Extension\Extension;
use Drupal\Core\State\StateInterface;

/**
 * Allows tests to dictate which extensions have staged database updates.
 */
class TestSandboxDatabaseUpdatesValidator extends BaseValidator {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * Sets the state service dependency.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function setState(StateInterface $state): void {
    $this->state = $state;
  }

  /**
   * Sets the names of the extensions which should have staged database updates.
   *
   * @param string[]|null $extensions
   *   The machine names of the extensions which should say they have staged
   *   database updates, or NULL to defer to the parent class.
   */
  public static function setExtensionsWithUpdates(?array $extensions): void {
    \Drupal::state()->set(static::class, $extensions);
  }

  /**
   * {@inheritdoc}
   */
  public function hasStagedUpdates(string $stage_dir, Extension $extension): bool {
    $extensions = $this->state->get(static::class);
    if (isset($extensions)) {
      return in_array($extension->getName(), $extensions, TRUE);
    }
    return parent::hasStagedUpdates($stage_dir, $extension);
  }

}
