<?php

/**
 * @file
 * Contains \Drupal\entity\Tests\EntityTranslationTestBase.
 */

namespace Drupal\translation_entity\Tests;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests entity translation workflows.
 */
abstract class EntityTranslationTestBase extends WebTestBase {

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
   * @var \Drupal\user\Plugin\Core\Entity\User
   */
  protected $translator;

  /**
   * The account to be used to test multilingual entity editing.
   *
   * @var \Drupal\user\Plugin\Core\Entity\User
   */
  protected $editor;

  /**
   * The account to be used to test access to both workflows.
   *
   * @var \Drupal\user\Plugin\Core\Entity\User
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
   * @var \Drupal\translation_entity\EntityTranslationControllerInterface
   */
  protected $controller;

  function setUp() {
    parent::setUp();

    $this->setupLanguages();
    $this->setupBundle();
    $this->enableTranslation();
    $this->setupUsers();
    $this->setupTestFields();

    $this->controller = translation_entity_controller($this->entityType);

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
      language_save(new Language(array('langcode' => $langcode)));
    }
    array_unshift($this->langcodes, language_default()->langcode);
  }

  /**
   * Returns an array of permissions needed for the translator.
   */
  protected function getTranslatorPermissions() {
    return array_filter(array($this->getTranslatePermission(), 'create entity translations', 'update entity translations', 'delete entity translations'));
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
    translation_entity_set_config($this->entityType, $this->bundle, 'enabled', TRUE);
    drupal_static_reset();
    entity_info_cache_clear();
    menu_router_rebuild();
  }

  /**
   * Creates the test fields.
   */
  protected function setupTestFields() {
    $this->fieldName = 'field_test_et_ui_test';

    $field = array(
      'field_name' => $this->fieldName,
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => TRUE,
    );
    field_create_field($field);

    $instance = array(
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'bundle' => $this->bundle,
      'label' => 'Test translatable text-field',
    );
    field_create_instance($instance);
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
    $controller = $this->container->get('plugin.manager.entity')->getStorageController($this->entityType);
    if (!($controller instanceof DatabaseStorageControllerNG)) {
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
