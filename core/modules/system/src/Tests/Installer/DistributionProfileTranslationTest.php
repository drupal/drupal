<?php

namespace Drupal\system\Tests\Installer;

use Drupal\Core\Serialization\Yaml;
use Drupal\simpletest\InstallerTestBase;

/**
 * Tests distribution profile support.
 *
 * @group Installer
 *
 * @see \Drupal\system\Tests\Installer\DistributionProfileTest
 */
class DistributionProfileTranslationTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $langcode = 'de';

  /**
   * The distribution profile info.
   *
   * @var array
   */
  protected $info;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->info = [
      'type' => 'profile',
      'core' => \Drupal::CORE_COMPATIBILITY,
      'name' => 'Distribution profile',
      'distribution' => [
        'name' => 'My Distribution',
        'langcode' => $this->langcode,
        'install' => [
          'theme' => 'bartik',
        ],
      ],
    ];
    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/mydistro';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/mydistro.info.yml", Yaml::encode($this->info));

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller() {
    // Place a custom local translation in the translations directory.
    mkdir(\Drupal::root() . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(\Drupal::root() . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', $this->getPo('de'));

    parent::visitInstaller();

    // The language should have been automatically detected, all following
    // screens should be translated already.
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $this->assertEqual((string) current($elements), 'Save and continue de');
    $this->translations['Save and continue'] = 'Save and continue de';

    // Check the language direction.
    $direction = (string) current($this->xpath('/html/@dir'));
    $this->assertEqual($direction, 'ltr');

    // Verify that the distribution name appears.
    $this->assertRaw($this->info['distribution']['name']);
    // Verify that the requested theme is used.
    $this->assertRaw($this->info['distribution']['install']['theme']);
    // Verify that the "Choose profile" step does not appear.
    $this->assertNoText('profile');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // This step is skipped, because the distribution profile uses a fixed
    // language.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile() {
    // This step is skipped, because there is a distribution profile.
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);

    // Confirm that we are logged-in after installation.
    $this->assertText($this->rootUser->getDisplayName());

    // Verify German was configured but not English.
    $this->drupalGet('admin/config/regional/language');
    $this->assertText('German');
    $this->assertNoText('English');
  }

  /**
   * Returns the string for the test .po file.
   *
   * @param string $langcode
   *   The language code.
   * @return string
   *   Contents for the test .po file.
   */
  protected function getPo($langcode) {
    return <<<ENDPO
msgid ""
msgstr ""

msgid "Save and continue"
msgstr "Save and continue $langcode"
ENDPO;
  }

}
