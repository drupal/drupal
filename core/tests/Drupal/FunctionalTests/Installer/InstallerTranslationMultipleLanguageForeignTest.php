<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests translation files for multiple languages get imported during install.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class InstallerTranslationMultipleLanguageForeignTest extends InstallerTranslationMultipleLanguageTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Overrides the language code in which to install Drupal.
   *
   * @var string
   */
  protected $langcode = 'de';

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage(): void {
    parent::setUpLanguage();
    $this->translations['Save and continue'] = 'Save and continue de';
  }

}
