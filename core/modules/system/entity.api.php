<?php

/**
 * @file
 * Hooks provided the Entity module.
 */

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Control entity operation access.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to check access to.
 * @param string $operation
 *   The operation that is to be performed on $entity.
 * @param \Drupal\Core\Session\AccountInterface $account
 *    The account trying to access the entity.
 * @param string $langcode
 *    The code of the language $entity is accessed in.
 *
 * @return bool|null
 *   A boolean to explicitly allow or deny access, or NULL to neither allow nor
 *   deny access.
 *
 * @see \Drupal\Core\Entity\EntityAccessController
 */
function hook_entity_access(\Drupal\Core\Entity\EntityInterface $entity, $operation, \Drupal\Core\Session\AccountInterface $account, $langcode) {
  return NULL;
}

/**
 * Control entity operation access for a specific entity type.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to check access to.
 * @param string $operation
 *   The operation that is to be performed on $entity.
 * @param \Drupal\Core\Session\AccountInterface $account
 *    The account trying to access the entity.
 * @param string $langcode
 *    The code of the language $entity is accessed in.
 *
 * @return bool|null
 *   A boolean to explicitly allow or deny access, or NULL to neither allow nor
 *   deny access.
 *
 * @see \Drupal\Core\Entity\EntityAccessController
 */
function hook_ENTITY_TYPE_access(\Drupal\Core\Entity\EntityInterface $entity, $operation, \Drupal\Core\Session\AccountInterface $account, $langcode) {
  return NULL;
}

/**
 * Control entity create access.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *    The account trying to access the entity.
 * @param string $langcode
 *    The code of the language $entity is accessed in.
 *
 * @return bool|null
 *   A boolean to explicitly allow or deny access, or NULL to neither allow nor
 *   deny access.
 *
 * @see \Drupal\Core\Entity\EntityAccessController
 */
function hook_entity_create_access(\Drupal\Core\Session\AccountInterface $account, $langcode) {
  return NULL;
}

/**
 * Control entity create access for a specific entity type.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *    The account trying to access the entity.
 * @param string $langcode
 *    The code of the language $entity is accessed in.
 *
 * @return bool|null
 *   A boolean to explicitly allow or deny access, or NULL to neither allow nor
 *   deny access.
 *
 * @see \Drupal\Core\Entity\EntityAccessController
 */
function hook_ENTITY_TYPE_create_access(\Drupal\Core\Session\AccountInterface $account, $langcode) {
  return NULL;
}

/**
 * Add to entity type definitions.
 *
 * Modules may implement this hook to add information to defined entity types.
 *
 * @param array $entity_info
 *   An associative array of all entity type definitions, keyed by the entity
 *   type name. Passed by reference.
 *
 * @see \Drupal\Core\Entity\Entity
 * @see \Drupal\Core\Entity\EntityManager
 * @see entity_get_info()
 */
function hook_entity_info(&$entity_info) {
  // Add a form controller for a custom node form without overriding the default
  // node form. To override the default node form, use hook_entity_info_alter()
  // to alter $entity_info['node']['controllers']['form']['default'].
  $entity_info['node']['controllers']['form']['mymodule_foo'] = 'Drupal\mymodule\NodeFooFormController';
}

/**
 * Alter the view modes for entity types.
 *
 * @param array $view_modes
 *   An array of view modes, keyed first by entity type, then by view mode name.
 *
 * @see entity_get_view_modes()
 * @see hook_entity_view_mode_info()
 */
function hook_entity_view_mode_info_alter(&$view_modes) {
  $view_modes['user']['full']['status'] = TRUE;
}

/**
 * Describe the bundles for entity types.
 *
 * @return array
 *   An associative array of all entity bundles, keyed by the entity
 *   type name, and then the bundle name, with the following keys:
 *   - label: The human-readable name of the bundle.
 *   - uri_callback: The same as the 'uri_callback' key defined for the entity
 *     type in the EntityManager, but for the bundle only. When determining
 *     the URI of an entity, if a 'uri_callback' is defined for both the
 *     entity type and the bundle, the one for the bundle is used.
 *   - translatable: (optional) A boolean value specifying whether this bundle
 *     has translation support enabled. Defaults to FALSE.
 *
 * @see entity_get_bundles()
 * @see hook_entity_bundle_info_alter()
 */
