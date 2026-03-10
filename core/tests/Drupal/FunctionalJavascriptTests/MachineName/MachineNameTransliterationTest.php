<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\MachineName;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the machine name transliteration functionality.
 */
#[Group('javascript')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class MachineNameTransliterationTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
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
   */
  #[DataProvider('machineNameInputOutput')]
  public function testMachineNameTransliterations(string $langcode, string $input, string $output): void {
    $page = $this->getSession()->getPage();
    if ($langcode !== 'en') {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->config('system.site')->set('default_langcode', $langcode)->save();
    $this->rebuildContainer();

    $this->drupalGet("/admin/people/roles/add");
    $page->find('css', '[data-drupal-selector="edit-label"]')->setValue($input);
    $this->assertSession()->elementTextEquals('css', 'span.machine-name-value', $output);
  }

  /**
   * Data for the testMachineNameTransliterations.
   *
   * @return array
   *   An array of arrays, where each sub-array contains a language code,
   *   input string, and the expected transliterated output string.
   */
  public static function machineNameInputOutput(): array {
    return [
      // cSpell:disable
      ['en', 'Bob', 'bob'],
      ['en', 'Äwesome', 'awesome'],
      ['de', 'Äwesome', 'aewesome'],
      ['da', 'äöüåøhello', 'aouaaoehello'],
      ['fr', 'ц', 'c'],
      ['fr', 'ᐑ', 'wii'],
      // This test is not working with chromedriver as '𐌰𐌸' chars are not
      // accepted.
      // phpcs:ignore Drupal.Commenting.InlineComment.InvalidEndChar
      // ['en', '𐌰𐌸', '__'],
      ['en', 'Ä Ö Ü Å Ø äöüåøhello', 'a_o_u_a_o_aouaohello'],
      ['de', 'Ä Ö Ü Å Ø äöüåøhello', 'ae_oe_ue_a_o_aeoeueaohello'],
      ['de', ']URY&m_G^;', 'ury_m_g'],
      ['da', 'Ä Ö Ü Å Ø äöüåøhello', 'a_o_u_aa_oe_aouaaoehello'],
      ['kg', 'ц', 'ts'],
      ['en', ' Hello Abventor! ', 'hello_abventor'],
      // cSpell:enable
    ];
  }

}
