<?php

/**
 * @file
 * Hooks provided the Entity module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Render\Element;

/**
 * @defgroup entity_crud Entity CRUD, editing, and view hooks
 * @{
 * Hooks used in various entity operations.
 *
 * Entity create, read, update, and delete (CRUD) operations are performed by
 * entity storage classes; see the
 * @link entity_api Entity API topic @endlink for more information. Most
 * entities use or extend the default classes:
 * \Drupal\Core\Entity\ContentEntityDatabaseStorage for content entities, and
 * \Drupal\Core\Config\Entity\ConfigEntityStorage for configuration entities.
 * For these entities, there is a set of hooks that is invoked for each
 * CRUD operation, which module developers can implement to affect these
 * operations; these hooks are actually invoked from methods on
 * \Drupal\Core\Entity\EntityStorageBase.
 *
 * For content entities, viewing and rendering are handled by a view builder
 * class; see the @link entity_api Entity API topic @endlink for more
 * information. Most view builders extend or use the default class
 * \Drupal\Core\Entity\EntityViewBuilder.
 *
 * Entity editing (including adding new entities) is handled by entity form
 * classes; see the @link entity_api Entity API topic @endlink for more
 * information. Most entity editing forms extend base classes
 * \Drupal\Core\Entity\EntityForm or \Drupal\Core\Entity\ContentEntityForm.
 * Note that many other operations, such as confirming deletion of entities,
 * also use entity form classes.
 *
 * This topic lists all of the entity CRUD and view operations, and the hooks
 * and other operations that are invoked (in order) for each operation. Some
 * notes:
 * - Whenever an entity hook is invoked, there is both a type-specific entity
 *   hook, and a generic entity hook. For instance, during a create operation on
 *   a node, first hook_node_create() and then hook_entity_create() would be
 *   invoked.
 * - The entity-type-specific hooks are represented in the list below as
 *   hook_ENTITY_TYPE_... (hook_ENTITY_TYPE_create() in this example). To
 *   implement one of these hooks for an entity whose machine name is "foo",
 *   define a function called mymodule_foo_create(), for instance. Also note
 *   that the entity or array of entities that are passed into a specific-type
 *   hook are of the specific entity class, not the generic Entity class, so in
 *   your implementation, you can make the $entity argument something like $node
 *   and give it a specific type hint (which should normally be to the specific
 *   interface, such as \Drupal\Node\NodeInterface for nodes).
 * - $storage in the code examples is assumed to be an entity storage
 *   class. See the @link entity_api Entity API topic @endlink for
 *   information on how to instantiate the correct storage class for an
 *   entity type.
 * - $view_builder in the code examples is assumed to be an entity view builder
 *   class. See the @link entity_api Entity API topic @endlink for
 *   information on how to instantiate the correct view builder class for
 *   an entity type.
 * - During many operations, static methods are called on the entity class,
 *   which implements \Drupal\Entity\EntityInterface.
 *
 * @section create Create operations
 * To create an entity:
 * @code
 * $entity = $storage->create();
 *
 * // Add code here to set properties on the entity.
 *
 * // Until you call save(), the entity is just in memory.
 * $entity->save();
 * @endcode
 * There is also a shortcut method on entity classes, which creates an entity
 * with an array of provided property values: \Drupal\Core\Entity::create().
 *
 * Hooks invoked during the create operation:
 * - hook_ENTITY_TYPE_create()
 * - hook_entity_create()
 *
 * See @ref save below for the save portion of the operation.
 *
 * @section load Read/Load operations
 * To load (read) a single entity:
 * @code
 * $entity = $storage->load($id);
 * @endcode
 * To load multiple entities:
 * @code
 * $entities = $storage->loadMultiple($ids);
 * @endcode
 * Since load() calls loadMultiple(), these are really the same operation.
 * Here is the order of hooks and other operations that take place during
 * entity loading:
 * - Entity is loaded from storage.
 * - postLoad() is called on the entity class, passing in all of the loaded
 *   entities.
 * - hook_entity_load()
 * - hook_ENTITY_TYPE_load()
 *
 * When an entity is loaded, normally the default entity revision is loaded.
 * It is also possible to load a different revision, for entities that support
 * revisions, with this code:
 * @code
 * $entity = $storage->loadRevision($revision_id);
 * @endcode
 * This involves the same hooks and operations as regular entity loading.
 *
 * @section save Save operations
 * To update an existing entity, you will need to load it, change properties,
 * and then save; as described above, when creating a new entity, you will also
 * need to save it. Here is the order of hooks and other events that happen
 * during an entity save:
 * - preSave() is called on the entity object, and field objects.
 * - hook_ENTITY_TYPE_presave()
 * - hook_entity_presave()
 * - Entity is saved to storage.
 * - For updates on content entities, if there is a translation added that
 *   was not previously present:
 *   - hook_ENTITY_TYPE_translation_insert()
 *   - hook_entity_translation_insert()
 * - For updates on content entities, if there was a translation removed:
 *   - hook_ENTITY_TYPE_translation_delete()
 *   - hook_entity_translation_delete()
 * - postSave() is called on the entity object.
 * - hook_ENTITY_TYPE_insert() (new) or hook_ENTITY_TYPE_update() (update)
 * - hook_entity_insert() (new) or hook_entity_update() (update)
 *
 * Some specific entity types invoke hooks during preSave() or postSave()
 * operations. Examples:
 * - Field configuration preSave(): hook_field_config_update_forbid()
 * - Node postSave(): hook_node_access_records() and
 *   hook_node_access_records_alter()
 * - Config entities that are acting as entity bundles, in postSave():
 *   hook_entity_bundle_create() or hook_entity_bundle_rename() as appropriate
 * - Comment: hook_comment_publish() and hook_comment_unpublish() as
 *   appropriate.
 *
 * @section edit Editing operations
 * When an entity's add/edit form is used to add or edit an entity, there
 * are several hooks that are invoked:
 * - hook_entity_prepare_form()
 * - hook_ENTITY_TYPE_prepare_form()
 * - hook_entity_form_display_alter() (for content entities only)
 *
 * Some specific entity types have additional hooks that are run during
 * various steps in the process:
 * - Node entities: hook_node_validate() and hook_submit().
 *
 * @section delete Delete operations
 * To delete one or more entities, load them and then delete them:
 * @code
 * $entities = $storage->loadMultiple($ids);
 * $storage->delete($entities);
 * @endcode
 *
 * During the delete operation, the following hooks and other events happen:
 * - preDelete() is called on the entity class.
 * - hook_ENTITY_TYPE_predelete()
 * - hook_entity_predelete()
 * - Entity and field information is removed from storage.
 * - postDelete() is called on the entity class.
 * - hook_ENTITY_TYPE_delete()
 * - hook_entity_delete()
 *
 * Some specific entity types invoke hooks during the delete process. Examples:
 * - Entity bundle postDelete(): hook_entity_bundle_delete()
 *
 * Individual revisions of an entity can also be deleted:
 * @code
 * $storage->deleteRevision($revision_id);
 * @endcode
 * This operation invokes the following operations and hooks:
 * - Revision is loaded (see @ref load above).
 * - Revision and field information is removed from the database.
 * - hook_ENTITY_TYPE_revision_delete()
 * - hook_entity_revision_delete()
 *
 * @section view View/render operations
 * To make a render array for a loaded entity:
 * @code
 * // You can omit the language ID if the default language is being used.
 * $build = $view_builder->view($entity, 'view_mode_name', $language->id);
 * @endcode
 * You can also use the viewMultiple() method to view multiple entities.
 *
 * Hooks invoked during the operation of building a render array:
 * - hook_entity_view_mode_alter()
 * - hook_ENTITY_TYPE_build_defaults_alter()
 * - hook_entity_build_defaults_alter()
 *
 * View builders for some types override these hooks, notably:
 * - The Tour view builder does not invoke any hooks.
 * - The Block view builder invokes hook_block_view_alter() and
 *   hook_block_view_BASE_BLOCK_ID_alter(). Note that in other view builders,
 *   the view alter hooks are run later in the process.
 *
 * During the rendering operation, the default entity viewer runs the following
 * hooks and operations in the pre-render step:
 * - hook_entity_view_display_alter()
 * - hook_entity_prepare_view()
 * - Entity fields are loaded, and render arrays are built for them using
 *   their formatters.
 * - hook_entity_display_build_alter()
 * - hook_ENTITY_TYPE_view()
 * - hook_entity_view()
 * - hook_ENTITY_TYPE_view_alter()
 * - hook_entity_view_alter()
 *
 * Some specific builders have specific hooks:
 * - The Node view builder invokes hook_node_links_alter().
 * - The Comment view builder invokes hook_comment_links_alter().
 *
 * After this point in rendering, the theme system takes over. See the
 * @link theme_render Theme system and render API topic @endlink for more
 * information.
 *
 * @section misc Other entity hooks
 * Some types of entities invoke hooks for specific operations:
 * - Searching nodes:
 *   - hook_ranking()
 *   - Query is executed to find matching nodes
 *   - Resulting node is loaded
 *   - Node render array is built
 *   - comment_node_update_index() is called (this adds "N comments" text)
 *   - hook_node_search_result()
 * - Search indexing nodes:
 *   - Node is loaded
 *   - Node render array is built
 *   - hook_node_update_index()
 * @}
 */

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
 * @see hook_entity_create_access()
 * @see hook_ENTITY_TYPE_access()
 *
 * @ingroup entity_api
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
 * @see hook_ENTITY_TYPE_create_access()
 * @see hook_entity_access()
 *
 * @ingroup entity_api
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
 * @see hook_entity_access()
 * @see hook_ENTITY_TYPE_create_access()
 *
 * @ingroup entity_api
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
 * @see hook_ENTITY_TYPE_access()
 * @see hook_entity_create_access()
 *
 * @ingroup entity_api
 */
