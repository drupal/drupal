<?php

namespace Drupal\Core\Installer\Exception;

use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Exception thrown if no installation profiles are available.
 */
class NoProfilesException extends InstallerException {

  /**
   * Constructs a new "no profiles available" exception.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation manager.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;

    $title = $this->t('No profiles available');
    $message = $this->t('We were unable to find any installation profiles. Installation profiles tell us what modules to install and what schema to install in the database. A profile is necessary to continue with the installation process.');
    parent::__construct($message, $title);
  }

}
