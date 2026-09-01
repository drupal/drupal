<?php

declare(strict_types=1);

namespace Drupal\Tests\Traits\Core\Locale;

/**
 * A trait to install Drupal in a specific language.
 */
trait InstallerLanguageTrait {

  /**
   * Gets the langcode to install Drupal with.
   *
   * @return string
   *   The langcode to install Drupal with.
   */
  abstract protected function getInstallLangcode(): string;

  /**
   * Returns the contents of the translation file.
   *
   * This method should be overridden by the test class to provide a translation
   * file that will be used during the installation.
   *
   * @return string
   *   The contents of the translation file.
   */
  protected function getTranslationFileContents(): string {
    return <<<PO
msgid ""
msgstr ""

PO;
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters(): array {
    $parameters = parent::installParameters();
    $parameters['parameters']['langcode'] = $this->getInstallLangcode();
    // Create a po file so we don't attempt to download one from
    // localize.drupal.org and to have a test translation that will not change.
    \Drupal::service('file_system')->mkdir($this->publicFilesDirectory . '/translations', NULL, TRUE);
    file_put_contents(
      $this->publicFilesDirectory . '/translations/drupal-' . \Drupal::VERSION . '.' . $this->getInstallLangcode() . '.po',
      $this->getTranslationFileContents()
    );
    return $parameters;
  }

}
