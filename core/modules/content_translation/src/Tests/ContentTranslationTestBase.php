<?php

namespace Drupal\content_translation\Tests;

@trigger_error(__NAMESPACE__ . '\ContentTranslationTestBase is deprecated for removal before Drupal 9.0.0. Use Drupal\Tests\content_translation\Functional\ContentTranslationTestBase instead. See https://www.drupal.org/node/2999939', E_USER_DEPRECATED);

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Base class for content translation tests.
 *
 * @deprecated in drupal:8.?.? and is removed from drupal:9.0.0.
 *   Use \Drupal\Tests\content_translation\Functional\ContentTranslationTestBase instead.
 *
 * @see https://www.drupal.org/node/2999939
 */
abstract class ContentTranslationTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['text'];

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

  /**
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $manager;

  protected function setUp() {
    parent::setUp();

    $this->setupLanguages();
    $this->setupBundle();
    $this->enableTranslation();
    $this->setupUsers();
    $this->setupTestFields();

    $this->manager = $this->container->get('content_translation.manager');
    $this->controller = $this->manager->getTranslationHandler($this->entityTypeId);

    // Rebuild the container so that the new languages are picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();
  }

  /**
   * Adds additional languages.
   */
  protected function setupLanguages() {
    $this->langcodes = ['it', 'fr'];
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    array_unshift($this->langcodes, \Drupal::languageManager()->getDefaultLanguage()->getId());
  }

  /**
   * Returns an array of permissions needed for the translator.
   */
  protected function getTranslatorPermissions() {
    return array_filter([$this->getTranslatePermission(), 'create content translations', 'update content translations', 'delete content translations']);
  }

  /**
   * Returns the translate permissions for the current entity and bundle.
   */
  protected function getTranslatePermission() {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->entityTypeId);
    if ($permission_granularity = $entity_type->getPermissionGranularity()) {
      return $permission_granularity == 'bundle' ? "translate {$this->bundle} {$this->entityTypeId}" : "translate {$this->entityTypeId}";
    }
  }

  /**
   * Returns an array of permissions needed for the editor.
   */
  protected function getEditorPermissions() {
    // Every entity-type-specific test needs to define these.
    return [];
  }

  /**
   * Returns an array of permissions needed for the administrator.
   */
  protected function getAdministratorPermissions() {
    return array_merge($this->getEditorPermissions(), $this->getTranslatorPermissions(), ['administer content translation']);
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
    \Drupal::service('content_translation.manager')->setEnabled($this->entityTypeId, $this->bundle, TRUE);

    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Creates the test fields.
   */
  protected function setupTestFields() {
    if (empty($this->fieldName)) {
      $this->fieldName = 'field_test_et_ui_test';
    }
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'string',
      'entity_type' => $this->entityTypeId,
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldName,
      'bundle' => $this->bundle,
      'label' => 'Test translatable text-field',
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($this->entityTypeId, $this->bundle)
      ->setComponent($this->fieldName, [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
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
   * @return string
   *   The entity id.
   */
  protected function createEntity($values, $langcode, $bundle_name = NULL) {
    $entity_values = $values;
    $entity_values['langcode'] = $langcode;
    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->entityTypeId);
    if ($bundle_key = $entity_type->getKey('bundle')) {
      $entity_values[$bundle_key] = $bundle_name ?: $this->bundle;
    }
    $controller = $this->container->get('entity_type.manager')->getStorage($this->entityTypeId);
    if (!($controller instanceof SqlContentEntityStorage)) {
      foreach ($values as $property => $value) {
        if (is_array($value)) {
          $entity_values[$property] = [$langcode => $value];
        }
      }
    }
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId)
      ->create($entity_values);
    $entity->save();
    return $entity->id();
  }

}
