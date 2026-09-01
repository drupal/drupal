<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\Locale\InstallerLanguageTrait;

// cspell:ignore hilfetestmodul übersetzung

/**
 * Provides a base class for functional help topic tests that use translation.
 *
 * Installs in German, with a small PO file, and sets up the task, help, and
 * page title blocks.
 */
abstract class HelpTopicTranslatedTestBase extends BrowserTestBase {
  use InstallerLanguageTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'help_topics_test',
    'help',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // These tests rely on some markup from the 'Claro' theme, as well as an
    // optional block added when Claro is enabled.
    \Drupal::service('theme_installer')->install(['claro']);
    \Drupal::configFactory()->getEditable('system.theme')
      ->set('admin', 'claro')
      ->save();

    // Place various blocks.
    $settings = [
      'theme' => 'claro',
      'region' => 'help',
    ];
    $this->placeBlock('help_block', $settings);
    $this->placeBlock('local_tasks_block', $settings);
    $this->placeBlock('local_actions_block', $settings);
    $this->placeBlock('page_title_block', $settings);

    // Create user.
    $this->drupalLogin($this->createUser([
      'access help pages',
      'view the administration theme',
      'administer permissions',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function getInstallLangcode(): string {
    return 'de';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationFileContents(): string {
    return <<<PO
msgid ""
msgstr ""

msgid "ABC Help Test module"
msgstr "ABC-Hilfetestmodul"

msgid "Test translation."
msgstr "Übersetzung testen."

msgid "Non-word-item to translate."
msgstr "Non-word-german sdfwedrsdf."

PO;
  }

}
