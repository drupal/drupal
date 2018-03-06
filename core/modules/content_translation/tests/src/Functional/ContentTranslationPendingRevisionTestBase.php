<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Base class for pending revision translation tests.
 */
abstract class ContentTranslationPendingRevisionTestBase extends ContentTranslationTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'content_translation', 'content_moderation', 'node'];

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $storage;

  /**
   * Permissions common to all test accounts.
   *
   * @var string[]
   */
  protected $commonPermissions;

  /**
   * The current test account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentAccount;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityTypeId = 'node';
    $this->bundle = 'article';

    $this->commonPermissions = [
      'view any unpublished content',
      "translate {$this->bundle} {$this->entityTypeId}",
      "create content translations",
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archive',
      'use editorial transition archived_draft',
      'use editorial transition archived_published',
    ];

    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->storage = $entity_type_manager->getStorage($this->entityTypeId);

    // @todo Remove this line once https://www.drupal.org/node/2945928 is fixed.
    $this->config('node.settings')->set('use_admin_theme', '1')->save();
  }

  /**
   * Enables content moderation for the test entity type and bundle.
   */
  protected function enableContentModeration() {
    $this->drupalLogin($this->rootUser);
    $workflow_id = 'editorial';
    $this->drupalGet('/admin/config/workflow/workflows');
    $edit['bundles[' . $this->bundle . ']'] = TRUE;
    $this->drupalPostForm('admin/config/workflow/workflows/manage/' . $workflow_id . '/type/' . $this->entityTypeId, $edit, t('Save'));
    // Ensure the parent environment is up-to-date.
    // @see content_moderation_workflow_insert()
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    /** @var \Drupal\Core\Routing\RouteBuilderInterface $router_builder */
    $router_builder = $this->container->get('router.builder');
    $router_builder->rebuildIfNeeded();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions() {
    $editor_permissions = [
      "edit any {$this->bundle} content",
      "delete any {$this->bundle} content",
      "view {$this->bundle} revisions",
      "delete {$this->bundle} revisions",
    ];
    return array_merge($editor_permissions, $this->commonPermissions);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), $this->commonPermissions);
  }

  /**
   * {@inheritdoc}
   */
  protected function setupBundle() {
    parent::setupBundle();
    $this->createContentType(['type' => $this->bundle]);
  }

}
