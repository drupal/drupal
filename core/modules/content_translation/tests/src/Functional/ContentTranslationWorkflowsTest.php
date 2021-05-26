<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\UserInterface;

/**
 * Tests the content translation workflows for the test entity.
 *
 * @group content_translation
 */
class ContentTranslationWorkflowsTest extends ContentTranslationTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * The entity used for testing.
   *
   * @var \Drupal\entity_test\Entity\EntityTestMul
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'entity_test_mulrevpub';

  /**
   * The referencing entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTestMul
   */
  protected $referencingEntity;

  /**
   * The entity owner account to be used to test multilingual entity editing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $entityOwner;

  /**
   * The user that has entity owner permission but is not the owner.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $notEntityOwner;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'content_translation',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_reference',
      'type' => 'entity_reference',
      'entity_type' => $this->entityTypeId,
      'cardinality' => 1,
      'settings' => [
        'target_type' => $this->entityTypeId,
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->entityTypeId,
      'label' => 'Reference',
      'translatable' => FALSE,
    ])->save();

    $this->container->get('entity_display.repository')
      ->getViewDisplay($this->entityTypeId, $this->entityTypeId, 'default')
      ->setComponent('field_reference', [
        'type' => 'entity_reference_entity_view',
      ])
      ->save();

    $this->setupEntity();

    // Create a second entity that references the first to test how the
    // translation can be viewed through an entity reference field.
    $this->referencingEntity = EntityTestMulRevPub::create([
      'name' => 'referencing',
      'field_reference' => $this->entity->id(),
    ]);
    $this->referencingEntity->addTranslation($this->langcodes[2], $this->referencingEntity->toArray());
    $this->referencingEntity->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function setupUsers() {
    $this->entityOwner = $this->drupalCreateUser($this->getEntityOwnerPermissions(), 'entity_owner');
    $this->notEntityOwner = $this->drupalCreateUser();
    $this->notEntityOwner->set('roles', $this->entityOwner->getRoles(TRUE));
    $this->notEntityOwner->save();
    parent::setupUsers();
  }

  /**
   * Returns an array of permissions needed for the entity owner.
   */
  protected function getEntityOwnerPermissions() {
    return ['edit own entity_test content', 'translate editable entities', 'view test entity', 'view test entity translations', 'view unpublished test entity translations'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    $permissions = parent::getTranslatorPermissions();
    $permissions[] = 'view test entity';
    $permissions[] = 'view test entity translations';
    $permissions[] = 'view unpublished test entity translations';

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions() {
    return ['administer entity_test content', 'view test entity', 'view test entity translations'];
  }

  /**
   * Creates a test entity and translate it.
   *
   * @param Drupal\User\UserInterface|null $user
   *   (optional) The entity owner.
   */
  protected function setupEntity(UserInterface $user = NULL) {
    $default_langcode = $this->langcodes[0];

    // Create a test entity.
    $user = $user ?: $this->drupalCreateUser();
    $values = [
      'name' => $this->randomMachineName(),
      'user_id' => $user->id(),
      $this->fieldName => [['value' => $this->randomMachineName(16)]],
    ];
    $id = $this->createEntity($values, $default_langcode);
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);

    // Create a translation that is not published to test view access.
    $this->drupalLogin($this->translator);
    $add_translation_url = Url::fromRoute("entity.$this->entityTypeId.content_translation_add", [$this->entityTypeId => $id, 'source' => $default_langcode, 'target' => $this->langcodes[2]]);
    $edit = [
      'name[0][value]' => 'translation name',
      'content_translation[status]' => FALSE,
    ];
    $this->drupalGet($add_translation_url);
    $this->submitForm($edit, 'Save');

    $storage->resetCache([$id]);
    $this->entity = $storage->load($id);

    $this->rebuildContainer();
  }

  /**
   * Test simple and editorial translation workflows.
   */
  public function testWorkflows() {
    // Test workflows for the editor.
    $expected_status = [
      'edit' => 200,
      'delete' => 200,
      'overview' => 403,
      'add_translation' => 403,
      'edit_translation' => 403,
      'delete_translation' => 403,
      'view_unpublished_translation' => 403,
      'view_unpublished_translation_reference' => FALSE,
    ];
    $this->doTestWorkflows($this->editor, $expected_status);

    // Test workflows for the translator.
    $expected_status = [
      'edit' => 403,
      'delete' => 403,
      'overview' => 200,
      'add_translation' => 200,
      'edit_translation' => 200,
      'delete_translation' => 200,
      'view_unpublished_translation' => 200,
      'view_unpublished_translation_reference' => TRUE,
    ];
    $this->doTestWorkflows($this->translator, $expected_status);

    // Test workflows for the admin.
    $expected_status = [
      'edit' => 200,
      'delete' => 200,
      'overview' => 200,
      'add_translation' => 200,
      'edit_translation' => 403,
      'delete_translation' => 403,
      'view_unpublished_translation' => 200,
      'view_unpublished_translation_reference' => TRUE,
    ];
    $this->doTestWorkflows($this->administrator, $expected_status);

    // Check that translation permissions allow the associated operations.
    $ops = ['create' => t('Add'), 'update' => t('Edit'), 'delete' => t('Delete')];
    $translations_url = $this->entity->toUrl('drupal:content-translation-overview');
    foreach ($ops as $current_op => $item) {
      $user = $this->drupalCreateUser([
        $this->getTranslatePermission(),
        "$current_op content translations",
        'view test entity',
      ]);
      $this->drupalLogin($user);
      $this->drupalGet($translations_url);

      // Make sure that the user.permissions cache context and the cache tags
      // for the entity are present.
      $this->assertCacheContext('user.permissions');
      foreach ($this->entity->getCacheTags() as $cache_tag) {
        $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $cache_tag);
      }

      foreach ($ops as $op => $label) {
        if ($op != $current_op) {
          $this->assertSession()->linkNotExists($label, new FormattableMarkup('No %op link found.', ['%op' => $label]));
        }
        else {
          $this->assertSession()->linkExists($label, 0, new FormattableMarkup('%op link found.', ['%op' => $label]));
        }
      }
    }

    // Test workflows for the entity owner with non-editable content.
    $expected_status = [
      'edit' => 403,
      'delete' => 403,
      'overview' => 403,
      'add_translation' => 403,
      'edit_translation' => 403,
      'delete_translation' => 403,
      'view_unpublished_translation' => 200,
      'view_unpublished_translation_reference' => TRUE,
    ];
    $this->doTestWorkflows($this->entityOwner, $expected_status);

    // Test workflows for the entity owner with editable content.
    $this->setupEntity($this->entityOwner);
    $this->referencingEntity->set('field_reference', $this->entity->id());
    $this->referencingEntity->save();
    $expected_status = [
      'edit' => 200,
      'delete' => 403,
      'overview' => 200,
      'add_translation' => 200,
      'edit_translation' => 200,
      'delete_translation' => 200,
      'view_unpublished_translation' => 200,
      'view_unpublished_translation_reference' => TRUE,
    ];
    $this->doTestWorkflows($this->entityOwner, $expected_status);
    $expected_status = [
      'edit' => 403,
      'delete' => 403,
      'overview' => 403,
      'add_translation' => 403,
      'edit_translation' => 403,
      'delete_translation' => 403,
      'view_unpublished_translation' => 200,
      'view_unpublished_translation_reference' => TRUE,
    ];
    $this->doTestWorkflows($this->notEntityOwner, $expected_status);
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
  protected function doTestWorkflows(UserInterface $user, $expected_status) {
    $default_langcode = $this->langcodes[0];
    $languages = $this->container->get('language_manager')->getLanguages();
    $options = ['language' => $languages[$default_langcode], 'absolute' => TRUE];
    $this->drupalLogin($user);

    // Check whether the user is allowed to access the entity form in edit mode.
    $edit_url = $this->entity->toUrl('edit-form', $options);
    $this->drupalGet($edit_url, $options);
    $this->assertSession()->statusCodeEquals($expected_status['edit']);

    // Check whether the user is allowed to access the entity delete form.
    $delete_url = $this->entity->toUrl('delete-form', $options);
    $this->drupalGet($delete_url, $options);
    $this->assertSession()->statusCodeEquals($expected_status['delete']);

    // Check whether the user is allowed to access the translation overview.
    $langcode = $this->langcodes[1];
    $options['language'] = $languages[$langcode];
    $translations_url = $this->entity->toUrl('drupal:content-translation-overview', $options)->toString();
    $this->drupalGet($translations_url);
    $this->assertSession()->statusCodeEquals($expected_status['overview']);

    // Check whether the user is allowed to create a translation.
    $add_translation_url = Url::fromRoute("entity.$this->entityTypeId.content_translation_add", [$this->entityTypeId => $this->entity->id(), 'source' => $default_langcode, 'target' => $langcode], $options);
    if ($expected_status['add_translation'] == 200) {
      $this->clickLink('Add');
      $this->assertSession()->addressEquals($add_translation_url);
      // Check that the translation form does not contain shared elements for
      // translators.
      if ($expected_status['edit'] == 403) {
        $this->assertNoSharedElements();
      }
    }
    else {
      $this->drupalGet($add_translation_url);
    }
    $this->assertSession()->statusCodeEquals($expected_status['add_translation']);

    // Check whether the user is allowed to edit a translation.
    $langcode = $this->langcodes[2];
    $options['language'] = $languages[$langcode];
    $edit_translation_url = Url::fromRoute("entity.$this->entityTypeId.content_translation_edit", [$this->entityTypeId => $this->entity->id(), 'language' => $langcode], $options);
    if ($expected_status['edit_translation'] == 200) {
      $this->drupalGet($translations_url);
      $editor = $expected_status['edit'] == 200;

      if ($editor) {
        $this->clickLink('Edit', 1);
        // An editor should be pointed to the entity form in multilingual mode.
        // We need a new expected edit path with a new language.
        $expected_edit_path = $this->entity->toUrl('edit-form', $options)->toString();
        $this->assertSession()->addressEquals($expected_edit_path);
      }
      else {
        $this->clickLink('Edit');
        // While a translator should be pointed to the translation form.
        $this->assertSession()->addressEquals($edit_translation_url);
        // Check that the translation form does not contain shared elements.
        $this->assertNoSharedElements();
      }
    }
    else {
      $this->drupalGet($edit_translation_url);
    }
    $this->assertSession()->statusCodeEquals($expected_status['edit_translation']);

    // When viewing an unpublished entity directly, access is currently denied
    // completely. See https://www.drupal.org/node/2978048.
    $this->drupalGet($this->entity->getTranslation($langcode)->toUrl());
    $this->assertSession()->statusCodeEquals($expected_status['view_unpublished_translation']);

    // On a reference field, the translation falls back to the default language.
    $this->drupalGet($this->referencingEntity->getTranslation($langcode)->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    if ($expected_status['view_unpublished_translation_reference']) {
      $this->assertSession()->pageTextContains('translation name');
    }
    else {
      $this->assertSession()->pageTextContains($this->entity->label());
    }

    // Check whether the user is allowed to delete a translation.
    $delete_translation_url = Url::fromRoute("entity.$this->entityTypeId.content_translation_delete", [$this->entityTypeId => $this->entity->id(), 'language' => $langcode], $options);
    if ($expected_status['delete_translation'] == 200) {
      $this->drupalGet($translations_url);
      $editor = $expected_status['delete'] == 200;

      if ($editor) {
        $this->clickLink('Delete', 1);
        // An editor should be pointed to the entity deletion form in
        // multilingual mode. We need a new expected delete path with a new
        // language.
        $expected_delete_path = $this->entity->toUrl('delete-form', $options)->toString();
        $this->assertSession()->addressEquals($expected_delete_path);
      }
      else {
        $this->clickLink('Delete');
        // While a translator should be pointed to the translation deletion
        // form.
        $this->assertSession()->addressEquals($delete_translation_url);
      }
    }
    else {
      $this->drupalGet($delete_translation_url);
    }
    $this->assertSession()->statusCodeEquals($expected_status['delete_translation']);
  }

  /**
   * Assert that the current page does not contain shared form elements.
   */
  protected function assertNoSharedElements() {
    $language_none = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    return $this->assertSession()->fieldNotExists("field_test_text[$language_none][0][value]");
  }

}
