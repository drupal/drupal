<?php

/**
 * @file
 * Contains \Drupal\config_translation\Tests\ConfigTranslationListUiTest.
 */

namespace Drupal\config_translation\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for listings that should have translate operation, except views.
 *
 * @see \Drupal\config_translation\Tests\ConfigTranslationViewListUiTest
 */
class ConfigTranslationListUiTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'block',
    'config_translation',
    'contact',
    'custom_block',
    'field',
    'field_ui',
    'menu',
    'node',
    'shortcut',
    'taxonomy',
    'image',
    'responsive_image',
    'toolbar',
  );

  public static function getInfo() {
    return array(
      'name' => 'Configuration Translation lists',
      'description' => 'Visit all lists.',
      'group' => 'Configuration Translation',
    );
  }

  /**
   * Admin user with all needed permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  public function setUp() {
    parent::setUp();

    $permissions = array(
      'access site-wide contact form',
      'administer blocks',
      'administer contact forms',
      'administer content types',
      'administer custom_block fields',
      'administer filters',
      'administer menu',
      'administer node fields',
      'administer permissions',
      'administer shortcuts',
      'administer site configuration',
      'administer taxonomy',
      'administer account settings',
      'administer languages',
      'administer image styles',
      'administer responsive image',
      'translate configuration',
    );

    // Create and log in user.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    \Drupal::config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->save();
  }

  /**
   * Tests the block listing for the translate operation.
   *
   * There are no blocks placed in the testing profile. Add one, then check
   * for Translate operation.
   */
  protected function doBlockListTest() {
    // Add a test block, any block will do.
    // Set the machine name so the translate link can be built later.
    $id = Unicode::strtolower($this->randomName(16));
    $this->drupalPlaceBlock('system_powered_by_block', array('id' => $id));

    // Get the Block listing.
    $this->drupalGet('admin/structure/block');

    $translate_link = 'admin/structure/block/manage/' . $id . '/translate';
    // Test if the link to translate the block is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the menu listing for the translate operation.
   */
  protected function doMenuListTest() {
    // Create a test menu to decouple looking for translate operations link so
    // this does not test more than necessary.
    $this->drupalGet('admin/structure/menu/add');
    // Lowercase the machine name.
    $menu_name = Unicode::strtolower($this->randomName(16));
    $label = $this->randomName(16);
    $edit = array(
      'id' => $menu_name,
      'description' => '',
      'label' =>  $label,
    );
    // Create the menu by posting the form.
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));

    // Get the Menu listing.
    $this->drupalGet('admin/structure/menu');

    $translate_link = 'admin/structure/menu/manage/' . $menu_name . '/translate';
    // Test if the link to translate the menu is on the page.
    $this->assertLinkByHref($translate_link);

    // Check if the Link is not added if you are missing 'translate
    // configuration' permission.
    $permissions = array(
      'administer menu',
    );
    $this->drupalLogin($this->drupalCreateUser($permissions));

    // Get the Menu listing.
    $this->drupalGet('admin/structure/menu');

    $translate_link = 'admin/structure/menu/manage/' . $menu_name . '/translate';
    // Test if the link to translate the menu is NOT on the page.
    $this->assertNoLinkByHref($translate_link);

    // Login as Admin again otherwise the rest will fail.
    $this->drupalLogin($this->adminUser);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the vocabulary listing for the translate operation.
   */
  protected function doVocabularyListTest() {
    // Create a test vocabulary to decouple looking for translate operations
    // link so this does not test more than necessary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      'vid' => Unicode::strtolower($this->randomName()),
    ));
    $vocabulary->save();

    // Get the Taxonomy listing.
    $this->drupalGet('admin/structure/taxonomy');

    $translate_link = 'admin/structure/taxonomy/manage/' . $vocabulary->id() . '/translate';
    // Test if the link to translate the vocabulary is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the custom block listing for the translate operation.
   */
  public function doCustomBlockTypeListTest() {
    // Create a test custom block type to decouple looking for translate
    // operations link so this does not test more than necessary.
    $custom_block_type = entity_create('custom_block_type', array(
      'id' => Unicode::strtolower($this->randomName(16)),
      'label' => $this->randomName(),
      'revision' => FALSE
    ));
    $custom_block_type->save();

    // Get the custom block type listing.
    $this->drupalGet('admin/structure/block/custom-blocks/types');

    $translate_link = 'admin/structure/block/custom-blocks/manage/' . $custom_block_type->id() . '/translate';
    // Test if the link to translate the custom block type is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the contact forms listing for the translate operation.
   */
  public function doContactFormsListTest() {
    // Create a test contact form to decouple looking for translate operations
    // link so this does not test more than necessary.
    $contact_form = entity_create('contact_category', array(
      'id' => Unicode::strtolower($this->randomName(16)),
      'label' => $this->randomName(),
    ));
    $contact_form->save();

    // Get the contact form listing.
    $this->drupalGet('admin/structure/contact');

    $translate_link = 'admin/structure/contact/manage/' . $contact_form->id() . '/translate';
    // Test if the link to translate the contact form is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the content type listing for the translate operation.
   */
  public function doContentTypeListTest() {
    // Create a test content type to decouple looking for translate operations
    // link so this does not test more than necessary.
    $content_type = entity_create('node_type', array(
      'type' => Unicode::strtolower($this->randomName(16)),
      'name' => $this->randomName(),
    ));
    $content_type->save();

    // Get the content type listing.
    $this->drupalGet('admin/structure/types');

    $translate_link = 'admin/structure/types/manage/' . $content_type->id() . '/translate';
    // Test if the link to translate the content type is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the formats listing for the translate operation.
   */
  public function doFormatsListTest() {
    // Create a test format to decouple looking for translate operations
    // link so this does not test more than necessary.
    $filter_format = entity_create('filter_format', array(
      'format' => Unicode::strtolower($this->randomName(16)),
      'name' => $this->randomName(),
    ));
    $filter_format->save();

    // Get the format listing.
    $this->drupalGet('admin/config/content/formats');

    $translate_link = 'admin/config/content/formats/manage/' . $filter_format->id() . '/translate';
    // Test if the link to translate the format is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the shortcut listing for the translate operation.
   */
  public function doShortcutListTest() {
    // Create a test shortcut to decouple looking for translate operations
    // link so this does not test more than necessary.
    $shortcut = entity_create('shortcut_set', array(
      'id' => Unicode::strtolower($this->randomName(16)),
      'label' => $this->randomString(),
    ));
    $shortcut->save();

    // Get the shortcut listing.
    $this->drupalGet('admin/config/user-interface/shortcut');

    $translate_link = 'admin/config/user-interface/shortcut/manage/' . $shortcut->id() . '/translate';
    // Test if the link to translate the shortcut is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the role listing for the translate operation.
   */
  public function doUserRoleListTest() {
    // Create a test role to decouple looking for translate operations
    // link so this does not test more than necessary.
    $role_id = Unicode::strtolower($this->randomName(16));
    $this->drupalCreateRole(array(), $role_id);

    // Get the role listing.
    $this->drupalGet('admin/people/roles');

    $translate_link = 'admin/people/roles/manage/' . $role_id . '/translate';
    // Test if the link to translate the role is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the language listing for the translate operation.
   */
  public function doLanguageListTest() {
    // Create a test language to decouple looking for translate operations
    // link so this does not test more than necessary.
    $language = new Language(array('id' => 'ga', 'name' => 'Irish'));
    language_save($language);

    // Get the language listing.
    $this->drupalGet('admin/config/regional/language');

    $translate_link = 'admin/config/regional/language/edit/ga/translate';
    // Test if the link to translate the language is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the image style listing for the translate operation.
   */
  public function doImageStyleListTest() {
    // Get the image style listing.
    $this->drupalGet('admin/config/media/image-styles');

    $translate_link = 'admin/config/media/image-styles/manage/medium/translate';
    // Test if the link to translate the style is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the responsive image mapping listing for the translate operation.
   */
  public function doResponsiveImageListTest() {
    $edit = array();
    $edit['label'] = $this->randomName();
    $edit['id'] = strtolower($edit['label']);

    $this->drupalPostForm('admin/config/media/responsive-image-mapping/add', $edit, t('Save'));
    $this->assertRaw(t('Responsive image mapping %label saved.', array('%label' => $edit['label'])));

    // Get the responsive image mapping listing.
    $this->drupalGet('admin/config/media/responsive-image-mapping');

    $translate_link = 'admin/config/media/responsive-image-mapping/' . $edit['id'] . '/translate';
    // Test if the link to translate the style is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests the field listing for the translate operation.
   */
  public function doFieldListTest() {
    // Create a base content type.
    $content_type = entity_create('node_type', array(
      'type' => Unicode::strtolower($this->randomName(16)),
      'name' => $this->randomName(),
    ));
    $content_type->save();

    // Look at a few fields on a few entity types.
    $pages = array(
      array(
        'list' => 'admin/structure/types/manage/' . $content_type->id() . '/fields',
        'field' => 'node.' . $content_type->id() . '.body',
      ),
      array(
        'list' => 'admin/structure/block/custom-blocks/manage/basic/fields',
        'field' => 'custom_block.basic.body',
      ),
    );

    foreach ($pages as $values) {
      // Get fields listing.
      $this->drupalGet($values['list']);

      $translate_link = $values['list'] . '/' . $values['field'] . '/translate';
      // Test if the link to translate the field is on the page.
      $this->assertLinkByHref($translate_link);

      // Test if the link to translate actually goes to the translate page.
      $this->drupalGet($translate_link);
      $this->assertRaw('<th>' . t('Language') . '</th>');
    }
  }

  /**
   * Tests the date format listing for the translate operation.
   */
  public function doDateFormatListTest() {
    // Get the date format listing.
    $this->drupalGet('admin/config/regional/date-time');

    $translate_link = 'admin/config/regional/date-time/formats/manage/long/translate';
    // Test if the link to translate the format is on the page.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests a given settings page for the translate operation.
   *
   * @param string $link
   *   URL of the settings page to test.
   */
  public function doSettingsPageTest($link) {
    // Get the settings page.
    $this->drupalGet($link);

    $translate_link = $link . '/translate';
    // Test if the link to translate the settings page is present.
    $this->assertLinkByHref($translate_link);

    // Test if the link to translate actually goes to the translate page.
    $this->drupalGet($translate_link);
    $this->assertRaw('<th>' . t('Language') . '</th>');
  }

  /**
   * Tests if translate link is added to operations in all configuration lists.
   */
  public function testTranslateOperationInListUi() {
    // All lists based on paths provided by the module.
    $this->doBlockListTest();
    $this->doMenuListTest();
    $this->doVocabularyListTest();
    $this->doCustomBlockTypeListTest();
    $this->doContactFormsListTest();
    $this->doContentTypeListTest();
    $this->doFormatsListTest();
    $this->doShortcutListTest();
    $this->doUserRoleListTest();
    $this->doLanguageListTest();
    $this->doImageStyleListTest();
    $this->doResponsiveImageListTest();
    $this->doDateFormatListTest();
    $this->doFieldListTest();

    // Views is tested in Drupal\config_translation\Tests\ConfigTranslationViewListUiTest

    // Test the maintenance settings page.
    $this->doSettingsPageTest('admin/config/development/maintenance');
    // Test the site information settings page.
    $this->doSettingsPageTest('admin/config/system/site-information');
    // Test the account settings page.
    $this->doSettingsPageTest('admin/config/people/accounts');
    // Test the RSS settings page.
    $this->doSettingsPageTest('admin/config/services/rss-publishing');
  }

}
