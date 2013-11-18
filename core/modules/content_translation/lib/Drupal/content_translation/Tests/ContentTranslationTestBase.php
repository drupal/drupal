<?php

/**
 * @file
 * Contains \Drupal\entity\Tests\ContentTranslationTestBase.
 */

namespace Drupal\content_translation\Tests;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests content translation workflows.
 */
abstract class ContentTranslationTestBase extends WebTestBase {

  /**
   * The entity type being tested.
   *
   * @var string
   */
  protected $entityType = 'entity_test_mul';

  /**
   * The bundle being tested.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The enabled languages.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * The account to be used to test translation operations.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translator;

  /**
   * The account to be used to test multilingual entity editing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor;

  /**
   * The account to be used to test access to both workflows.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $administrator;

  /**
   * The name of the field used to test translation.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The translation controller for the current entity type.
   *
   * @var \Drupal\content_translation\ContentTranslationControllerInterface
   */
  protected $controller;

  function setUp() {
    parent::setUp();

    $this->setupLanguages();
    $this->setupBundle();
    $this->enableTranslation();
    $this->setupUsers();
    $this->setupTestFields();

    $this->controller = content_translation_controller($this->entityType);

    // Rebuild the container so that the new languages are picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();
  }

  /**
   * Enables additional languages.
   */
  protected function setupLanguages() {
    $this->langcodes = array('it', 'fr');
    foreach ($this->langcodes as $langcode) {
      language_save(new Language(array('id' => $langcode)));
    }
    array_unshift($this->langcodes, language_default()->id);
  }

  /**
   * Returns an array of permissions needed for the translator.
   */
  protected function getTranslatorPermissions() {
    return array_filter(array($this->getTranslatePermission(), 'create content translations', 'update content translations', 'delete content translations'));
  }

  /**
   * Returns the translate permissions for the current entity and bundle.
   */
  protected function getTranslatePermission() {
    $info = entity_get_info($this->entityType);
    if (!empty($info['permission_granularity'])) {
      return $info['permission_granularity'] == 'bundle' ? "translate {$this->bundle} {$this->entityType}" : "translate {$this->entityType}";
    }
  }

  /**
   * Returns an array of permissions needed for the editor.
   */
  protected function getEditorPermissions() {
    // Every entity-type-specific test needs to define these.
    return array();
  }

  /**
   * Creates and activates translator, editor and admin users.
   */
  protected function setupUsers() {
    $this->translator = $this->drupalCreateUser($this->getTranslatorPermissions(), 'translator');
    $this->editor = $this->drupalCreateUser($this->getEditorPermissions(), 'editor');
    $this->administrator = $this->drupalCreateUser(array_merge($this->getEditorPermissions(), $this->getTranslatorPermissions()), 'administrator');
    $this->drupalLogin($this->translator);
  }

  /**
   * Creates or initializes the bundle date if needed.
   */
  protected function setupBundle() {
    if (empty($this->bundle)) {
      $this->bundle = $this->entityType;
    }
  }

  /**
   * Enables translation for the current entity type and bundle.
   */
  protected function enableTranslation() {
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    content_translation_set_config($this->entityType, $this->bundle, 'enabled', TRUE);
    drupal_static_reset();
    entity_info_cache_clear();
    menu_router_rebuild();
  }

  /**
   * Creates the test fields.
   */
  protected function setupTestFields() {
    $this->fieldName = 'field_test_et_ui_test';

    entity_create('field_entity', array(
      'name' => $this->fieldName,
      'type' => 'text',
      'entity_type' => $this->entityType,
      'cardinality' => 1,
      'translatable' => TRUE,
    ))->save();
    entity_create('field_instance', array(
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'bundle' => $this->bundle,
      'label' => 'Test translatable text-field',
    ))->save();
    entity_get_form_display($this->entityType, $this->bundle, 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'text_textfield',
        'weight' => 0,
      ))
      ->save();
  }

  /**
   * Creates the entity to be translated.
   *
   * @param array $values
   *   An array of initial values for the entity.
   * @param string $langcode
   *   The initial language code of the entity.
   * @param string $bundle_name
   *   (optional) The entity bundle, if the entity uses bundles. Defaults to
   *   NULL. If left NULL, $this->bundle will be used.
   *
   * @return
   *   The entity id.
   */
  protected function createEntity($values, $langcode, $bundle_name = NULL) {
    $entity_values = $values;
    $entity_values['langcode'] = $langcode;
    $info = entity_get_info($this->entityType);
    if (!empty($info['entity_keys']['bundle'])) {
      $entity_values[$info['entity_keys']['bundle']] = $bundle_name ?: $this->bundle;
    }
    $controller = $this->container->get('entity.manager')->getStorageController($this->entityType);
    if (!($controller instanceof FieldableDatabaseStorageController)) {
      foreach ($values as $property => $value) {
        if (is_array($value)) {
          $entity_values[$property] = array($langcode => $value);
        }
      }
    }
    $entity = entity_create($this->entityType, $entity_values);
    $entity->save();
    return $entity->id();
  }

}
