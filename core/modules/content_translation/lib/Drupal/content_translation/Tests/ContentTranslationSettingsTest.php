<?php

/**
 * @file
 * Contains Drupal\content_translation\Tests\ContentTranslationSettingsTest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\Core\Language\Language;
use Drupal\field\Field as FieldService;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Entity Test Translation UI.
 */
class ContentTranslationSettingsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'node', 'comment', 'field_ui');

  public static function getInfo() {
    return array(
      'name' => 'Content Translation settings',
      'description' => 'Tests the content translation settings UI.',
      'group' => 'Content Translation UI',
    );
  }

  function setUp() {
    parent::setUp();

    // Set up two content types to test field instances shared between different
    // bundles.
    $this->drupalCreateContentType(array('type' => 'article'));
    $this->drupalCreateContentType(array('type' => 'page'));
    $this->container->get('comment.manager')->addDefaultField('node', 'article', 'comment_article');

    $admin_user = $this->drupalCreateUser(array('access administration pages', 'administer languages', 'administer content translation', 'administer content types', 'administer node fields', 'administer comment fields'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the settings UI works as expected.
   */
  function testSettingsUI() {
    // Check for the content_translation_menu_link_defaults_alter() changes.
    $this->drupalGet('admin/config');
    $this->assertLink('Content language and translation');
    $this->assertText('Configure language and translation support for content.');
    // Test that the translation settings are ignored if the bundle is marked
    // translatable but the entity type is not.
    $edit = array('settings[comment][node__comment_article][translatable]' => TRUE);
    $this->assertSettings('comment', NULL, FALSE, $edit);

    // Test that the translation settings are ignored if only a field is marked
    // as translatable and not the related entity type and bundle.
    $edit = array('settings[comment][node__comment_article][fields][comment_body]' => TRUE);
    $this->assertSettings('comment', NULL, FALSE, $edit);

    // Test that the translation settings are not stored if an entity type and
    // bundle are marked as translatable but no field is.
    $edit = array(
      'entity_types[comment]' => TRUE,
      'settings[comment][node__comment_article][translatable]' => TRUE,
    );
    $this->assertSettings('comment', 'node__comment_article', FALSE, $edit);
    $xpath_err = '//div[contains(@class, "error")]';
    $this->assertTrue($this->xpath($xpath_err), 'Enabling translation only for entity bundles generates a form error.');

    // Test that the translation settings are not stored if a non-configurable
    // language is set as default and the language selector is hidden.
    $edit = array(
      'entity_types[comment]' => TRUE,
      'settings[comment][node__comment_article][settings][language][langcode]' => Language::LANGCODE_NOT_SPECIFIED,
      'settings[comment][node__comment_article][settings][language][language_show]' => FALSE,
      'settings[comment][node__comment_article][translatable]' => TRUE,
      'settings[comment][node__comment_article][fields][comment_body]' => TRUE,
    );
    $this->assertSettings('comment', 'node__comment_article', FALSE, $edit);
    $this->assertTrue($this->xpath($xpath_err), 'Enabling translation with a fixed non-configurable language generates a form error.');

    // Test that a field shared among different bundles can be enabled without
    // needing to make all the related bundles translatable.
    $edit = array(
      'entity_types[comment]' => TRUE,
      'settings[comment][node__comment_article][settings][language][langcode]' => 'current_interface',
      'settings[comment][node__comment_article][settings][language][language_show]' => TRUE,
      'settings[comment][node__comment_article][translatable]' => TRUE,
      'settings[comment][node__comment_article][fields][comment_body]' => TRUE,
    );
    $this->assertSettings('comment', 'node__comment_article', TRUE, $edit);
    field_info_cache_clear();
    $field = field_info_field('comment', 'comment_body');
    $this->assertTrue($field->isTranslatable(), 'Comment body is translatable.');

    // Test that language settings are correctly stored.
    $language_configuration = language_get_default_configuration('comment', 'node__comment_article');
    $this->assertEqual($language_configuration['langcode'], 'current_interface', 'The default language for article comments is set to the current interface language.');
    $this->assertTrue($language_configuration['language_show'], 'The language selector for article comments is shown.');

    // Verify language widget appears on node type form.
    $this->drupalGet('admin/structure/comments/manage/node__comment_article/fields/comment.node__comment_article.comment_body/field');
    $this->assertField('field[translatable]');
    $this->assertFieldChecked('edit-field-translatable');

    // Verify that translation may be enabled for the article content type.
    $edit = array(
      'language_configuration[content_translation]' => TRUE,
    );
    // Make sure the checkbox is available and not checked by default.
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertField('language_configuration[content_translation]');
    $this->assertNoFieldChecked('edit-language-configuration-content-translation');
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertFieldChecked('edit-language-configuration-content-translation');

    // Test that the title field of nodes is available in the settings form.
    $edit = array(
      'entity_types[node]' => TRUE,
      'settings[node][article][settings][language][langcode]' => 'current_interface',
      'settings[node][article][settings][language][language_show]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][fields][title]' => TRUE
    );
    $this->assertSettings('node', NULL, TRUE, $edit);

    foreach (array(TRUE, FALSE) as $translatable) {
      // Test that configurable field translatability is correctly switched.
      $edit = array('settings[node][article][fields][body]' => $translatable);
      $this->assertSettings('node', 'article', TRUE, $edit);
      $field = FieldService::fieldInfo()->getField('node', 'body');
      $definitions = \Drupal::entityManager()->getFieldDefinitions('node', 'article');
      $this->assertEqual($definitions['body']->isTranslatable(), $translatable, 'Field translatability correctly switched.');
      $this->assertEqual($field->isTranslatable(), $definitions['body']->isTranslatable(), 'Configurable field translatability correctly switched.');

      // Test that also the Field UI form behaves correctly.
      $translatable = !$translatable;
      $edit = array('field[translatable]' => $translatable);
      $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.body/field', $edit, t('Save field settings'));
      field_info_cache_clear();
      entity_info_cache_clear();
      $field = FieldService::fieldInfo()->getField('node', 'body');
      $definitions = \Drupal::entityManager()->getFieldDefinitions('node', 'article');
      $this->assertEqual($definitions['body']->isTranslatable(), $translatable, 'Field translatability correctly switched.');
      $this->assertEqual($field->isTranslatable(), $definitions['body']->isTranslatable(), 'Configurable field translatability correctly switched.');
    }
  }

  /**
   * Asserts that translatability has the expected value for the given bundle.
   *
   * @param string $entity_type
   *   The entity type for which to check translatibility.
   * @param string $bundle
   *   The bundle for which to check translatibility.
   * @param boolean $enabled
   *   TRUE if translatibility should be enabled, FALSE otherwise.
   * @param array $edit
   *   An array of values to submit to the content translation settings page.
   *
   * @return boolean
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertSettings($entity_type, $bundle, $enabled, $edit) {
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save'));
    $args = array('@entity_type' => $entity_type, '@bundle' => $bundle, '@enabled' => $enabled ? 'enabled' : 'disabled');
    $message = format_string('Translation for entity @entity_type (@bundle) is @enabled.', $args);
    field_info_cache_clear();
    entity_info_cache_clear();
    return $this->assertEqual(content_translation_enabled($entity_type, $bundle), $enabled, $message);
  }

  /**
   * Tests that field instance setting depends on bundle translatability.
   */
  function testFieldTranslatableSettingsUI() {

    // At least one field needs to be translatable to enable article for
    // translation. Create an extra field to be used for this purpose.
    $field = array(
      'name' => 'article_text',
      'entity_type' => 'node',
      'type' => 'text',
    );
    entity_create('field_config', $field)->save();
    $instance = array(
      'field_name' => 'article_text',
      'entity_type' => 'node',
      'bundle' => 'article',
    );
    entity_create('field_instance_config', $instance)->save();

    // Tests that field instance doesn't have translatable setting if bundle
    // is not translatable.
    $path = 'admin/structure/types/manage/article/fields/node.article.body/field';
    $this->drupalGet($path);
    $this->assertText('To enable translation of this field, enable language support for this type.', 'No translatable setting for field.');

    // Tests that field instance has translatable setting if bundle is
    // translatable. Note: this field instance is not translatable when
    // enable bundle translatability.
    $edit = array(
      'entity_types[node]' => TRUE,
      'settings[node][article][settings][language][language_show]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][fields][article_text]' => TRUE,
    );
    $this->assertSettings('node', 'article', TRUE, $edit);
    $this->drupalGet($path);
    $this->assertNoText('To enable translation of this field, enable language support for this type.', 'No translatable setting for field.');
  }

}
