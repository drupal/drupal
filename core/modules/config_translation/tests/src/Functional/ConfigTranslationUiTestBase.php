<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\filter\Entity\FilterFormat;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Translate settings and entities to various languages.
 */
abstract class ConfigTranslationUiTestBase extends BrowserTestBase {

  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $langcodes = ['fr', 'ta', 'tyv'];

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Translator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translatorUser;

  /**
   * String translation storage object.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'config_translation',
    'config_translation_test',
    'contact',
    'contact_test',
    'contextual',
    'entity_test',
    'field_test',
    'field_ui',
    'filter',
    'filter_test',
    'node',
    'views',
    'views_ui',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $translator_permissions = [
      'translate configuration',
    ];

    /** @var \Drupal\filter\FilterFormatInterface $filter_test_format */
    $filter_test_format = FilterFormat::load('filter_test');
    /** @var \Drupal\filter\FilterFormatInterface $filtered_html_format */
    $filtered_html_format = FilterFormat::load('filtered_html');
    /** @var \Drupal\filter\FilterFormatInterface $full_html_format */
    $full_html_format = FilterFormat::load('full_html');

    $admin_permissions = array_merge(
      $translator_permissions,
      [
        'administer languages',
        'administer site configuration',
        'link to any page',
        'administer contact forms',
        'administer filters',
        $filtered_html_format->getPermissionName(),
        $full_html_format->getPermissionName(),
        $filter_test_format->getPermissionName(),
        'access site-wide contact form',
        'access contextual links',
        'administer views',
        'administer account settings',
        'administer themes',
        'bypass node access',
        'administer content types',
        'translate interface',
      ]
    );
    // Create and log in user.
    $this->translatorUser = $this->drupalCreateUser($translator_permissions);
    $this->adminUser = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->localeStorage = $this->container->get('locale.storage');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Gets translation from locale storage.
   *
   * @param $config_name
   *   Configuration object.
   * @param $key
   *   Translation configuration field key.
   * @param $langcode
   *   String language code to load translation.
   *
   * @return bool|mixed
   *   Returns translation if exists, FALSE otherwise.
   */
  protected function getTranslation($config_name, $key, $langcode) {
    $settings_locations = $this->localeStorage->getLocations(['type' => 'configuration', 'name' => $config_name]);
    $this->assertNotEmpty($settings_locations, "$config_name should have configuration locations.");

    if ($settings_locations) {
      $source = $this->container->get('config.factory')->get($config_name)->get($key);
      $source_string = $this->localeStorage->findString(['source' => $source, 'type' => 'configuration']);
      $this->assertNotEmpty($source_string, "$config_name.$key should have a source string.");

      if ($source_string) {
        $conditions = [
          'lid' => $source_string->lid,
          'language' => $langcode,
        ];
        $translations = $this->localeStorage->getTranslations($conditions + ['translated' => TRUE]);
        return reset($translations);
      }
    }
    return FALSE;
  }

  /**
   * Sets site name and slogan for default language, helps in tests.
   *
   * @param string $site_name
   *   The site name.
   * @param string $site_slogan
   *   The site slogan.
   */
  protected function setSiteInformation($site_name, $site_slogan) {
    $edit = [
      'site_name' => $site_name,
      'site_slogan' => $site_slogan,
    ];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

  /**
   * Asserts that a textarea with a given ID has been disabled from editing.
   *
   * @param string $id
   *   The HTML ID of the textarea.
   *
   * @internal
   */
  protected function assertDisabledTextarea(string $id): void {
    $textarea = $this->assertSession()->fieldDisabled($id);
    $this->assertSame('textarea', $textarea->getTagName());
    $this->assertSame('This field has been disabled because you do not have sufficient permissions to edit it.', $textarea->getText());
    // Make sure the text format select is not shown.
    $select_id = str_replace('value', 'format--2', $id);
    $xpath = $this->assertSession()->buildXPathQuery('//select[@id=:id]', [':id' => $select_id]);
    $this->assertSession()->elementNotExists('xpath', $xpath);
  }

  /**
   * Helper function that returns a .po file with a given number of plural forms.
   */
  public function getPoFile($plurals) {
    $po_file = [];

    $po_file[1] = <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=1; plural=0;\\n"
EOF;

    $po_file[2] = <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n>1);\\n"
EOF;

    $po_file[4] = <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=4; plural=(((n%100)==1)?(0):(((n%100)==2)?(1):((((n%100)==3)||((n%100)==4))?(2):3)));\\n"
EOF;

    return $po_file[$plurals];
  }

}