function hook_entity_bundle_info() {
  $bundles['user']['user']['label'] = t('User');
  return $bundles;
}

/**
 * Alter the bundles for entity types.
 *
 * @param array $bundles
 *   An array of bundles, keyed first by entity type, then by bundle name.
 *
 * @see entity_get_bundles()
 * @see hook_entity_bundle_info()
 */
function hook_entity_bundle_info_alter(&$bundles) {
  $bundles['user']['user']['label'] = t('Full account');
}

/**
 * Act on entity_bundle_create().
 *
 * This hook is invoked after the operation has been performed.
 *
 * @param string $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param string $bundle
 *   The name of the bundle.
 */
function hook_entity_bundle_create($entity_type, $bundle) {
  // When a new bundle is created, the menu needs to be rebuilt to add the
  // Field UI menu item tabs.
  \Drupal::state()->set('menu_rebuild_needed', TRUE);
}

/**
 * Act on entity_bundle_rename().
 *
 * This hook is invoked after the operation has been performed.
 *
 * @param string $entity_type
 *   The entity type to which the bundle is bound.
 * @param string $bundle_old
 *   The previous name of the bundle.
 * @param string $bundle_new
 *   The new name of the bundle.
 */
function hook_entity_bundle_rename($entity_type, $bundle_old, $bundle_new) {
  // Update the settings associated with the bundle in my_module.settings.
  $config = \Drupal::config('my_module.settings');
  $bundle_settings = $config->get('bundle_settings');
  if (isset($bundle_settings[$entity_type][$bundle_old])) {
    $bundle_settings[$entity_type][$bundle_new] = $bundle_settings[$entity_type][$bundle_old];
    unset($bundle_settings[$entity_type][$bundle_old]);
    $config->set('bundle_settings', $bundle_settings);
  }
}

/**
 * Act on entity_bundle_delete().
 *
 * This hook is invoked after the operation has been performed.
 *
 * @param string $entity_type
 *   The type of entity; for example, 'node' or 'user'.
 * @param string $bundle
 *   The bundle that was just deleted.
 */
function hook_entity_bundle_delete($entity_type, $bundle) {
  // Remove the settings associated with the bundle in my_module.settings.
  $config = \Drupal::config('my_module.settings');
  $bundle_settings = $config->get('bundle_settings');
  if (isset($bundle_settings[$entity_type][$bundle])) {
    unset($bundle_settings[$entity_type][$bundle]);
    $config->set('bundle_settings', $bundle_settings);
  }
}

/**
 * Alter the entity type definitions.
 *
 * Modules may implement this hook to alter the information that defines an
 * entity type. All properties that are available in
 * \Drupal\Core\Entity\EntityManager can be altered here.
 *
 * Do not use this hook to add information to entity types. Use
 * hook_entity_info() for that instead.
 *
 * @param array $entity_info
 *   An associative array of all entity type definitions, keyed by the entity
 *   type name. Passed by reference.
 *
 * @see \Drupal\Core\Entity\Entity
 * @see \Drupal\Core\Entity\EntityManager
 * @see entity_get_info()
 */
function hook_entity_info_alter(&$entity_info) {
  // Set the controller class for nodes to an alternate implementation of the
  // Drupal\Core\Entity\EntityStorageControllerInterface interface.
  $entity_info['node']['controllers']['storage'] = 'Drupal\mymodule\MyCustomNodeStorageController';
}

/**
 * Act on a newly created entity.
 *
 * This hook runs after a new entity object has just been instantiated. It can
 * be used to set initial values, e.g. to provide defaults.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 */
function hook_entity_create(\Drupal\Core\Entity\EntityInterface $entity) {
  if ($entity instanceof ContentEntityInterface && !$entity->foo->value) {
    $entity->foo->value = 'some_initial_value';
  }
}

