<?php

declare(strict_types=1);

namespace Drupal\entity_test\Hook;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\entity_test\EntityTestHelper;
use Drupal\entity_test\Callbacks;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Hook implementations for entity_test.
 */
class EntityTestHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    $state = \Drupal::state();
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    foreach (EntityTestHelper::getEntityTypes() as $entity_type) {
      // Optionally specify a translation handler for testing translations.
      if ($state->get('entity_test.translation')) {
        $translation = $entity_types[$entity_type]->get('translation');
        $translation[$entity_type] = TRUE;
        $entity_types[$entity_type]->set('translation', $translation);
      }
    }
    // Allow entity_test_rev tests to override the entity type definition.
    $entity_types['entity_test_rev'] = $state->get('entity_test_rev.entity_type', $entity_types['entity_test_rev']);
    $entity_types['entity_test_revpub'] = $state->get('entity_test_revpub.entity_type', $entity_types['entity_test_revpub']);
    // Enable the entity_test_new only when needed.
    if (!$state->get('entity_test_new')) {
      unset($entity_types['entity_test_new']);
    }
    else {
      // Allow tests to override the entity type definition.
      $entity_types['entity_test_new'] = \Drupal::state()->get('entity_test_new.entity_type', $entity_types['entity_test_new']);
    }
    $entity_test_definition = $entity_types['entity_test'];
    $entity_test_definition->set('entity_keys', $state->get('entity_test.entity_keys', []) + $entity_test_definition->getKeys());
    // Allow tests to alter the permission granularity of entity_test_mul.
    $entity_types['entity_test_mul']->set('permission_granularity', \Drupal::state()->get('entity_test_mul.permission_granularity', 'entity_type'));
  }

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type): array {
    $fields = [];
    if ($entity_type->id() === 'entity_test' && \Drupal::state()->get('entity_test.internal_field')) {
      $fields['internal_string_field'] = BaseFieldDefinition::create('string')->setLabel('Internal field')->setInternal(TRUE);
    }
    if ($entity_type->id() === 'entity_test_mul' && \Drupal::state()->get('entity_test.required_default_field')) {
      $fields['required_default_field'] = BaseFieldDefinition::create('string')->setLabel('Required field with default value')->setRequired(TRUE)->setDefaultValue('this is a default value');
    }
    if ($entity_type->id() === 'entity_test_mul' && \Drupal::state()->get('entity_test.required_multi_default_field')) {
      $fields['required_multi_default_field'] = BaseFieldDefinition::create('string')->setLabel('Required field with default value')->setRequired(TRUE)->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)->setDefaultValue([
            [
              'value' => 'this is the first default field item',
            ],
            [
              'value' => 'this is the second default value',
            ],
            [
              'value' => 'you get the idea...',
            ],
      ]);
    }
    if ($entity_type->id() == 'entity_test_mulrev' && \Drupal::state()->get('entity_test.field_test_item')) {
      $fields['field_test_item'] = BaseFieldDefinition::create('field_test')->setLabel($this->t('Field test'))->setDescription($this->t('A field test.'))->setRevisionable(TRUE)->setTranslatable(TRUE);
    }
    if ($entity_type->id() == 'entity_test_mulrev' && \Drupal::state()->get('entity_test.multi_column')) {
      $fields['description'] = BaseFieldDefinition::create('shape')->setLabel($this->t('Some custom description'))->setTranslatable(TRUE);
    }
    return $fields;
  }

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(&$fields, EntityTypeInterface $entity_type): void {
    $state = \Drupal::state();
    if ($entity_type->id() == 'entity_test_mulrev' && ($names = $state->get('entity_test.field_definitions.translatable'))) {
      foreach ($names as $name => $value) {
        $fields[$name]->setTranslatable($value);
      }
    }
    if ($entity_type->id() == 'node' && $state->get('entity_test.node_remove_status_field')) {
      unset($fields['status']);
    }
    if ($entity_type->id() == 'entity_test' && $state->get('entity_test.remove_name_field')) {
      unset($fields['name']);
    }
    // In 8001 we are assuming that a new definition with multiple cardinality
    // has been deployed.
    // @todo Remove this if we end up using state definitions at runtime. See
    //   https://www.drupal.org/node/2554235.
    if ($entity_type->id() == 'entity_test' && $state->get('entity_test.db_updates.entity_definition_updates') == 8001) {
      $fields['user_id']->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    }
  }

  /**
   * Implements hook_entity_bundle_info().
   *
   * @see \Drupal\entity_test\EntityTestHelper::createBundle()
   * @see \Drupal\entity_test\EntityTestHelper::deleteBundle()
   */
  #[Hook('entity_bundle_info')]
  public function entityBundleInfo(): array {
    $bundles = [];
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type->getProvider() == 'entity_test'
        && !in_array($entity_type_id, ['entity_test_with_bundle', 'entity_test_mul_with_bundle'], TRUE)) {
        $bundles[$entity_type_id] = \Drupal::state()->get($entity_type_id . '.bundles', [$entity_type_id => ['label' => 'Entity Test Bundle']]);
      }
    }
    return $bundles;
  }

  /**
   * Implements hook_entity_bundle_info_alter().
   */
  #[Hook('entity_bundle_info_alter')]
  public function entityBundleInfoAlter(&$bundles): void {
    $entity_info = \Drupal::entityTypeManager()->getDefinitions();
    $state = \Drupal::state();
    foreach ($bundles as $entity_type_id => &$all_bundle_info) {
      if ($entity_info[$entity_type_id]->getProvider() == 'entity_test') {
        if ($state->get('entity_test.translation') && $entity_info[$entity_type_id]->isTranslatable()) {
          foreach ($all_bundle_info as &$bundle_info) {
            $bundle_info['translatable'] = TRUE;
            if ($state->get('entity_test.untranslatable_fields.default_translation_affected')) {
              $bundle_info['untranslatable_fields.default_translation_affected'] = TRUE;
            }
          }
        }
      }
    }
  }

  /**
   * Implements hook_entity_view_mode_info_alter().
   */
  #[Hook('entity_view_mode_info_alter')]
  public function entityViewModeInfoAlter(&$view_modes): void {
    $entity_info = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($entity_info as $entity_type => $info) {
      if ($entity_info[$entity_type]->getProvider() == 'entity_test' && !isset($view_modes[$entity_type])) {
        $view_modes[$entity_type] = [
          'full' => [
            'label' => $this->t('Full object'),
            'status' => TRUE,
            'cache' => TRUE,
          ],
          'teaser' => [
            'label' => $this->t('Teaser'),
            'status' => TRUE,
            'cache' => TRUE,
          ],
        ];
      }
    }
  }

  /**
   * Implements hook_entity_form_mode_info_alter().
   */
  #[Hook('entity_form_mode_info_alter')]
  public function entityFormModeInfoAlter(&$form_modes): void {
    $entity_info = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($entity_info as $entity_type => $info) {
      if ($entity_info[$entity_type]->getProvider() == 'entity_test') {
        $form_modes[$entity_type]['compact'] = ['label' => $this->t('Compact version'), 'status' => TRUE];
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_view_mode_alter().
   */
  #[Hook('entity_test_view_mode_alter')]
  public function entityTestViewModeAlter(string &$view_mode, EntityInterface $entity) : void {
    if ($view_mode == 'entity_test.vm_alter_test') {
      $view_mode = 'entity_test.vm_alter_full';
    }
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $extra['entity_test']['bundle_with_extra_fields'] = [
      'display' => [
        // Note: those extra fields do not currently display anything, they are
        // just used in \Drupal\Tests\field_ui\Kernel\EntityDisplayTest to test
        // the behavior of entity display objects.
        'display_extra_field' => [
          'label' => $this->t('Display extra field'),
          'description' => $this->t('An extra field on the display side.'),
          'weight' => 5,
          'visible' => TRUE,
        ],
        'display_extra_field_hidden' => [
          'label' => $this->t('Display extra field (hidden)'),
          'description' => $this->t('An extra field on the display side, hidden by default.'),
          'visible' => FALSE,
        ],
      ],
    ];
    return $extra;
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_entity_test_form_alter')]
  public function formEntityTestFormAlter(&$form) : void {
    switch (\Drupal::state()->get('entity_test.form.validate.test')) {
      case 'form-level':
        $form['#validate'][] = [Callbacks::class, 'entityTestFormValidate'];
        $form['#validate'][] = [Callbacks::class, 'entityTestFormValidateCheck'];
        break;

      case 'button-level':
        $form['actions']['submit']['#validate'][] = [Callbacks::class, 'entityTestFormValidateCheck'];
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    $langcode = $form_state->getFormObject()->getFormLangcode($form_state);
    \Drupal::state()->set('entity_test.form_langcode', $langcode);
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for 'entity_test'.
   */
  #[Hook('entity_test_insert')]
  public function entityTestInsert($entity): void {
    if ($entity->name->value == 'fail_insert') {
      throw new \Exception("Test exception rollback.");
    }
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() == 'entity_test_mulrev' && $entity->label() == 'EntityLoadedRevisionTest') {
      $entity->setNewRevision(FALSE);
      $entity->save();
    }
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    if ($entity instanceof ContentEntityInterface) {
      \Drupal::state()->set('entity_test.loadedRevisionId', $entity->getLoadedRevisionId());
    }
  }

  /**
   * Implements hook_entity_field_access().
   *
   * @see \Drupal\system\Tests\Entity\FieldAccessTest::testFieldAccess()
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    if ($field_definition->getName() == 'field_test_text') {
      if ($items) {
        if ($items->value == 'no access value') {
          return AccessResult::forbidden()->addCacheableDependency($items->getEntity());
        }
        elseif ($items->value == 'custom cache tag value') {
          return AccessResult::allowed()->addCacheableDependency($items->getEntity())->addCacheTags(['entity_test_access:field_test_text']);
        }
        elseif ($operation == 'edit' && $items->value == 'no edit access value') {
          return AccessResult::forbidden()->addCacheableDependency($items->getEntity());
        }
      }
    }
    if ($field = \Drupal::state()->get('views_field_access_test-field')) {
      if ($field_definition->getName() === $field) {
        $result = AccessResult::allowedIfHasPermission($account, 'view test entity field');
        // For test purposes we want to actively deny access.
        if ($result->isNeutral()) {
          $result = AccessResult::forbidden();
        }
        return $result;
      }
    }
    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * Implements hook_entity_field_access_alter().
   *
   * @see \Drupal\system\Tests\Entity\FieldAccessTest::testFieldAccess()
   */
  #[Hook('entity_field_access_alter')]
  public function entityFieldAccessAlter(array &$grants, array $context): void {
    if ($context['field_definition']->getName() == 'field_test_text' && $context['items']->value == 'access alter value') {
      $grants[':default'] = AccessResult::forbidden()->inheritCacheability($grants[':default'])->addCacheableDependency($context['items']->getEntity());
    }
  }

  /**
   * Implements hook_entity_form_mode_alter().
   */
  #[Hook('entity_form_mode_alter')]
  public function entityFormModeAlter(&$form_mode, EntityInterface $entity): void {
    if ($entity->getEntityTypeId() === 'entity_test' && $entity->get('name')->value === 'compact_form_mode') {
      $form_mode = 'compact';
    }
  }

  /**
   * Implements hook_entity_form_display_alter().
   */
  #[Hook('entity_form_display_alter')]
  public function entityFormDisplayAlter(EntityFormDisplay $form_display, $context): void {
    // Make the field_test_text field 42 characters for entity_test_mul.
    if ($context['entity_type'] == 'entity_test') {
      if ($component_options = $form_display->getComponent('field_test_text')) {
        $component_options['settings']['size'] = 42;
        $form_display->setComponent('field_test_text', $component_options);
      }
    }
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    if (isset($GLOBALS['entity_test_throw_exception'])) {
      throw new \Exception('Entity presave exception', 1);
    }
    if ($entity->getEntityType()->id() == 'entity_view_display') {
      $entity->setThirdPartySetting('entity_test', 'foo', 'bar');
    }
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    if (isset($GLOBALS['entity_test_throw_exception'])) {
      throw new \Exception('Entity predelete exception', 2);
    }
  }

  /**
   * Implements hook_entity_operation_alter().
   */
  #[Hook('entity_operation_alter')]
  public function entityOperationAlter(array &$operations, EntityInterface $entity): void {
    $valid_entity_type_ids = ['user_role', 'block'];
    if (in_array($entity->getEntityTypeId(), $valid_entity_type_ids)) {
      if (\Drupal::service('router.route_provider')->getRouteByName("entity.{$entity->getEntityTypeId()}.test_operation")) {
        $operations['test_operation'] = [
          'title' => new FormattableMarkup('Test Operation: @label', [
            '@label' => $entity->label(),
          ]),
          'url' => Url::fromRoute("entity.{$entity->getEntityTypeId()}.test_operation", [
            $entity->getEntityTypeId() => $entity->id(),
          ]),
          'weight' => 50,
        ];
      }
    }
  }

  /**
   * Implements hook_entity_translation_create().
   */
  #[Hook('entity_translation_create')]
  public function entityTranslationCreate(EntityInterface $translation): void {
    $this->recordHooks('entity_translation_create', $translation->language()->getId());
  }

  /**
   * Implements hook_entity_translation_insert().
   */
  #[Hook('entity_translation_insert')]
  public function entityTranslationInsert(EntityInterface $translation): void {
    $this->recordHooks('entity_translation_insert', $translation->language()->getId());
  }

  /**
   * Implements hook_entity_translation_delete().
   */
  #[Hook('entity_translation_delete')]
  public function entityTranslationDelete(EntityInterface $translation): void {
    $this->recordHooks('entity_translation_delete', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_create() for 'entity_test_mul'.
   */
  #[Hook('entity_test_mul_translation_create')]
  public function entityTestMulTranslationCreate(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mul_translation_create', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_insert() for 'entity_test_mul'.
   */
  #[Hook('entity_test_mul_translation_insert')]
  public function entityTestMulTranslationInsert(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mul_translation_insert', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_delete() for 'entity_test_mul'.
   */
  #[Hook('entity_test_mul_translation_delete')]
  public function entityTestMulTranslationDelete(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mul_translation_delete', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_create() for 'entity_test_mul_changed'.
   */
  #[Hook('entity_test_mul_changed_translation_create')]
  public function entityTestMulChangedTranslationCreate(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mul_changed_translation_create', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_insert() for 'entity_test_mul_changed'.
   */
  #[Hook('entity_test_mul_changed_translation_insert')]
  public function entityTestMulChangedTranslationInsert(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mul_changed_translation_insert', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_delete().
   */
  #[Hook('entity_test_mul_changed_translation_delete')]
  public function entityTestMulChangedTranslationDelete(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mul_changed_translation_delete', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_create().
   */
  #[Hook('entity_test_mulrev_translation_create')]
  public function entityTestMulrevTranslationCreate(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mulrev_translation_create', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_insert().
   */
  #[Hook('entity_test_mulrev_translation_insert')]
  public function entityTestMulrevTranslationInsert(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mulrev_translation_insert', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_delete() for 'entity_test_mulrev'.
   */
  #[Hook('entity_test_mulrev_translation_delete')]
  public function entityTestMulrevTranslationDelete(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mulrev_translation_delete', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_create() for 'entity_test_mulrev_changed'.
   */
  #[Hook('entity_test_mulrev_changed_translation_create')]
  public function entityTestMulrevChangedTranslationCreate(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mulrev_changed_translation_create', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_insert() for 'entity_test_mulrev'.
   */
  #[Hook('entity_test_mulrev_changed_translation_insert')]
  public function entityTestMulrevChangedTranslationInsert(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mulrev_changed_translation_insert', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_delete().
   */
  #[Hook('entity_test_mulrev_changed_translation_delete')]
  public function entityTestMulrevChangedTranslationDelete(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mulrev_changed_translation_delete', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_create() for 'entity_test_mul_langcode_key'.
   */
  #[Hook('entity_test_mul_langcode_key_translation_create')]
  public function entityTestMulLangcodeKeyTranslationCreate(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mul_langcode_key_translation_create', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_insert() for 'entity_test_mul_langcode_key'.
   */
  #[Hook('entity_test_mul_langcode_key_translation_insert')]
  public function entityTestMulLangcodeKeyTranslationInsert(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mul_langcode_key_translation_insert', $translation->language()->getId());
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_delete() for 'entity_test_mul_langcode_key'.
   */
  #[Hook('entity_test_mul_langcode_key_translation_delete')]
  public function entityTestMulLangcodeKeyTranslationDelete(EntityInterface $translation): void {
    $this->recordHooks('entity_test_mul_langcode_key_translation_delete', $translation->language()->getId());
  }

  /**
   * Implements hook_entity_revision_create().
   */
  #[Hook('entity_revision_create')]
  public function entityRevisionCreate(EntityInterface $new_revision, EntityInterface $entity, $keep_untranslatable_fields): void {
    $this->recordHooks('entity_revision_create', [
      'new_revision' => $new_revision,
      'entity' => $entity,
      'keep_untranslatable_fields' => $keep_untranslatable_fields,
    ]);
  }

  /**
   * Implements hook_ENTITY_TYPE_revision_create() for 'entity_test_mulrev'.
   */
  #[Hook('entity_test_mulrev_revision_create')]
  public function entityTestMulrevRevisionCreate(EntityInterface $new_revision, EntityInterface $entity, $keep_untranslatable_fields): void {
    if ($new_revision->get('name')->value == 'revision_create_test_it') {
      $new_revision->set('name', 'revision_create_test_it_altered');
    }
    $this->recordHooks('entity_test_mulrev_revision_create', [
      'new_revision' => $new_revision,
      'entity' => $entity,
      'keep_untranslatable_fields' => $keep_untranslatable_fields,
    ]);
  }

  /**
   * Implements hook_entity_prepare_view().
   */
  #[Hook('entity_prepare_view')]
  public function entityPrepareView($entity_type, array $entities, array $displays): void {
    if ($entity_type == 'entity_test') {
      foreach ($entities as $entity) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        // Add field item attributes on field_test_text if it exists.
        // See \Drupal\Tests\system\Functional\Entity\EntityViewControllerTest::testFieldItemAttributes().
        if ($entity->hasField('field_test_text') && $displays[$entity->bundle()]->getComponent('field_test_text')) {
          foreach ($entity->get('field_test_text') as $item) {
            $item->_attributes += ['data-field-item-attr' => 'foobar', 'property' => 'schema:text'];
          }
        }
        // Add an item attribute on daterange fields if they exist.
        $fields = $entity->getFieldDefinitions();
        foreach ($fields as $field) {
          if ($field->getType() === 'daterange') {
            $item = $entity->get($field->getName());
            $item->_attributes += ['data-field-item-attr' => 'foobar'];
          }
        }
      }
    }
  }

  /**
   * Implements hook_entity_display_build_alter().
   */
  #[Hook('entity_display_build_alter')]
  public function entityDisplayBuildAlter(&$build, $context): void {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $context['entity'];
    if ($entity->getEntityTypeId() == 'entity_test' && $entity->bundle() == 'display_build_alter_bundle') {
      $build['entity_display_build_alter']['#markup'] = 'Content added in hook_entity_display_build_alter for entity id ' . $entity->id();
    }
  }

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // Only apply to the 'entity_test' entities.
    if ($entity->getEntityType()->getProvider() != 'entity_test') {
      return AccessResult::neutral();
    }
    \Drupal::state()->set('entity_test_entity_access', TRUE);
    // Attempt to allow access to entities with the title forbid_access,
    // this will be overridden by
    // \Drupal\entity_test\EntityTestAccessControlHandler::checkAccess().
    if ($entity->label() == 'forbid_access') {
      return AccessResult::allowed();
    }
    // Create specific labels to allow or deny access based on certain test
    // conditions.
    // @see \Drupal\KernelTests\Core\Entity\EntityAccessControlHandlerTest
    if ($entity->label() == 'Accessible') {
      return AccessResult::allowed();
    }
    if ($entity->label() == 'Inaccessible') {
      return AccessResult::forbidden();
    }
    // Uncacheable because the access result depends on a State key-value pair and
    // might therefore change at any time.
    $condition = \Drupal::state()->get("entity_test_entity_access.{$operation}." . $entity->id(), FALSE);
    return AccessResult::allowedIf($condition)->setCacheMaxAge(0);
  }

  /**
   * Implements hook_ENTITY_TYPE_access() for 'entity_test'.
   */
  #[Hook('entity_test_access')]
  public function entityTestAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    \Drupal::state()->set('entity_test_entity_test_access', TRUE);
    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * Implements hook_entity_create_access().
   */
  #[Hook('entity_create_access')]
  public function entityCreateAccess(AccountInterface $account, $context, $entity_bundle): AccessResultInterface {
    \Drupal::state()->set('entity_test_entity_create_access', TRUE);
    \Drupal::state()->set('entity_test_entity_create_access_context', $context);
    if ($entity_bundle === 'forbidden_access_bundle') {
      // We need to cover a case in which a bundle is specifically forbidden
      // from creation (as opposed to neutral access).
      return AccessResult::forbidden();
    }
    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * Implements hook_ENTITY_TYPE_create_access() for 'entity_test'.
   */
  #[Hook('entity_test_create_access')]
  public function entityTestCreateAccess(AccountInterface $account, $context, $entity_bundle): AccessResultInterface {
    \Drupal::state()->set('entity_test_entity_test_create_access', TRUE);
    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * Implements hook_query_entity_test_access_alter().
   */
  #[Hook('query_entity_test_access_alter')]
  public function queryEntityTestAccessAlter(AlterableInterface $query): void {
    if (!\Drupal::state()->get('entity_test_query_access')) {
      return;
    }
    /** @var \Drupal\Core\Database\Query\Select|\Drupal\Core\Database\Query\AlterableInterface $query */
    if (!\Drupal::currentUser()->hasPermission('view all entity_test_query_access entities')) {
      $query->condition('entity_test_query_access.name', 'published entity');
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_form_mode_alter().
   */
  #[Hook('entity_test_form_mode_alter')]
  public function entityTestFormModeAlter(&$form_mode, EntityInterface $entity) : void {
    if ($entity->getEntityTypeId() === 'entity_test' && $entity->get('name')->value === 'test_entity_type_form_mode_alter') {
      $form_mode = 'compact';
    }
  }

  /**
   * Implements hook_entity_duplicate().
   */
  #[Hook('entity_duplicate')]
  public function entityDuplicateAlter(EntityInterface $duplicate, EntityInterface $entity) : void {
    if ($duplicate instanceof ContentEntityInterface && str_contains($duplicate->label(), 'UUID CRUD test entity') && $duplicate->hasField('name')) {
      $duplicate->set('name', $duplicate->label() . ' duplicate');
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_duplicate().
   */
  #[Hook('entity_test_duplicate')]
  public function entityTestDuplicate(EntityTest $duplicate, EntityTest $entity) : void {
    if (str_contains($duplicate->label(), 'UUID CRUD test entity') && $duplicate->hasField('name')) {
      $duplicate->set('name', 'prefix ' . $duplicate->label());
    }
  }

  /**
   * Helper function to be used to record hook invocations.
   *
   * @param string $hook
   *   The hook name.
   * @param mixed $data
   *   Arbitrary data associated with the hook invocation.
   */
  protected function recordHooks($hook, $data): void {
    $state = \Drupal::state();
    $key = 'entity_test.hooks';
    $hooks = $state->get($key);
    $hooks[$hook] = $data;
    $state->set($key, $hooks);
  }

}
