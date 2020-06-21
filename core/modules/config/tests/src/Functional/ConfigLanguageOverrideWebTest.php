<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests language overrides applied through the website.
 *
 * @group config
 */
class ConfigLanguageOverrideWebTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'language',
    'system',
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
  }

  /**
   * Tests translating the site name.
   */
  public function testSiteNameTranslation() {
    $adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer languages',
    ]);
    $this->drupalLogin($adminUser);

    // Add a custom language.
    $langcode = 'xx';
    $name = $this->randomMachineName(16);
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    \Drupal::languageManager()
      ->getLanguageConfigOverride($langcode, 'system.site')
      ->set('name', 'XX site name')
      ->save();

    // Place branding block with site name into header region.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);

    $this->drupalLogout();

    // The home page in English should not have the override.
    $this->drupalGet('');
    $this->assertNoText('XX site name');

    // During path resolution the system.site configuration object is used to
    // determine the front page. This occurs before language negotiation causing
    // the configuration factory to cache an object without the correct
    // overrides. We are testing that the configuration factory is
    // re-initialized after language negotiation. Ensure that it applies when
    // we access the XX front page.
    // @see \Drupal\Core\PathProcessor::processInbound()
    $this->drupalGet('xx');
    $this->assertText('XX site name');

    // Set the xx language to be the default language and delete the English
    // language so the site is no longer multilingual and confirm configuration
    // overrides still work.
    $language_manager = \Drupal::languageManager()->reset();
    $this->assertTrue($language_manager->isMultilingual(), 'The test site is multilingual.');
    $this->config('system.site')->set('default_langcode', 'xx')->save();

    ConfigurableLanguage::load('en')->delete();
    $this->assertFalse($language_manager->isMultilingual(), 'The test site is monolingual.');

    $this->drupalGet('xx');
    $this->assertText('XX site name');

  }

}
