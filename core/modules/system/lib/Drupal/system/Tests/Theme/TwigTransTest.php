<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigTransTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Twig "trans" tags.
 */
class TwigTransTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'theme_test',
    'twig_theme_test',
    'locale',
    'language'
  );

  /**
   * An administrative user for testing.
   *
   * @var \Drupal\user\Plugin\Core\Entity\User
   */
  protected $admin_user;

  /**
   * Custom language code.
   *
   * @var string
   */
  protected $langcode = 'xx';

  /**
   * Custom language name.
   *
   * @var string
   */
  protected $name = 'Lolspeak';

  /**
   * Defines information about this test.
   *
   * @return array
   *   An associative array of information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Twig Translation',
      'description' => 'Test Twig "trans" tags.',
      'group' => 'Theme',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup test_theme.
    theme_enable(array('test_theme'));
    \Drupal::config('system.theme')->set('default', 'test_theme')->save();

    // Create and log in as admin.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer languages',
      'access administration pages',
      'administer site configuration',
      'translate interface'
    ));
    $this->drupalLogin($this->admin_user);

    // Add test language for translation testing.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $this->langcode,
      'name' => $this->name,
      'direction' => '0',
    );

    // Install the lolspeak language.
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw('"edit-languages-' . $this->langcode . '-weight"', 'Language code found.');

    // Import a custom .po file for the lolspeak language.
    $this->importPoFile($this->examplePoFile(), array(
      'langcode' => $this->langcode,
      'customized' => TRUE,
    ));

    // Assign lolspeak to be the default language.
    $edit = array('site_default_language' => $this->langcode);
    $this->drupalPost('admin/config/regional/settings', $edit, t('Save configuration'));

    // Reset the static cache of the language list.
    drupal_static_reset('language_list');

    // Check that lolspeak is the default language for the site.
    $this->assertEqual(language_default()->id, $this->langcode, $this->name . ' is the default language');
  }

  /**
   * Test Twig "trans" tags.
   */
  public function testTwigTransTags() {
    $this->drupalGet('twig-theme-test/trans', array('language' => language_load('xx')));

    $this->assertText(
      'OH HAI SUNZ',
      '{% trans "Hello sun." %} was successfully translated.'
    );

    $this->assertText(
      'O HERRO ERRRF.',
      '{{ "Hello Earth."|trans }} was successfully translated.'
    );

    $this->assertText(
      'OH HAI TEH MUUN',
      '{% trans %}Hello moon.{% endtrans %} was successfully translated.'
    );

    $this->assertText(
      'O HAI STARRRRR',
      '{% trans %} with {% plural count = 1 %} was successfully translated.'
    );

    $this->assertText(
      'O HAI 2 STARZZZZ',
      '{% trans %} with {% plural count = 2 %} was successfully translated.'
    );

    $this->assertRaw(
      'ESCAPEE: &amp;&quot;&lt;&gt;',
      '{{ token }} was successfully translated and prefixed with "@".'
    );

    $this->assertRaw(
      'PAS-THRU: &"<>',
      '{{ token|passthrough }} was successfully translated and prefixed with "!".'
    );

    $this->assertRaw(
      'PLAYSHOLDR: <em class="placeholder">&amp;&quot;&lt;&gt;</em>',
      '{{ token|placeholder }} was successfully translated and prefixed with "%".'
    );

    $this->assertRaw(
      'DIS complex token HAZ LENGTH OV: 3. IT CONTAYNZ: <em class="placeholder">12345</em> AN &amp;&quot;&lt;&gt;. LETS PAS TEH BAD TEXT THRU: &"<>.',
      '{{ complex.tokens }} were successfully translated with appropriate prefixes.'
    );

    // Ensure debug output does not print.
    $this->checkForDebugMarkup(FALSE);
  }

  /**
   * Test Twig "trans" debug markup.
   */
  public function testTwigTransDebug() {
    // Enable twig debug and write to the test settings.php file.
    $this->settingsSet('twig_debug', TRUE);
    $settings['settings']['twig_debug'] = (object) array(
      'value' => TRUE,
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    // Get page for assertion testing.
    $this->drupalGet('twig-theme-test/trans', array('language' => language_load('xx')));

    // Ensure debug output is printed.
    $this->checkForDebugMarkup(TRUE);
  }

  /**
   * Helper function: test twig debug translation markup.
   *
   * @param bool $visible
   *   Toggle determining which assertion to use for test.
   */
  protected function checkForDebugMarkup($visible) {
    $tests = array(
      '{% trans "Hello sun." %}' => '<!-- TRANSLATION: "Hello sun." -->',
      '{{ "Hello moon."|trans }}' => '<!-- TRANSLATION: "Hello moon." -->',
      '{% trans %} with {% plural %}' => '<!-- TRANSLATION: "Hello star.", PLURAL: "Hello @count stars." -->',
      '{{ token }}' => '<!-- TRANSLATION: "Escaped: @string" -->',
      '{{ token|passthrough }}' => '<!-- TRANSLATION: "Pass-through: !string" -->',
      '{{ token|placeholder }}' => '<!-- TRANSLATION: "Placeholder: %string" -->',
      '{{ complex.tokens }}' => '<!-- TRANSLATION: "This @name has a length of: @count. It contains: %numbers and @bad_text. Lets pass the bad text through: !bad_text." -->',
    );
    foreach ($tests as $test => $markup) {
      if ($visible) {
        $this->assertRaw($markup, "Twig debug translation markup exists in source for: $test");
      }
      else {
        $this->assertNoRaw($markup, "Twig debug translation markup does not exist in source for: $test");
      }
    }
  }

  /**
   * Helper function: import a standalone .po file in a given language.
   *
   * Borrowed from \Drupal\locale\Tests\LocaleImportFunctionalTest.
   *
   * @param string $contents
   *   Contents of the .po file to import.
   * @param array $options
   *   Additional options to pass to the translation import form.
   */
  protected function importPoFile($contents, array $options = array()) {
    $name = tempnam('temporary://', "po_") . '.po';
    file_put_contents($name, $contents);
    $options['files[file]'] = $name;
    $this->drupalPost('admin/config/regional/translate/import', $options, t('Import'));
    drupal_unlink($name);
  }

  /**
   * An example .po file.
   *
   * @return string
   *   The .po contents used for this test.
   */
  protected function examplePoFile() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "Hello sun."
msgstr "OH HAI SUNZ"

msgid "Hello Earth."
msgstr "O HERRO ERRRF."

msgid "Hello moon."
msgstr "OH HAI TEH MUUN"

msgid "Hello star."
msgid_plural "Hello @count stars."
msgstr[0] "O HAI STARRRRR"
msgstr[1] "O HAI @count STARZZZZ"

msgid "Escaped: @string"
msgstr "ESCAPEE: @string"

msgid "Pass-through: !string"
msgstr "PAS-THRU: !string"

msgid "Placeholder: %string"
msgstr "PLAYSHOLDR: %string"

msgid "This @name has a length of: @count. It contains: %numbers and @bad_text. Lets pass the bad text through: !bad_text."
msgstr "DIS @name HAZ LENGTH OV: @count. IT CONTAYNZ: %numbers AN @bad_text. LETS PAS TEH BAD TEXT THRU: !bad_text."
EOF;
  }

}