/**
 * Act on entities when loaded.
 *
 * This is a generic load hook called for all entity types loaded via the
 * entity API.
 *
 * @param array $entities
 *   The entities keyed by entity ID.
 * @param string $entity_type
 *   The type of entities being loaded (i.e. node, user, comment).
 */
function hook_entity_load($entities, $entity_type) {
  foreach ($entities as $entity) {
    $entity->foo = mymodule_add_something($entity);
  }
}

/**
 * Act on an entity before it is about to be created or updated.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 */
function hook_entity_presave(Drupal\Core\Entity\EntityInterface $entity) {
  $entity->changed = REQUEST_TIME;
}

/**
 * Act on entities when inserted.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 */
function hook_entity_insert(Drupal\Core\Entity\EntityInterface $entity) {
  // Insert the new entity into a fictional table of all entities.
  db_insert('example_entity')
    ->fields(array(
      'type' => $entity->entityType(),
      'id' => $entity->id(),
      'created' => REQUEST_TIME,
      'updated' => REQUEST_TIME,
    ))
    ->execute();
}

/**
 * Act on entities when updated.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 */
function hook_entity_update(Drupal\Core\Entity\EntityInterface $entity) {
  // Update the entity's entry in a fictional table of all entities.
  db_update('example_entity')
    ->fields(array(
      'updated' => REQUEST_TIME,
    ))
    ->condition('type', $entity->entityType())
    ->condition('id', $entity->id())
    ->execute();
}

/**
 * Acts after storing a new entity translation.
 *
 * @param \Drupal\Core\Entity\EntityInterface $translation
 *   The entity object of the translation just stored.
 */
function hook_entity_translation_insert(\Drupal\Core\Entity\EntityInterface $translation) {
  $variables = array(
    '@language' => $translation->language()->name,
    '@label' => $translation->getUntranslated()->label(),
  );
  watchdog('example', 'The @language translation of @label has just been stored.', $variables);
}

/**
 * Acts after deleting an entity translation from the storage.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The original entity object.
 */
function hook_entity_translation_delete(\Drupal\Core\Entity\EntityInterface $translation) {
  $languages = language_list();
  $variables = array(
    '@language' => $languages[$langcode]->name,
    '@label' => $entity->label(),
  );
  watchdog('example', 'The @language translation of @label has just been deleted.', $variables);
}

/**
 * Act before entity deletion.
 *
 * This hook runs after the entity type-specific predelete hook.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity object for the entity that is about to be deleted.
 */
function hook_entity_predelete(Drupal\Core\Entity\EntityInterface $entity) {
  // Count references to this entity in a custom table before they are removed
  // upon entity deletion.
  $id = $entity->id();
  $type = $entity->entityType();
  $count = db_select('example_entity_data')
    ->condition('type', $type)
    ->condition('id', $id)
    ->countQuery()
    ->execute()
    ->fetchField();

  // Log the count in a table that records this statistic for deleted entities.
  $ref_count_record = (object) array(
    'count' => $count,
    'type' => $type,
    'id' => $id,
  );
  drupal_write_record('example_deleted_entity_statistics', $ref_count_record);
}

/**
 * Respond to entity deletion.
 *
 * This hook runs after the entity type-specific delete hook.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity object for the entity that has been deleted.
 */
function hook_entity_delete(Drupal\Core\Entity\EntityInterface $entity) {
  // Delete the entity's entry from a fictional table of all entities.
  db_delete('example_entity')
    ->condition('type', $entity->entityType())
    ->condition('id', $entity->id())
    ->execute();
}

/**
 * Respond to entity revision deletion.
 *
 * This hook runs after the entity type-specific revision delete hook.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity object for the entity revision that has been deleted.
 */
function hook_entity_revision_delete(Drupal\Core\Entity\EntityInterface $entity) {
  // @todo: code example
}

/**
 * Alter or execute an Drupal\Core\Entity\Query\EntityQueryInterface.
 *
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   Note the $query->altered attribute which is TRUE in case the query has
 *   already been altered once. This happens with cloned queries.
 *   If there is a pager, then such a cloned query will be executed to count
 *   all elements. This query can be detected by checking for
 *   ($query->pager && $query->count), allowing the driver to return 0 from
 *   the count query and disable the pager.
 */
