<?php

namespace Drupal\Tests\help_topics\Functional;

use Drupal\Tests\BrowserTestBase;

// cspell:ignore hilfetestmodul übersetzung

/**
 * Provides a base class for functional help topic tests that use translation.
 *
 * Installs in German, with a small PO file, and sets up the task, help, and
 * page title blocks.
 */
abstract class HelpTopicTranslatedTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'help_topics_test',
    'help',
    'help_topics',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // These tests rely on some markup from the 'Seven' theme.
    \Drupal::service('theme_installer')->install(['seven']);
    \Drupal::configFactory()->getEditable('system.theme')
      ->set('admin', 'seven')
      ->save(TRUE);

    // Place various blocks.
    $settings = [
      'theme' => 'seven',
      'region' => 'help',
    ];
    $this->placeBlock('help_block', $settings);
    $this->placeBlock('local_tasks_block', $settings);
    $this->placeBlock('local_actions_block', $settings);
    $this->placeBlock('page_title_block', $settings);

    // Create user.
    $this->drupalLogin($this->createUser([
      'access administration pages',
      'view the administration theme',
      'administer permissions',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $parameters = parent::installParameters();
    // Install in German. This will ensure the language and locale modules are
    // installed.
    $parameters['parameters']['langcode'] = 'de';
    // Create a po file so we don't attempt to download one from
    // localize.drupal.org and to have a test translation that will not change.
    \Drupal::service('file_system')->mkdir($this->publicFilesDirectory . '/translations', NULL, TRUE);
    $contents = <<<ENDPO
msgid ""
msgstr ""

msgid "ABC Help Test module"
msgstr "ABC-Hilfetestmodul"

msgid "Test translation."
msgstr "Übersetzung testen."

msgid "Non-word-item to translate."
msgstr "Non-word-german sdfwedrsdf."

ENDPO;
    include_once $this->root . '/core/includes/install.core.inc';
    $version = _install_get_version_info(\Drupal::VERSION)['major'] . '.0.0';
    file_put_contents($this->publicFilesDirectory . "/translations/drupal-{$version}.de.po", $contents);
    return $parameters;
  }

}
