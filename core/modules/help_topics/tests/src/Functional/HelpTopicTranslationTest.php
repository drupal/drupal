<?php

namespace Drupal\Tests\help_topics\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies help topic display and user access to help based on permissions.
 *
 * @group help_topics
 */
class HelpTopicTranslationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
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
    \Drupal::service('config.factory')->getEditable('system.theme')->set('admin', 'seven')->save();

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
   * Tests help topic translations.
   */
  public function testHelpTopicTranslations() {
    $session = $this->assertSession();

    // Verify that help topic link is translated on admin/help.
    $this->drupalGet('admin/help');
    $session->linkExists('ABC-Hilfetestmodul');
    // Verify that help topic is translated.
    $this->drupalGet('admin/help/topic/help_topics_test.test');
    $session->pageTextContains('ABC-Hilfetestmodul');
    $session->pageTextContains('Übersetzung testen.');
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

ENDPO;
    include_once $this->root . '/core/includes/install.core.inc';
    $version = _install_get_version_info(\Drupal::VERSION)['major'] . '.0.0';
    file_put_contents($this->publicFilesDirectory . "/translations/drupal-{$version}.de.po", $contents);
    return $parameters;
  }

}
