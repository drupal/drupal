<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;
use Twig\Error\SyntaxError;

/**
 * Tests Twig "trans" tags.
 *
 * @group Theme
 */
class TwigTransTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'theme_test',
    'twig_theme_test',
    'locale',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An administrative user for testing.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Custom languages.
   *
   * @var array
   */
  protected $languages = [
    'xx' => 'Lolspeak',
    'zz' => 'Lolspeak2',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup test_theme.
    \Drupal::service('theme_installer')->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();

    // Create and log in as admin.
    $this->adminUser = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'administer site configuration',
      'translate interface',
    ]);
    $this->drupalLogin($this->adminUser);

    // Install languages.
    $this->installLanguages();

    // Assign Lolspeak (xx) to be the default language.
    $this->config('system.site')->set('default_langcode', 'xx')->save();
    $this->rebuildContainer();

    // Check that lolspeak is the default language for the site.
    $this->assertEquals('xx', \Drupal::languageManager()->getDefaultLanguage()->getId(), 'Lolspeak is the default language');
  }

  /**
   * Tests Twig "trans" tags.
   */
  public function testTwigTransTags() {
    // Run this once without and once with Twig debug because trans can work
    // differently depending on that setting.
    $this->drupalGet('twig-theme-test/trans', ['language' => \Drupal::languageManager()->getLanguage('xx')]);
    $this->assertTwigTransTags();

    // Enable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();

    $this->drupalGet('twig-theme-test/trans', ['language' => \Drupal::languageManager()->getLanguage('xx')]);
    $this->assertTwigTransTags();
  }

  /**
   * Tests empty Twig "trans" tags.
   */
  public function testEmptyTwigTransTags() {
    $elements = [
      '#type' => 'inline_template',
      '#template' => '{% trans %}{% endtrans %}',
    ];
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    try {
      $renderer->renderPlain($elements);

      $this->fail('{% trans %}{% endtrans %} did not throw an exception.');
    }
    catch (SyntaxError $e) {
      $this->assertStringContainsString('{% trans %} tag cannot be empty', $e->getMessage());
    }
    catch (\Exception $e) {
      $this->fail('{% trans %}{% endtrans %} threw an unexpected exception.');
    }
  }

  /**
   * Asserts Twig trans tags.
   */
  protected function assertTwigTransTags() {
    // Assert that {% trans "Hello sun." %} is translated correctly.
    $this->assertSession()->pageTextContains('OH HAI SUNZ');

    // Assert that {% trans "Hello sun." %} with {"context": "Lolspeak"} is
    // translated correctly.
    $this->assertSession()->pageTextContains('O HAI SUNZZZZZZZ');

    // Assert that {{ "Hello Earth."|trans }} is translated correctly.
    $this->assertSession()->pageTextContains('O HERRO ERRRF.');

    // Assert that {% trans %}Hello moon.{% endtrans %} is translated correctly.
    $this->assertSession()->pageTextContains('OH HAI TEH MUUN');

    // Assert that {% trans %} with {% plural count = 1 %} is translated
    // correctly.
    $this->assertSession()->pageTextContains('O HAI STARRRRR');

    // Assert that {% trans %} with {% plural count = 2 %} is translated
    // correctly.
    $this->assertSession()->pageTextContains('O HAI 2 STARZZZZ');

    // Assert that {{ token }} was successfully translated and prefixed
    // with "@".
    $this->assertSession()->responseContains('ESCAPEE: &amp;&quot;&lt;&gt;');

    // Assert that {{ token|placeholder }} was successfully translated and
    // prefixed with "%".
    $this->assertSession()->responseContains('PLAYSHOLDR: <em class="placeholder">&amp;&quot;&lt;&gt;</em>');

    // Assert that {{ complex.tokens }} were successfully translated with
    // appropriate prefixes.
    $this->assertSession()->responseContains('DIS complex token HAZ LENGTH OV: 3. IT CONTAYNZ: <em class="placeholder">12345</em> AN &amp;&quot;&lt;&gt;.');

    // Assert that {% trans %} with a context only msgid is excluded from
    // translation.
    $this->assertSession()->pageTextContains('I have context.');

    // Assert that {% trans with {"context": "Lolspeak"} %} was successfully
    // translated with context.
    $this->assertSession()->pageTextContains('I HAZ KONTEX.');

    // Assert that {% trans with {"langcode": "zz"} %} is successfully
    // translated in specified language.
    $this->assertSession()->pageTextContains('O HAI NU TXT.');

    // Assert that {% trans with {"context": "Lolspeak", "langcode": "zz"} %}
    // is successfully translated with context in specified language.
    $this->assertSession()->pageTextContains('O HAI NU TXTZZZZ.');

    // Makes sure https://www.drupal.org/node/2489024 doesn't happen without
    // twig debug.
    // Ensure that running php code inside a Twig trans is not possible.
    $this->assertSession()->pageTextNotContains(pi());
  }

  /**
   * Helper function: install languages.
   */
  protected function installLanguages() {
    $file_system = \Drupal::service('file_system');
    foreach ($this->languages as $langcode => $name) {
      // Generate custom .po contents for the language.
      $contents = $this->poFileContents($langcode);
      if ($contents) {
        // Add test language for translation testing.
        $edit = [
          'predefined_langcode' => 'custom',
          'langcode' => $langcode,
          'label' => $name,
          'direction' => LanguageInterface::DIRECTION_LTR,
        ];

        // Install the language in Drupal.
        $this->drupalGet('admin/config/regional/language/add');
        $this->submitForm($edit, 'Add custom language');
        $this->assertSession()->responseContains('"edit-languages-' . $langcode . '-weight"');

        // Import the custom .po contents for the language.
        $filename = $file_system->tempnam('temporary://', "po_") . '.po';
        file_put_contents($filename, $contents);
        $options = [
          'files[file]' => $filename,
          'langcode' => $langcode,
          'customized' => TRUE,
        ];
        $this->drupalGet('admin/config/regional/translate/import');
        $this->submitForm($options, 'Import');
        $file_system->unlink($filename);
      }
    }
    $this->container->get('language_manager')->reset();
  }

  /**
   * Generate a custom .po file for a specific test language.
   *
   * @param string $langcode
   *   The langcode of the specified language.
   *
   * @return string|false
   *   The .po contents for the specified language or FALSE if none exists.
   */
  protected function poFileContents($langcode) {
    if ($langcode === 'xx') {
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

msgctxt "Lolspeak"
msgid "Hello sun."
msgstr "O HAI SUNZZZZZZZ"

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

msgid "Placeholder: %string"
msgstr "PLAYSHOLDR: %string"

msgid "This @token.name has a length of: @count. It contains: %token.numbers and @token.bad_text."
msgstr "DIS @token.name HAZ LENGTH OV: @count. IT CONTAYNZ: %token.numbers AN @token.bad_text."

msgctxt "Lolspeak"
msgid "I have context."
msgstr "I HAZ KONTEX."
EOF;
    }
    elseif ($langcode === 'zz') {
      return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "Hello new text."
msgstr "O HAI NU TXT."

msgctxt "Lolspeak"
msgid "Hello new text."
msgstr "O HAI NU TXTZZZZ."
EOF;
    }
    return FALSE;
  }

}
