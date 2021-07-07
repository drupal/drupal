<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Url;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Adds and configures custom languages.
 *
 * @group language
 */
class LanguageCustomLanguageConfigurationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Functional tests for adding, editing and deleting languages.
   */
  public function testLanguageConfiguration() {

    // Create user with permissions to add and remove languages.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    // Add custom language.
    $edit = [
      'predefined_langcode' => 'custom',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    // Test validation on missing values.
    $this->assertSession()->pageTextContains('Language code field is required.');
    $this->assertSession()->pageTextContains('Language name field is required.');
    $empty_language = new Language();
    $this->assertSession()->checkboxChecked('edit-direction-' . $empty_language->getDirection());
    $this->assertSession()->addressEquals(Url::fromRoute('language.add'));

    // Test validation of invalid values.
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => 'white space',
      'label' => '<strong>evil markup</strong>',
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');

    $this->assertRaw(t('%field must be a valid language tag as <a href=":url">defined by the W3C</a>.', [
      '%field' => t('Language code'),
      ':url' => 'http://www.w3.org/International/articles/language-tags/',
    ]));

    $this->assertRaw(t('%field cannot contain any markup.', ['%field' => t('Language name')]));
    $this->assertSession()->addressEquals(Url::fromRoute('language.add'));

    // Test adding a custom language with a numeric region code.
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => 'es-419',
      'label' => 'Latin American Spanish',
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];

    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    $this->assertRaw(t(
      'The language %language has been created and can now be used.',
      ['%language' => $edit['label']]
    ));
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection'));

    // Test validation of existing language values.
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => 'de',
      'label' => 'German',
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];

    // Add the language the first time.
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    $this->assertRaw(t(
      'The language %language has been created and can now be used.',
      ['%language' => $edit['label']]
    ));
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection'));

    // Add the language a second time and confirm that this is not allowed.
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    $this->assertRaw(t(
      'The language %language (%langcode) already exists.',
      ['%language' => $edit['label'], '%langcode' => $edit['langcode']]
    ));
    $this->assertSession()->addressEquals(Url::fromRoute('language.add'));
  }

}