function hook_ENTITY_TYPE_create_access(\Drupal\Core\Session\AccountInterface $account, $langcode) {
  return NULL;
}

/**
 * Add to entity type definitions.
 *
 * Modules may implement this hook to add information to defined entity types,
 * as defined in \Drupal\Core\Entity\EntityTypeInterface.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
 *   An associative array of all entity type definitions, keyed by the entity
 *   type name. Passed by reference.
 *
 * @see \Drupal\Core\Entity\Entity
 * @see \Drupal\Core\Entity\EntityTypeInterface
 */
function hook_entity_type_build(array &$entity_types) {
  /** @var $entity_types \Drupal\Core\Entity\EntityTypeInterface[] */
  // Add a form for a custom node form without overriding the default
  // node form. To override the default node form, use hook_entity_type_alter().
  $entity_types['node']->setFormClass('mymodule_foo', 'Drupal\mymodule\NodeFooForm');
}

/**
 * Alter the entity type definitions.
 *
 * Modules may implement this hook to alter the information that defines an
 * entity type. All properties that are available in
 * \Drupal\Core\Entity\Annotation\EntityType and all the ones additionally
 * provided by modules can be altered here.
 *
 * Do not use this hook to add information to entity types, unless you are just
 * filling-in default values. Use hook_entity_type_build() instead.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
 *   An associative array of all entity type definitions, keyed by the entity
 *   type name. Passed by reference.
 *
 * @see \Drupal\Core\Entity\Entity
 * @see \Drupal\Core\Entity\EntityTypeInterface
 */
