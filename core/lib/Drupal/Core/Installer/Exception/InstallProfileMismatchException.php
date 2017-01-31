<?php

namespace Drupal\Core\Installer\Exception;

use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Exception thrown if settings.php cannot be written and the chosen profile does not match.
 */
class InstallProfileMismatchException extends InstallerException {

  /**
   * Constructs a new InstallProfileMismatchException exception.
   *
   * @param string $selected_profile
   *   The profile selected by _install_select_profile().
   * @param string $settings_profile
   *   The profile in settings.php.
   * @param string $settings_file
   *   The path to settigns.php.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation manager.
   *
   * @deprecated in Drupal 8.3.0 and will be removed before Drupal 9.0.0. The
   *    install profile is written to core.extension.
   *
   * @see _install_select_profile()
   * @see install_write_profile
   */
  public function __construct($selected_profile, $settings_profile, $settings_file, TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;

    $title = $this->t('Install profile mismatch');
    $message = $this->t(
      'The selected profile %profile does not match the install_profile setting, which is %settings_profile. Cannot write updated setting to %settings_file.',
      [
        '%profile' => $selected_profile,
        '%settings_profile' => $settings_profile,
        '%settings_file' => $settings_file,
      ]
    );
    parent::__construct($message, $title);
  }

}