function hook_entity_query_alter(\Drupal\Core\Entity\Query\QueryInterface $query) {
  // @todo: code example.
}

/**
 * Act on entities being assembled before rendering.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 * @param \Drupal\entity\Entity\EntityDisplay $display
 *   The entity_display object holding the display options configured for the
 *   entity components.
 * @param $view_mode
 *   The view mode the entity is rendered in.
 * @param $langcode
 *   The language code used for rendering.
 *
 * The module may add elements to $entity->content prior to rendering. The
 * structure of $entity->content is a renderable array as expected by
 * drupal_render().
 *
 * @see hook_entity_view_alter()
 * @see hook_comment_view()
 * @see hook_node_view()
 * @see hook_user_view()
 */
function hook_entity_view(\Drupal\Core\Entity\EntityInterface $entity, \Drupal\entity\Entity\EntityDisplay $display, $view_mode, $langcode) {
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a 'mymodule_addition' extra field has been defined for the
  // entity bundle in hook_field_extra_fields().
  if ($display->getComponent('mymodule_addition')) {
    $entity->content['mymodule_addition'] = array(
      '#markup' => mymodule_addition($entity),
      '#theme' => 'mymodule_my_additional_field',
    );
  }
}

/**
 * Alter the results of ENTITY_view().
 *
 * This hook is called after the content has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * entity content structure has been built.
 *
 * If a module wishes to act on the rendered HTML of the entity rather than the
 * structured content array, it may use this hook to add a #post_render
 * callback. Alternatively, it could also implement hook_preprocess_HOOK() for
 * the particular entity type template, if there is one (e.g., node.html.twig).
 * See drupal_render() and theme() for details.
 *
 * @param $build
 *   A renderable array representing the entity content.
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity object being rendered.
 * @param \Drupal\entity\Entity\EntityDisplay $display
 *   The entity_display object holding the display options configured for the
 *   entity components.
 *
 * @see hook_entity_view()
 * @see hook_comment_view_alter()
 * @see hook_node_view_alter()
 * @see hook_taxonomy_term_view_alter()
 * @see hook_user_view_alter()
 */
function hook_entity_view_alter(&$build, Drupal\Core\Entity\EntityInterface $entity, \Drupal\entity\Entity\EntityDisplay $display) {
  if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;

    // Add a #post_render callback to act on the rendered HTML of the entity.
    $build['#post_render'][] = 'my_module_node_post_render';
  }
}

/**
 * Act on entities as they are being prepared for view.
 *
 * Allows you to operate on multiple entities as they are being prepared for
 * view. Only use this if attaching the data during the entity loading phase
 * is not appropriate, for example when attaching other 'entity' style objects.
 *
 * @param string $entity_type
 *   The type of entities being viewed (i.e. node, user, comment).
 * @param array $entities
 *   The entities keyed by entity ID.
 * @param array $display
 *   The array of entity_display objects holding the display options configured
 *   for the entity components, keyed by bundle name.
 * @param string $view_mode
 *   The view mode.
 */
function hook_entity_prepare_view($entity_type, array $entities, array $displays, $view_mode) {
  // Load a specific node into the user object for later theming.
  if (!empty($entities) && $entity_type == 'user') {
    // Only do the extra work if the component is configured to be
    // displayed. This assumes a 'mymodule_addition' extra field has been
    // defined for the entity bundle in hook_field_extra_fields().
    $ids = array();
    foreach ($entities as $id => $entity) {
      if ($displays[$entity->bundle()]->getComponent('mymodule_addition')) {
        $ids[] = $id;
      }
    }
    if ($ids) {
      $nodes = mymodule_get_user_nodes($ids);
      foreach ($ids as $id) {
        $entities[$id]->user_node = $nodes[$id];
      }
    }
  }
}

/**
 * Change the view mode of an entity that is being displayed.
 *
 * @param string $view_mode
 *   The view_mode that is to be used to display the entity.
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity that is being viewed.
 * @param array $context
 *   Array with additional context information, currently only contains the
 *   langcode the entity is viewed in.
 */
