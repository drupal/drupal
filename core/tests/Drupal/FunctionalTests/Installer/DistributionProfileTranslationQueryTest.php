<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Serialization\Yaml;

/**
 * Tests distribution profile support with a 'langcode' query string.
 *
 * @group Installer
 *
 * @see \Drupal\FunctionalTests\Installer\DistributionProfileTranslationTest
 */
class DistributionProfileTranslationQueryTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $this->info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
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
    $path = $this->root . DIRECTORY_SEPARATOR . $this->siteDirectory . '/profiles/mydistro';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/mydistro.info.yml", Yaml::encode($this->info));
    // Place a custom local translation in the translations directory.
    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', $this->getPo('de'));
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.fr.po', $this->getPo('fr'));
  }

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller() {
    // Pass a different language code than the one set in the distribution
    // profile. This distribution language should still be used.
    // The unrouted URL assembler does not exist at this point, so we build the
    // URL ourselves.
    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php' . '?langcode=fr');
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
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // The language should have been automatically detected, all following
    // screens should be translated already.
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $this->assertEqual(current($elements)->getText(), 'Save and continue de');
    $this->translations['Save and continue'] = 'Save and continue de';

    // Check the language direction.
    $direction = $this->getSession()->getPage()->find('xpath', '/@dir')->getText();
    $this->assertEqual($direction, 'ltr');

    // Verify that the distribution name appears.
    $this->assertRaw($this->info['distribution']['name']);
    // Verify that the requested theme is used.
    $this->assertRaw($this->info['distribution']['install']['theme']);
    // Verify that the "Choose profile" step does not appear.
    $this->assertNoText('profile');

    parent::setUpSettings();
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
