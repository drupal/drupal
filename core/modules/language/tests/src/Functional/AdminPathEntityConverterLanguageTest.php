<?php

namespace Drupal\Tests\language\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Test administration path based conversion of entities.
 *
 * @group language
 */
class AdminPathEntityConverterLanguageTest extends BrowserTestBase {

  public static $modules = array('language', 'language_test');

  protected function setUp() {
    parent::setUp();
    $permissions = array(
      'access administration pages',
      'administer site configuration',
    );
    $this->drupalLogin($this->drupalCreateUser($permissions));
    ConfigurableLanguage::createFromLangcode('es')->save();
  }

  /**
   * Tests the translated and untranslated config entities are loaded properly.
   */
  public function testConfigUsingCurrentLanguage() {
    \Drupal::languageManager()
      ->getLanguageConfigOverride('es', 'language.entity.es')
      ->set('label', 'Español')
      ->save();

    $this->drupalGet('es/admin/language_test/entity_using_current_language/es');
    $this->assertNoRaw(t('Loaded %label.', array('%label' => 'Spanish')));
    $this->assertRaw(t('Loaded %label.', array('%label' => 'Español')));

    $this->drupalGet('es/admin/language_test/entity_using_original_language/es');
    $this->assertRaw(t('Loaded %label.', array('%label' => 'Spanish')));
    $this->assertNoRaw(t('Loaded %label.', array('%label' => 'Español')));
  }

}
