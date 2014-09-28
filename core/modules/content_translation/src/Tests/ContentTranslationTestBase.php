<?php

/**
 * @file
 * Contains \Drupal\entity\Tests\ContentTranslationTestBase.
 */

namespace Drupal\content_translation\Tests;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests content translation workflows.
 */
abstract class ContentTranslationTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('text');

  /**
   * The entity type being tested.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test_mul';

  /**
   * The bundle being tested.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The added languages.
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
   * @var \Drupal\content_translation\ContentTranslationHandlerInterface
   */
  protected $controller;

  protected function setUp() {
    parent::setUp();

    $this->setupLanguages();
    $this->setupBundle();
    $this->enableTranslation();
    $this->setupUsers();
    $this->setupTestFields();

    $this->controller = content_translation_controller($this->entityTypeId);

    // Rebuild the container so that the new languages are picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();
  }

  /**
   * Adds additional languages.
   */
  protected function setupLanguages() {
    $this->langcodes = array('it', 'fr');
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    array_unshift($this->langcodes, \Drupal::languageManager()->getDefaultLanguage()->id);
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
    $entity_type = \Drupal::entityManager()->getDefinition($this->entityTypeId);
    if ($permission_granularity = $entity_type->getPermissionGranularity()) {
      return $permission_granularity == 'bundle' ? "translate {$this->bundle} {$this->entityTypeId}" : "translate {$this->entityTypeId}";
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
   * Returns an array of permissions needed for the administrator.
   */
  protected function getAdministratorPermissions() {
    return array_merge($this->getEditorPermissions(), $this->getTranslatorPermissions(), array('administer content translation'));
  }

  /**
   * Creates and activates translator, editor and admin users.
   */
  protected function setupUsers() {
    $this->translator = $this->drupalCreateUser($this->getTranslatorPermissions(), 'translator');
    $this->editor = $this->drupalCreateUser($this->getEditorPermissions(), 'editor');
    $this->administrator = $this->drupalCreateUser($this->getAdministratorPermissions(), 'administrator');
    $this->drupalLogin($this->translator);
  }

  /**
   * Creates or initializes the bundle date if needed.
   */
  protected function setupBundle() {
    if (empty($this->bundle)) {
      $this->bundle = $this->entityTypeId;
    }
  }

  /**
   * Enables translation for the current entity type and bundle.
   */
  protected function enableTranslation() {
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    content_translation_set_config($this->entityTypeId, $this->bundle, 'enabled', TRUE);
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Creates the test fields.
   */
  protected function setupTestFields() {
    $entity_type = \Drupal::entityManager()->getDefinition($this->entityTypeId);
    if ($entity_type->isFieldable()) {
      if (empty($this->fieldName)) {
        $this->fieldName = 'field_test_et_ui_test';
      }
      entity_create('field_storage_config', array(
        'field_name' => $this->fieldName,
        'type' => 'string',
        'entity_type' => $this->entityTypeId,
        'cardinality' => 1,
        'translatable' => TRUE,
      ))->save();
      entity_create('field_config', array(
        'entity_type' => $this->entityTypeId,
        'field_name' => $this->fieldName,
        'bundle' => $this->bundle,
        'label' => 'Test translatable text-field',
      ))->save();
      entity_get_form_display($this->entityTypeId, $this->bundle, 'default')
        ->setComponent($this->fieldName, array(
          'type' => 'string_textfield',
          'weight' => 0,
        ))
        ->save();
    }
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
    $entity_type = \Drupal::entityManager()->getDefinition($this->entityTypeId);
    if ($bundle_key = $entity_type->getKey('bundle')) {
      $entity_values[$bundle_key] = $bundle_name ?: $this->bundle;
    }
    $controller = $this->container->get('entity.manager')->getStorage($this->entityTypeId);
    if (!($controller instanceof SqlContentEntityStorage)) {
      foreach ($values as $property => $value) {
        if (is_array($value)) {
          $entity_values[$property] = array($langcode => $value);
        }
      }
    }
    $entity = entity_create($this->entityTypeId, $entity_values);
    $entity->save();
    return $entity->id();
  }

}