function hook_entity_type_alter(array &$entity_types) {
  /** @var $entity_types \Drupal\Core\Entity\EntityTypeInterface[] */
  // Set the controller class for nodes to an alternate implementation of the
  // Drupal\Core\Entity\EntityStorageInterface interface.
  $entity_types['node']->setStorageClass('Drupal\mymodule\MyCustomNodeStorage');
}

/**
 * Alter the view modes for entity types.
 *
 * @param array $view_modes
 *   An array of view modes, keyed first by entity type, then by view mode name.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface::getAllViewModes()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getViewModes()
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
 * @param string $entity_type_id
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param string $bundle
 *   The name of the bundle.
 *
 * @see entity_crud
 */
function hook_entity_bundle_create($entity_type_id, $bundle) {
  // When a new bundle is created, the menu needs to be rebuilt to add the
  // Field UI menu item tabs.
  \Drupal::service('router.builder')->setRebuildNeeded();
}

/**
 * Act on entity_bundle_rename().
 *
 * This hook is invoked after the operation has been performed.
 *
 * @param string $entity_type_id
 *   The entity type to which the bundle is bound.
 * @param string $bundle_old
 *   The previous name of the bundle.
 * @param string $bundle_new
 *   The new name of the bundle.
 *
 * @see entity_crud
 */
function hook_entity_bundle_rename($entity_type_id, $bundle_old, $bundle_new) {
  // Update the settings associated with the bundle in my_module.settings.
  $config = \Drupal::config('my_module.settings');
  $bundle_settings = $config->get('bundle_settings');
  if (isset($bundle_settings[$entity_type_id][$bundle_old])) {
    $bundle_settings[$entity_type_id][$bundle_new] = $bundle_settings[$entity_type_id][$bundle_old];
    unset($bundle_settings[$entity_type_id][$bundle_old]);
    $config->set('bundle_settings', $bundle_settings);
  }
}

/**
 * Act on entity_bundle_delete().
 *
 * This hook is invoked after the operation has been performed.
 *
 * @param string $entity_type_id
 *   The type of entity; for example, 'node' or 'user'.
 * @param string $bundle
 *   The bundle that was just deleted.
 *
 * @ingroup entity_crud
 */
function hook_entity_bundle_delete($entity_type_id, $bundle) {
  // Remove the settings associated with the bundle in my_module.settings.
  $config = \Drupal::config('my_module.settings');
  $bundle_settings = $config->get('bundle_settings');
  if (isset($bundle_settings[$entity_type_id][$bundle])) {
    unset($bundle_settings[$entity_type_id][$bundle]);
    $config->set('bundle_settings', $bundle_settings);
  }
}

/**
 * Act on a newly created entity.
 *
 * This hook runs after a new entity object has just been instantiated. It can
 * be used to set initial values, e.g. to provide defaults.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_create()
 */
function hook_entity_create(\Drupal\Core\Entity\EntityInterface $entity) {
  if ($entity instanceof ContentEntityInterface && !$entity->foo->value) {
    $entity->foo->value = 'some_initial_value';
  }
}

/**
 * Act on a newly created entity of a specific type.
 *
 * This hook runs after a new entity object has just been instantiated. It can
 * be used to set initial values, e.g. to provide defaults.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_create()
 */