function hook_entity_view_mode_alter(&$view_mode, Drupal\Core\Entity\EntityInterface $entity, $context) {
  // For nodes, change the view mode when it is teaser.
  if ($entity->entityType() == 'node' && $view_mode == 'teaser') {
    $view_mode = 'my_custom_view_mode';
  }
}

/**
 * Alters the settings used for displaying an entity.
 *
 * @param \Drupal\entity\Entity\EntityDisplay $display
 *   The entity_display object that will be used to display the entity
 *   components.
 * @param array $context
 *   An associative array containing:
 *   - entity_type: The entity type, e.g., 'node' or 'user'.
 *   - bundle: The bundle, e.g., 'page' or 'article'.
 *   - view_mode: The view mode, e.g. 'full', 'teaser'...
 */
function hook_entity_display_alter(\Drupal\entity\Entity\EntityDisplay $display, array $context) {
  // Leave field labels out of the search index.
  if ($context['entity_type'] == 'node' && $context['view_mode'] == 'search_index') {
    foreach ($display->getComponents() as $name => $options) {
      if (isset($options['label'])) {
        $options['label'] = 'hidden';
        $display->setComponent($name, $options);
      }
    }
  }
}

/**
 * Acts on an entity object about to be shown on an entity form.
 *
 * This can be typically used to pre-fill entity values or change the form state
 * before the entity form is built. It is invoked just once when first building
 * the entity form. Rebuilds will not trigger a new invocation.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity that is about to be shown on the form.
 * @param $form_display
 *   The current form display.
 * @param $operation
 *   The current operation.
 * @param array $form_state
 *   An associative array containing the current state of the form.
 *
 * @see \Drupal\Core\Entity\EntityFormController::prepareEntity()
 */
function hook_entity_prepare_form(\Drupal\Core\Entity\EntityInterface $entity, $form_display, $operation, array &$form_state) {
  if ($operation == 'edit') {
    $entity->label->value = 'Altered label';
    $form_state['mymodule']['label_altered'] = TRUE;
  }
}

/**
 * Alters the settings used for displaying an entity form.
 *
 * @param \Drupal\entity\Entity\EntityFormDisplay $form_display
 *   The entity_form_display object that will be used to display the entity form
 *   components.
 * @param array $context
 *   An associative array containing:
 *   - entity_type: The entity type, e.g., 'node' or 'user'.
 *   - bundle: The bundle, e.g., 'page' or 'article'.
 *   - form_mode: The form mode, e.g. 'default', 'profile', 'register'...
 */
function hook_entity_form_display_alter(\Drupal\entity\Entity\EntityFormDisplay $form_display, array $context) {
  // Hide the 'user_picture' field from the register form.
  if ($context['entity_type'] == 'user' && $context['form_mode'] == 'register') {
    $form_display->setComponent('user_picture', array(
      'type' => 'hidden',
    ));
  }
}

/**
 * Define custom entity fields.
 *
 * @param string $entity_type
 *   The entity type for which to define entity fields.
 *
 * @return array
 *   An array of entity field information having the following optional entries:
 *   - definitions: An array of field definitions to add all entities of this
 *     type, keyed by field name. See
 *     \Drupal\Core\Entity\EntityManager::getFieldDefinitions() for a list of
 *     supported keys in field definitions.
 *   - optional: An array of field definitions for optional entity fields, keyed
 *     by field name. Optional fields are fields that only exist for certain
 *     bundles of the entity type.
 *   - bundle map: An array keyed by bundle name, containing the names of
 *     optional fields that entities of this bundle have.
 *
 * @see hook_entity_field_info_alter()
 * @see \Drupal\Core\Entity\EntityManager::getFieldDefinitions()
 * @see \Drupal\Core\TypedData\TypedDataManager::create()
 */
