<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\MachineName;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the machine name transliteration functionality.
 *
 * @group javascript
 * @group #slow
 */
class MachineNameTransliterationTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer languages',
      'access administration pages',
      'administer permissions',
    ]);
    $this->drupalLogin($admin_user);

  }

  /**
   * Test for machine name transliteration functionality.
   *
   * @dataProvider machineNameInputOutput
   */
  public function testMachineNameTransliterations($langcode, $input, $output): void {
    $page = $this->getSession()->getPage();
    if ($langcode !== 'en') {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->config('system.site')->set('default_langcode', $langcode)->save();
    $this->rebuildContainer();

    $this->drupalGet("/admin/people/roles/add");
    $page->find('css', '[data-drupal-selector="edit-label"]')->setValue($input);
    $this->assertSession()->pageTextContains($output);

  }

  /**
   * Data for the testMachineNameTransliterations.
   *
   * @return array
   */
  public static function machineNameInputOutput(): array {
    return [
      // cSpell:disable
      ['en', 'Bob', 'bob'],
      ['en', 'Äwesome', 'awesome'],
      ['de', 'Äwesome', 'aewesome'],
      ['da', 'äöüåøhello', 'aouaaoehello'],
      ['fr', 'ц', 'c'],
      // These tests are not working with chromedriver as
      // 'ᐑ','𐌰𐌸' chars are not accepted.
      // ['fr', 'ᐑ', 'wii'],
      // ['en', '𐌰𐌸', '__'],
      ['en', 'Ä Ö Ü Å Ø äöüåøhello', 'a_o_u_a_o_aouaohello'],
      ['de', 'Ä Ö Ü Å Ø äöüåøhello', 'ae_oe_ue_a_o_aeoeueaohello'],
      ['de', ']URY&m_G^;', ' ury_m_g'],
      ['da', 'Ä Ö Ü Å Ø äöüåøhello', 'a_o_u_aa_oe_aouaaoehello'],
      ['kg', 'ц', 'ts'],
      ['en', ' Hello Abventor! ', 'hello_abventor'],
      // cSpell:enable
    ];
  }

}