function hook_ENTITY_TYPE_create(\Drupal\Core\Entity\EntityInterface $entity) {
  if (!$entity->foo->value) {
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
 * @param string $entity_type_id
 *   The type of entities being loaded (i.e. node, user, comment).
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_load()
 */
function hook_entity_load($entities, $entity_type_id) {
  foreach ($entities as $entity) {
    $entity->foo = mymodule_add_something($entity);
  }
}

/**
 * Act on entities of a specific type when loaded.
 *
 * @param array $entities
 *   The entities keyed by entity ID.
 *
 * @ingroup entity_crud
 * @see hook_entity_load()
 */
function hook_ENTITY_TYPE_load($entities) {
  foreach ($entities as $entity) {
    $entity->foo = mymodule_add_something($entity);
  }
}

/**
 * Act on an entity before it is created or updated.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_presave()
 */
function hook_entity_presave(Drupal\Core\Entity\EntityInterface $entity) {
 if ($entity instanceof ContentEntityInterface && $entity->isTranslatable()) {
   $attributes = \Drupal::request()->attributes;
   \Drupal::service('content_translation.synchronizer')->synchronizeFields($entity, $entity->language()->id, $attributes->get('source_langcode'));
  }
}

/**
 * Act on a specific type of entity before it is created or updated.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_presave()
 */
function hook_ENTITY_TYPE_presave(Drupal\Core\Entity\EntityInterface $entity) {
  if ($entity->isTranslatable()) {
    $attributes = \Drupal::request()->attributes;
    \Drupal::service('content_translation.synchronizer')->synchronizeFields($entity, $entity->language()->id, $attributes->get('source_langcode'));
  }
}

/**
 * Respond to creation of a new entity.
 *
 * This hook runs once the entity has been stored. Note that hook
 * implementations may not alter the stored entity data.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_insert()
 */
function hook_entity_insert(Drupal\Core\Entity\EntityInterface $entity) {
  // Insert the new entity into a fictional table of all entities.
  db_insert('example_entity')
    ->fields(array(
      'type' => $entity->getEntityTypeId(),
      'id' => $entity->id(),
      'created' => REQUEST_TIME,
      'updated' => REQUEST_TIME,
    ))
    ->execute();
}

/**
 * Respond to creation of a new entity of a particular type.
 *
 * This hook runs once the entity has been stored. Note that hook
 * implementations may not alter the stored entity data.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_insert()
 */
function hook_ENTITY_TYPE_insert(Drupal\Core\Entity\EntityInterface $entity) {
  // Insert the new entity into a fictional table of this type of entity.
  db_insert('example_entity')
    ->fields(array(
      'id' => $entity->id(),
      'created' => REQUEST_TIME,
      'updated' => REQUEST_TIME,
    ))
    ->execute();
}

/**
 * Respond to updates to an entity.
 *
 * This hook runs once the entity storage has been updated. Note that hook
 * implementations may not alter the stored entity data.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_update()
 */
function hook_entity_update(Drupal\Core\Entity\EntityInterface $entity) {
  // Update the entity's entry in a fictional table of all entities.
  db_update('example_entity')
    ->fields(array(
      'updated' => REQUEST_TIME,
    ))
    ->condition('type', $entity->getEntityTypeId())
    ->condition('id', $entity->id())
    ->execute();
}

/**
 * Respond to updates to an entity of a particular type.
 *
 * This hook runs once the entity storage has been updated. Note that hook
 * implementations may not alter the stored entity data.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_update()
 */
function hook_ENTITY_TYPE_update(Drupal\Core\Entity\EntityInterface $entity) {
  // Update the entity's entry in a fictional table of this type of entity.
  db_update('example_entity')
    ->fields(array(
      'updated' => REQUEST_TIME,
    ))
    ->condition('id', $entity->id())
    ->execute();
}

/**
 * Respond to creation of a new entity translation.
 *
 * This hook runs once the entity translation has been stored. Note that hook
 * implementations may not alter the stored entity translation data.
 *
 * @param \Drupal\Core\Entity\EntityInterface $translation
 *   The entity object of the translation just stored.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_translation_insert()
 */
function hook_entity_translation_insert(\Drupal\Core\Entity\EntityInterface $translation) {
  $variables = array(
    '@language' => $translation->language()->name,
    '@label' => $translation->getUntranslated()->label(),
  );
  \Drupal::logger('example')->notice('The @language translation of @label has just been stored.', $variables);
}

/**
 * Respond to creation of a new entity translation of a particular type.
 *
 * This hook runs once the entity translation has been stored. Note that hook
 * implementations may not alter the stored entity translation data.
 *
 * @param \Drupal\Core\Entity\EntityInterface $translation
 *   The entity object of the translation just stored.
 *
 * @ingroup entity_crud
 * @see hook_entity_translation_insert()
 */
function hook_ENTITY_TYPE_translation_insert(\Drupal\Core\Entity\EntityInterface $translation) {
  $variables = array(
    '@language' => $translation->language()->name,
    '@label' => $translation->getUntranslated()->label(),
  );
  \Drupal::logger('example')->notice('The @language translation of @label has just been stored.', $variables);
}

/**
 * Respond to entity translation deletion.
 *
 * This hook runs once the entity translation has been deleted from storage.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The original entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_translation_delete()
 */
function hook_entity_translation_delete(\Drupal\Core\Entity\EntityInterface $translation) {
  $languages = \Drupal::languageManager()->getLanguages();
  $variables = array(
    '@language' => $languages[$langcode]->name,
    '@label' => $entity->label(),
  );
  \Drupal::logger('example')->notice('The @language translation of @label has just been deleted.', $variables);
}

/**
 * Respond to entity translation deletion of a particular type.
 *
 * This hook runs once the entity translation has been deleted from storage.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The original entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_translation_delete()
 */
function hook_ENTITY_TYPE_translation_delete(\Drupal\Core\Entity\EntityInterface $translation) {
  $languages = \Drupal::languageManager()->getLanguages();
  $variables = array(
    '@language' => $languages[$langcode]->name,
    '@label' => $entity->label(),
  );
  \Drupal::logger('example')->notice('The @language translation of @label has just been deleted.', $variables);
}

/**
 * Act before entity deletion.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object for the entity that is about to be deleted.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_predelete()
 */
function hook_entity_predelete(Drupal\Core\Entity\EntityInterface $entity) {
  // Count references to this entity in a custom table before they are removed
  // upon entity deletion.
  $id = $entity->id();
  $type = $entity->getEntityTypeId();
  $count = db_select('example_entity_data')
    ->condition('type', $type)
    ->condition('id', $id)
    ->countQuery()
    ->execute()
    ->fetchField();

  // Log the count in a table that records this statistic for deleted entities.
  db_merge('example_deleted_entity_statistics')
    ->key(array('type' => $type, 'id' => $id))
    ->fields(array('count' => $count))
    ->execute();
}

/**
 * Act before entity deletion of a particular entity type.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object for the entity that is about to be deleted.
 *
 * @ingroup entity_crud
 * @see hook_entity_predelete()
 */
function hook_ENTITY_TYPE_predelete(Drupal\Core\Entity\EntityInterface $entity) {
  // Count references to this entity in a custom table before they are removed
  // upon entity deletion.
  $id = $entity->id();
  $type = $entity->getEntityTypeId();
  $count = db_select('example_entity_data')
    ->condition('type', $type)
    ->condition('id', $id)
    ->countQuery()
    ->execute()
    ->fetchField();

  // Log the count in a table that records this statistic for deleted entities.
  db_merge('example_deleted_entity_statistics')
    ->key(array('type' => $type, 'id' => $id))
    ->fields(array('count' => $count))
    ->execute();
}

/**
 * Respond to entity deletion.
 *
 * This hook runs once the entity has been deleted from the storage.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object for the entity that has been deleted.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_delete()
 */
function hook_entity_delete(Drupal\Core\Entity\EntityInterface $entity) {
  // Delete the entity's entry from a fictional table of all entities.
  db_delete('example_entity')
    ->condition('type', $entity->getEntityTypeId())
    ->condition('id', $entity->id())
    ->execute();
}

/**
 * Respond to entity deletion of a particular type.
 *
 * This hook runs once the entity has been deleted from the storage.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object for the entity that has been deleted.
 *
 * @ingroup entity_crud
 * @see hook_entity_delete()
 */
function hook_ENTITY_TYPE_delete(Drupal\Core\Entity\EntityInterface $entity) {
  // Delete the entity's entry from a fictional table of all entities.
  db_delete('example_entity')
    ->condition('type', $entity->getEntityTypeId())
    ->condition('id', $entity->id())
    ->execute();
}

/**
 * Respond to entity revision deletion.
 *
 * This hook runs once the entity revision has been deleted from the storage.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object for the entity revision that has been deleted.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_revision_delete()
 */
function hook_entity_revision_delete(Drupal\Core\Entity\EntityInterface $entity) {
  $referenced_files_by_field = _editor_get_file_uuids_by_field($entity);
  foreach ($referenced_files_by_field as $field => $uuids) {
    _editor_delete_file_usage($uuids, $entity, 1);
  }
}

/**
 * Respond to entity revision deletion of a particular type.
 *
 * This hook runs once the entity revision has been deleted from the storage.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object for the entity revision that has been deleted.
 *
 * @ingroup entity_crud
 * @see hook_entity_revision_delete()
 */
function hook_ENTITY_TYPE_revision_delete(Drupal\Core\Entity\EntityInterface $entity) {
  $referenced_files_by_field = _editor_get_file_uuids_by_field($entity);
  foreach ($referenced_files_by_field as $field => $uuids) {
    _editor_delete_file_usage($uuids, $entity, 1);
  }
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
 * @param &$build
 *   A renderable array representing the entity content.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 * @param $view_mode
 *   The view mode the entity is rendered in.
 * @param $langcode
 *   The language code used for rendering.
 *
 * The module may add elements to $build prior to rendering. The
 * structure of $build is a renderable array as expected by
 * drupal_render().
 *
 * @see hook_entity_view_alter()
 * @see hook_ENTITY_TYPE_view()
 *
 * @ingroup entity_crud
 */
function hook_entity_view(array &$build, \Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode, $langcode) {
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a 'mymodule_addition' extra field has been defined for the
  // entity bundle in hook_entity_extra_field_info().
  if ($display->getComponent('mymodule_addition')) {
    $build['mymodule_addition'] = array(
      '#markup' => mymodule_addition($entity),
      '#theme' => 'mymodule_my_additional_field',
    );
  }
}

/**
 * Act on entities of a particular type being assembled before rendering.
 *
 * @param &$build
 *   A renderable array representing the entity content.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 * @param $view_mode
 *   The view mode the entity is rendered in.
 * @param $langcode
 *   The language code used for rendering.
 *
 * The module may add elements to $build prior to rendering. The
 * structure of $build is a renderable array as expected by
 * drupal_render().
 *
 * @see hook_ENTITY_TYPE_view_alter()
 * @see hook_entity_view()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_view(array &$build, \Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode, $langcode) {
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a 'mymodule_addition' extra field has been defined for the
  // entity bundle in hook_entity_extra_field_info().
  if ($display->getComponent('mymodule_addition')) {
    $build['mymodule_addition'] = array(
      '#markup' => mymodule_addition($entity),
      '#theme' => 'mymodule_my_additional_field',
    );
  }
}

/**
 * Alter the results of the entity build array.
 *
 * This hook is called after the content has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * entity content structure has been built.
 *
 * If a module wishes to act on the rendered HTML of the entity rather than the
 * structured content array, it may use this hook to add a #post_render
 * callback. Alternatively, it could also implement hook_preprocess_HOOK() for
 * the particular entity type template, if there is one (e.g., node.html.twig).
 * See drupal_render() and _theme() for details.
 *
 * @param array &$build
 *   A renderable array representing the entity content.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object being rendered.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 *
 * @see hook_entity_view()
 * @see hook_ENTITY_TYPE_view_alter()
 *
 * @ingroup entity_crud
 */
function hook_entity_view_alter(array &$build, Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display) {
  if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;

    // Add a #post_render callback to act on the rendered HTML of the entity.
    $build['#post_render'][] = 'my_module_node_post_render';
  }
}

/**
 * Alter the results of the entity build array for a particular entity type.
 *
 * This hook is called after the content has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * entity content structure has been built.
 *
 * If a module wishes to act on the rendered HTML of the entity rather than the
 * structured content array, it may use this hook to add a #post_render
 * callback. Alternatively, it could also implement hook_preprocess_HOOK() for
 * the particular entity type template, if there is one (e.g., node.html.twig).
 * See drupal_render() and _theme() for details.
 *
 * @param array &$build
 *   A renderable array representing the entity content.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object being rendered.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 *
 * @see hook_ENTITY_TYPE_view()
 * @see hook_entity_view_alter()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_view_alter(array &$build, Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display) {
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
 * @param string $entity_type_id
 *   The type of entities being viewed (i.e. node, user, comment).
 * @param array $entities
 *   The entities keyed by entity ID.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface[] $displays
 *   The array of entity view displays holding the display options configured
 *   for the entity components, keyed by bundle name.
 * @param string $view_mode
 *   The view mode.
 *
 * @ingroup entity_crud
 */
function hook_entity_prepare_view($entity_type_id, array $entities, array $displays, $view_mode) {
  // Load a specific node into the user object for later theming.
  if (!empty($entities) && $entity_type_id == 'user') {
    // Only do the extra work if the component is configured to be
    // displayed. This assumes a 'mymodule_addition' extra field has been
    // defined for the entity bundle in hook_entity_extra_field_info().
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
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity that is being viewed.
 * @param array $context
 *   Array with additional context information, currently only contains the
 *   langcode the entity is viewed in.
 *
 * @ingroup entity_crud
 */
function hook_entity_view_mode_alter(&$view_mode, Drupal\Core\Entity\EntityInterface $entity, $context) {
  // For nodes, change the view mode when it is teaser.
  if ($entity->getEntityTypeId() == 'node' && $view_mode == 'teaser') {
    $view_mode = 'my_custom_view_mode';
  }
}

/**
 * Alter entity renderable values before cache checking in drupal_render().
 *
 * Invoked for a specific entity type.
 *
 * The values in the #cache key of the renderable array are used to determine if
 * a cache entry exists for the entity's rendered output. Ideally only values
 * that pertain to caching should be altered in this hook.
 *
 * @param array &$build
 *   A renderable array containing the entity's caching and view mode values.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity that is being viewed.
 * @param string $view_mode
 *   The view_mode that is to be used to display the entity.
 * @param string $langcode
 *   The code of the language $entity is accessed in.
 *
 * @see drupal_render()
 * @see \Drupal\Core\Entity\EntityViewBuilder
 * @see hook_entity_build_defaults_alter()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_build_defaults_alter(array &$build, \Drupal\Core\Entity\EntityInterface $entity, $view_mode, $langcode) {

}

/**
 * Alter entity renderable values before cache checking in drupal_render().
 *
 * The values in the #cache key of the renderable array are used to determine if
 * a cache entry exists for the entity's rendered output. Ideally only values
 * that pertain to caching should be altered in this hook.
 *
 * @param array &$build
 *   A renderable array containing the entity's caching and view mode values.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity that is being viewed.
 * @param string $view_mode
 *   The view_mode that is to be used to display the entity.
 * @param string $langcode
 *   The code of the language $entity is accessed in.
 *
 * @see drupal_render()
 * @see \Drupal\Core\Entity\EntityViewBuilder
 * @see hook_ENTITY_TYPE_build_defaults_alter()
 *
 * @ingroup entity_crud
 */
function hook_entity_build_defaults_alter(array &$build, \Drupal\Core\Entity\EntityInterface $entity, $view_mode, $langcode) {

}

/**
 * Alter the settings used for displaying an entity.
 *
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display that will be used to display the entity
 *   components.
 * @param array $context
 *   An associative array containing:
 *   - entity_type: The entity type, e.g., 'node' or 'user'.
 *   - bundle: The bundle, e.g., 'page' or 'article'.
 *   - view_mode: The view mode, e.g. 'full', 'teaser'...
 *
 * @ingroup entity_crud
 */
function hook_entity_view_display_alter(\Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, array $context) {
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
 * Alter the render array generated by an EntityDisplay for an entity.
 *
 * @param array $build
 *   The renderable array generated by the EntityDisplay.
 * @param array $context
 *   An associative array containing:
 *   - entity: The entity being rendered.
 *   - view_mode: The view mode; for example, 'full' or 'teaser'.
 *   - display: The EntityDisplay holding the display options.
 *
 * @ingroup entity_crud
 */
function hook_entity_display_build_alter(&$build, $context) {
  // Append RDF term mappings on displayed taxonomy links.
  foreach (Element::children($build) as $field_name) {
    $element = &$build[$field_name];
    if ($element['#field_type'] == 'entity_reference' && $element['#formatter'] == 'entity_reference_label') {
      foreach ($element['#items'] as $delta => $item) {
        $term = $item->entity;
        if (!empty($term->rdf_mapping['rdftype'])) {
          $element[$delta]['#options']['attributes']['typeof'] = $term->rdf_mapping['rdftype'];
        }
        if (!empty($term->rdf_mapping['name']['predicates'])) {
          $element[$delta]['#options']['attributes']['property'] = $term->rdf_mapping['name']['predicates'];
        }
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
 * @param $operation
 *   The current operation.
 * @param array $form_state
 *   An associative array containing the current state of the form.
 *
 * @see \Drupal\Core\Entity\EntityForm::prepareEntity()
 * @see hook_ENTITY_TYPE_prepare_form()
 *
 * @ingroup entity_crud
 */
function hook_entity_prepare_form(\Drupal\Core\Entity\EntityInterface $entity, $operation, array &$form_state) {
  if ($operation == 'edit') {
    $entity->label->value = 'Altered label';
    $form_state['mymodule']['label_altered'] = TRUE;
  }
}

/**
 * Acts on a particular type of entity object about to be in an entity form.
 *
 * This can be typically used to pre-fill entity values or change the form state
 * before the entity form is built. It is invoked just once when first building
 * the entity form. Rebuilds will not trigger a new invocation.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity that is about to be shown on the form.
 * @param $operation
 *   The current operation.
 * @param array $form_state
 *   An associative array containing the current state of the form.
 *
 * @see \Drupal\Core\Entity\EntityForm::prepareEntity()
 * @see hook_entity_prepare_form()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_prepare_form(\Drupal\Core\Entity\EntityInterface $entity, $operation, array &$form_state) {
  if ($operation == 'edit') {
    $entity->label->value = 'Altered label';
    $form_state['mymodule']['label_altered'] = TRUE;
  }
}

/**
 * Alter the settings used for displaying an entity form.
 *
 * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display
 *   The entity_form_display object that will be used to display the entity form
 *   components.
 * @param array $context
 *   An associative array containing:
 *   - entity_type: The entity type, e.g., 'node' or 'user'.
 *   - bundle: The bundle, e.g., 'page' or 'article'.
 *   - form_mode: The form mode, e.g. 'default', 'profile', 'register'...
 *
 * @ingroup entity_crud
 */
function hook_entity_form_display_alter(\Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display, array $context) {
  // Hide the 'user_picture' field from the register form.
  if ($context['entity_type'] == 'user' && $context['form_mode'] == 'register') {
    $form_display->setComponent('user_picture', array(
      'type' => 'hidden',
    ));
  }
}

/**
 * Provides custom base field definitions for a content entity type.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type definition.
 *
 * @return \Drupal\Core\Field\FieldDefinitionInterface[]
 *   An array of field definitions, keyed by field name.
 *
 * @see hook_entity_base_field_info_alter()
 * @see hook_entity_bundle_field_info()
 * @see hook_entity_bundle_field_info_alter()
 * @see \Drupal\Core\Field\FieldDefinitionInterface
 * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldDefinitions()
 */
function hook_entity_base_field_info(\Drupal\Core\Entity\EntityTypeInterface $entity_type) {
  if ($entity_type->id() == 'node') {
    $fields = array();
    $fields['mymodule_text'] = FieldDefinition::create('string')
      ->setLabel(t('The text'))
      ->setDescription(t('A text property added by mymodule.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\mymodule\EntityComputedText');

    return $fields;
  }
}

/**
 * Alter base field definitions for a content entity type.
 *
 * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fields
 *   The array of base field definitions for the entity type.
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type definition.
 *
 * @see hook_entity_base_field_info()
 * @see hook_entity_bundle_field_info()
 * @see hook_entity_bundle_field_info_alter()
 */
function hook_entity_base_field_info_alter(&$fields, \Drupal\Core\Entity\EntityTypeInterface $entity_type) {
  // Alter the mymodule_text field to use a custom class.
  if ($entity_type->id() == 'node' && !empty($fields['mymodule_text'])) {
    $fields['mymodule_text']->setClass('\Drupal\anothermodule\EntityComputedText');
  }
}

/**
 * Provides field definitions for a specific bundle within an entity type.
 *
 * Bundle fields either have to override an existing base field, or need to
 * provide a field storage definition via hook_entity_field_storage_info()
 * unless they are computed.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type definition.
 * @param string $bundle
 *   The bundle.
 * @param \Drupal\Core\Field\FieldDefinitionInterface[] $base_field_definitions
 *   The list of base field definitions for the entity type.
 *
 * @return \Drupal\Core\Field\FieldDefinitionInterface[]
 *   An array of bundle field definitions, keyed by field name.
 *
 * @see hook_entity_base_field_info()
 * @see hook_entity_base_field_info_alter()
 * @see hook_entity_field_storage_info()
 * @see hook_entity_field_storage_info_alter()
 * @see hook_entity_bundle_field_info_alter()
 * @see \Drupal\Core\Field\FieldDefinitionInterface
 * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldDefinitions()
 */
function hook_entity_bundle_field_info(\Drupal\Core\Entity\EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
  // Add a property only to nodes of the 'article' bundle.
  if ($entity_type->id() == 'node' && $bundle == 'article') {
    $fields = array();
    $fields['mymodule_text_more'] = FieldDefinition::create('string')
        ->setLabel(t('More text'))
        ->setComputed(TRUE)
        ->setClass('\Drupal\mymodule\EntityComputedMoreText');
    return $fields;
  }
}

/**
 * Alter bundle field definitions.
 *
 * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fields
 *   The array of bundle field definitions.
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type definition.
 * @param string $bundle
 *   The bundle.
 *
 * @see hook_entity_base_field_info()
 * @see hook_entity_base_field_info_alter()
 * @see hook_entity_bundle_field_info()
 */
function hook_entity_bundle_field_info_alter(&$fields, \Drupal\Core\Entity\EntityTypeInterface $entity_type, $bundle) {
  if ($entity_type->id() == 'node' && $bundle == 'article' && !empty($fields['mymodule_text'])) {
    // Alter the mymodule_text field to use a custom class.
    $fields['mymodule_text']->setClass('\Drupal\anothermodule\EntityComputedText');
  }
}

/**
 * Provides field storage definitions for a content entity type.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type definition.
 *
 * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
 *   An array of field storage definitions, keyed by field name.
 *
 * @see hook_entity_field_storage_info_alter()
 * @see \Drupal\Core\Field\FieldStorageDefinitionInterface
 * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldStorageDefinitions()
 */
function hook_entity_field_storage_info(\Drupal\Core\Entity\EntityTypeInterface $entity_type) {
  // Expose storage definitions for all exposed bundle fields.
  if ($entity_type->isFieldable()) {
    // Query by filtering on the ID as this is more efficient than filtering
    // on the entity_type property directly.
    $ids = \Drupal::entityQuery('field_config')
      ->condition('id', $entity_type->id() . '.', 'STARTS_WITH')
      ->execute();

    // Fetch all fields and key them by field name.
    $field_configs = entity_load_multiple('field_config', $ids);
    $result = array();
    foreach ($field_configs as $field_config) {
      $result[$field_config->getName()] = $field_config;
    }
    return $result;
  }
}

/**
 * Alter field storage definitions for a content entity type.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $fields
 *   The array of field storage definitions for the entity type.
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type definition.
 *
 * @see hook_entity_field_storage_info()
 */
function hook_entity_field_storage_info_alter(&$fields, \Drupal\Core\Entity\EntityTypeInterface $entity_type) {
  // Alter the max_length setting.
  if ($entity_type->id() == 'node' && !empty($fields['mymodule_text'])) {
    $fields['mymodule_text']->setSetting('max_length', 128);
  }
}

/**
 * Declares entity operations.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity on which the linked operations will be performed.
 *
 * @return array
 *   An operations array as returned by
 *   \Drupal\Core\Entity\EntityListBuilderInterface::getOperations().
 */
function hook_entity_operation(\Drupal\Core\Entity\EntityInterface $entity) {
  $operations = array();
  $operations['translate'] = array(
    'title' => t('Translate'),
    'route_name' => 'foo_module.entity.translate',
    'weight' => 50,
  );

  return $operations;
}

/**
 * Alter entity operations.
 *
 * @param array $operations
 *   Operations array as returned by
 *   \Drupal\Core\Entity\EntityListBuilderInterface::getOperations().
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity on which the linked operations will be performed.
 */
function hook_entity_operation_alter(array &$operations, \Drupal\Core\Entity\EntityInterface $entity) {
  // Alter the title and weight.
  $operations['translate']['title'] = t('Translate @entity_type', array(
    '@entity_type' => $entity->getEntityTypeId(),
  ));
  $operations['translate']['weight'] = 99;
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
 *   \Drupal\Core\Access\AccessibleInterface::access() for possible values.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
 *   The field definition.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user account to check.
 * @param \Drupal\Core\Field\FieldItemListInterface $items
 *   (optional) The entity field object on which the operation is to be
 *   performed.
 *
 * @return bool|null
 *   TRUE if access should be allowed, FALSE if access should be denied and NULL
 *   if the implementation has no opinion.
 */
function hook_entity_field_access($operation, \Drupal\Core\Field\FieldDefinitionInterface $field_definition, \Drupal\Core\Session\AccountInterface $account, \Drupal\Core\Field\FieldItemListInterface $items = NULL) {
  if ($field_definition->getName() == 'field_of_interest' && $operation == 'edit') {
    return user_access('update field of interest', $account);
  }
}

/**
 * Alter the default access behavior for a given field.
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
 *     (\Drupal\Core\Field\FieldDefinitionInterface)
 *   - account: The user account to check access for
 *     (Drupal\user\Entity\User).
 *   - items: (optional) The entity field items
 *     (\Drupal\Core\Field\FieldItemListInterface).
 */
function hook_entity_field_access_alter(array &$grants, array $context) {
  /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
  $field_definition = $context['field_definition'];
  if ($field_definition->getName() == 'field_of_interest' && $grants['node'] === FALSE) {
    // Override node module's restriction to no opinion. We don't want to
    // provide our own access hook, we only want to take out node module's part
    // in the access handling of this field. We also don't want to switch node
    // module's grant to TRUE, because the grants of other modules should still
    // decide on their own if this field is accessible or not.
    $grants['node'] = NULL;
  }
}

/**
 * Exposes "pseudo-field" components on content entities.
 *
 * Field UI's "Manage fields" and "Manage display" pages let users re-order
 * fields, but also non-field components. For nodes, these include elements
 * exposed by modules through hook_form_alter(), for instance.
 *
 * Content entities or modules that want to have their components supported
 * should expose them using this hook. The user-defined settings (weight,
 * visible) are automatically applied when entities or entity forms are
 * rendered.
 *
 * @see hook_entity_extra_field_info_alter()
 *
 * @return array
 *   The array structure is identical to that of the return value of
 *   \Drupal\Core\Entity\EntityManagerInterface::getExtraFields().
 */
function hook_entity_extra_field_info() {
  $extra = array();
  $module_language_enabled = \Drupal::moduleHandler()->moduleExists('language');
  $description = t('Node module element');

  foreach (node_type_get_types() as $bundle) {

    // Add also the 'language' select if Language module is enabled and the
    // bundle has multilingual support.
    // Visibility of the ordering of the language selector is the same as on the
    // node/add form.
    if ($module_language_enabled) {
      $configuration = language_get_default_configuration('node', $bundle->type);
      if ($configuration['language_show']) {
        $extra['node'][$bundle->type]['form']['language'] = array(
          'label' => t('Language'),
          'description' => $description,
          'weight' => 0,
        );
      }
    }
    $extra['node'][$bundle->type]['display']['language'] = array(
      'label' => t('Language'),
      'description' => $description,
      'weight' => 0,
      'visible' => FALSE,
    );
  }

  return $extra;
}

/**
 * Alter "pseudo-field" components on content entities.
 *
 * @param array $info
 *   The array structure is identical to that of the return value of
 *   \Drupal\Core\Entity\EntityManagerInterface::getExtraFields().
 *
 * @see hook_entity_extra_field_info()
 */
function hook_entity_extra_field_info_alter(&$info) {
  // Force node title to always be at the top of the list by default.
  foreach (node_type_get_types() as $bundle) {
    if (isset($info['node'][$bundle->type]['form']['title'])) {
      $info['node'][$bundle->type]['form']['title']['weight'] = -20;
    }
  }
}