function hook_entity_field_info($entity_type) {
  if (mymodule_uses_entity_type($entity_type)) {
    $info = array();
    $info['definitions']['mymodule_text'] = array(
      'type' => 'string_item',
      'list' => TRUE,
      'label' => t('The text'),
      'description' => t('A text property added by mymodule.'),
      'computed' => TRUE,
      'class' => '\Drupal\mymodule\EntityComputedText',
    );
    if ($entity_type == 'node') {
      // Add a property only to entities of the 'article' bundle.
      $info['optional']['mymodule_text_more'] = array(
        'type' => 'string_item',
        'list' => TRUE,
        'label' => t('More text'),
        'computed' => TRUE,
        'class' => '\Drupal\mymodule\EntityComputedMoreText',
      );
      $info['bundle map']['article'][0] = 'mymodule_text_more';
    }
    return $info;
  }
}

/**
 * Alter defined entity fields.
 *
 * @param array $info
 *   The entity field info array as returned by hook_entity_field_info().
 * @param string $entity_type
 *   The entity type for which entity fields are defined.
 *
 * @see hook_entity_field_info()
 */
function hook_entity_field_info_alter(&$info, $entity_type) {
  if (!empty($info['definitions']['mymodule_text'])) {
    // Alter the mymodule_text property to use a custom class.
    $info['definitions']['mymodule_text']['class'] = '\Drupal\anothermodule\EntityComputedText';
  }
}

/**
 * Alter entity operations.
 *
 * @param array $operations
 *   Operations array as returned by
 *   \Drupal\Core\Entity\EntityStorageControllerInterface::getOperations().
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity on which the linked operations will be performed.
 */
function hook_entity_operation_alter(array &$operations, \Drupal\Core\Entity\EntityInterface $entity) {
  $uri = $entity->uri();
  $operations['translate'] = array(
    'title' => t('Translate'),
    'href' => $uri['path'] . '/translate',
    'weight' => 50,
  );
}

/**
 * Control access to fields.
 *
 * This hook is invoked from
 * \Drupal\Core\Entity\EntityAccessController::fieldAccess() to let modules
 * grant or deny operations on fields.
 *
 * @param string $operation
 *   The operation to be performed. See
 *   \Drupal\Core\TypedData\AccessibleInterface::access() for possible values.
 * @param \Drupal\Core\Entity\Field\FieldDefinitionInterface $field_definition
 *   The field definition.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user account to check.
 * @param \Drupal\Core\Entity\Field\FieldItemListInterface $items
 *   (optional) The entity field object on which the operation is to be
 *   performed.
 *
 * @return bool|NULL
 *   TRUE if access should be allowed, FALSE if access should be denied and NULL
 *   if the implementation has no opinion.
 */
function hook_entity_field_access($operation, \Drupal\Core\Entity\Field\FieldDefinitionInterface $field_definition, \Drupal\Core\Session\AccountInterface $account, \Drupal\Core\Entity\Field\FieldItemListInterface $items = NULL) {
  if ($field_definition->getFieldName() == 'field_of_interest' && $operation == 'edit') {
    return user_access('update field of interest', $account);
  }
}

/**
 * Alters the default access behaviour for a given field.
 *
 * Use this hook to override access grants from another module. Note that the
 * original default access flag is masked under the ':default' key.
 *
 * @param array $grants
 *   An array of grants gathered by hook_entity_field_access(). The array is
 *   keyed by the module that defines the field's access control; the values are
 *   grant responses for each module (Boolean or NULL).
 * @param array $context
 *   Context array on the performed operation with the following keys:
 *   - operation: The operation to be performed (string).
 *   - field_definition: The field definition object
 *     (\Drupal\Core\Entity\Field\FieldDefinitionInterface)
 *   - account: The user account to check access for
 *     (Drupal\user\Entity\User).
 *   - items: (optional) The entity field items
 *     (\Drupal\Core\Entity\Field\FieldItemListInterface).
 */
function hook_entity_field_access_alter(array &$grants, array $context) {
  $field_definition = $context['field_definition'];
  if ($field_definition->getFieldName() == 'field_of_interest' && $grants['node'] === FALSE) {
    // Override node module's restriction to no opinion. We don't want to
    // provide our own access hook, we only want to take out node module's part
    // in the access handling of this field. We also don't want to switch node
    // module's grant to TRUE, because the grants of other modules should still
    // decide on their own if this field is accessible or not.
    $grants['node'] = NULL;
  }
}
