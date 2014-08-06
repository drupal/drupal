<?php

/**
 * @file
 * Contains \Drupal\entity\Tests\ContentTranslationWorkflowsTest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\user\UserInterface;

/**
 * Tests the content translation workflows for the test entity.
 *
 * @group content_translation
 */
class ContentTranslationWorkflowsTest extends ContentTranslationTestBase {

  /**
   * The entity used for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'entity_test');

  function setUp() {
    parent::setUp();
    $this->setupEntity();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationTestBase::getEditorPermissions().
   */
  protected function getEditorPermissions() {
    return array('administer entity_test content');
  }

  /**
   * Creates a test entity and translate it.
   */
  protected function setupEntity() {
    $default_langcode = $this->langcodes[0];

    // Create a test entity.
    $user = $this->drupalCreateUser();
    $values = array(
      'name' => $this->randomMachineName(),
      'user_id' => $user->id(),
      $this->fieldName => array(array('value' => $this->randomMachineName(16))),
    );
    $id = $this->createEntity($values, $default_langcode);
    $this->entity = entity_load($this->entityTypeId, $id, TRUE);

    // Create a translation.
    $this->drupalLogin($this->translator);
    $path = $this->entity->getSystemPath('drupal:content-translation-overview');
    $add_translation_path = $path . "/add/$default_langcode/{$this->langcodes[2]}";
    $this->drupalPostForm($add_translation_path, array(), t('Save'));
    $this->rebuildContainer();
  }

  /**
   * Test simple and editorial translation workflows.
   */
  function testWorkflows() {
    // Test workflows for the editor.
    $expected_status = array('edit' => 200, 'overview' => 403, 'add_translation' => 403, 'edit_translation' => 403);
    $this->assertWorkflows($this->editor, $expected_status);

    // Test workflows for the translator.
    $expected_status = array('edit' => 403, 'overview' => 200, 'add_translation' => 200, 'edit_translation' => 200);
    $this->assertWorkflows($this->translator, $expected_status);

    // Test workflows for the admin.
    $expected_status = array('edit' => 200, 'overview' => 200, 'add_translation' => 200, 'edit_translation' => 200);
    $this->assertWorkflows($this->administrator, $expected_status);

    // Check that translation permissions governate the associated operations.
    $ops = array('create' => t('Add'), 'update' => t('Edit'), 'delete' => t('Delete'));
    $translations_path = $this->entity->getSystemPath('drupal:content-translation-overview');
    foreach ($ops as $current_op => $label) {
      $user = $this->drupalCreateUser(array($this->getTranslatePermission(), "$current_op content translations"));
      $this->drupalLogin($user);
      $this->drupalGet($translations_path);

      foreach ($ops as $op => $label) {
        if ($op != $current_op) {
          $this->assertNoLink($label, format_string('No %op link found.', array('%op' => $label)));
        }
        else {
          $this->assertLink($label, 0, format_string('%op link found.', array('%op' => $label)));
        }
      }
    }
  }

  /**
   * Checks that workflows have the expected behaviors for the given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to test the workflow behavior against.
   * @param array $expected_status
   *   The an associative array with the operation name as key and the expected
   *   status as value.
   */
  protected function assertWorkflows(UserInterface $user, $expected_status) {
    $default_langcode = $this->langcodes[0];
    $languages = $this->container->get('language_manager')->getLanguages();
    $args = array('@user_label' => $user->getUsername());
    $this->drupalLogin($user);

    // Check whether the user is allowed to access the entity form in edit mode.
    $edit_path = $this->entity->getSystemPath('edit-form');
    $options = array('language' => $languages[$default_langcode]);
    $this->drupalGet($edit_path, $options);
    $this->assertResponse($expected_status['edit'], format_string('The @user_label has the expected edit access.', $args));

    // Check whether the user is allowed to access the translation overview.
    $langcode = $this->langcodes[1];
    $translations_path = $this->entity->getSystemPath('drupal:content-translation-overview');
    $options = array('language' => $languages[$langcode]);
    $this->drupalGet($translations_path, $options);
    $this->assertResponse($expected_status['overview'], format_string('The @user_label has the expected translation overview access.', $args));

    // Check whether the user is allowed to create a translation.
    $add_translation_path = $translations_path . "/add/$default_langcode/$langcode";
    if ($expected_status['add_translation'] == 200) {
      $this->clickLink('Add');
      $this->assertUrl($add_translation_path, $options, 'The translation overview points to the translation form when creating translations.');
      // Check that the translation form does not contain shared elements for
      // translators.
      if ($expected_status['edit'] == 403) {
        $this->assertNoSharedElements();
      }
    }
    else {
      $this->drupalGet($add_translation_path, $options);
    }
    $this->assertResponse($expected_status['add_translation'], format_string('The @user_label has the expected translation creation access.', $args));

    // Check whether the user is allowed to edit a translation.
    $langcode = $this->langcodes[2];
    $edit_translation_path = $translations_path . "/edit/$langcode";
    $options = array('language' => $languages[$langcode]);
    if ($expected_status['edit_translation'] == 200) {
      $this->drupalGet($translations_path, $options);
      $editor = $expected_status['edit'] == 200;

      if ($editor) {
        $this->clickLink('Edit', 2);
        // An editor should be pointed to the entity form in multilingual mode.
        $this->assertUrl($edit_path, $options, 'The translation overview points to the edit form for editors when editing translations.');
      }
      else {
        $this->clickLink('Edit');
        // While a translator should be pointed to the translation form.
        $this->assertUrl($edit_translation_path, $options, 'The translation overview points to the translation form for translators when editing translations.');
        // Check that the translation form does not contain shared elements.
        $this->assertNoSharedElements();
      }
    }
    else {
      $this->drupalGet($edit_translation_path, $options);
    }
    $this->assertResponse($expected_status['edit_translation'], format_string('The @user_label has the expected translation creation access.', $args));
  }

  /**
   * Assert that the current page does not contain shared form elements.
   */
  protected function assertNoSharedElements() {
    $language_none = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    return $this->assertNoFieldByXPath("//input[@name='field_test_text[$language_none][0][value]']", NULL, 'Shared elements are not available on the translation form.');
  }

}
