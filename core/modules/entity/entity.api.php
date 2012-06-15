<?php

/**
 * @file
 * Hooks provided the Entity module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Inform the base system and the Field API about one or more entity types.
 *
 * Inform the system about one or more entity types (i.e., object types that
 * can be loaded via entity_load() and, optionally, to which fields can be
 * attached).
 *
 * @return
 *   An array whose keys are entity type names and whose values identify
 *   properties of those types that the system needs to know about:
 *   - label: The human-readable name of the type.
 *   - entity class: The name of the entity class, defaults to
 *     Drupal\entity\Entity. The entity class must implement EntityInterface.
 *   - controller class: The name of the class that is used to load the objects.
 *     The class has to implement the
 *     Drupal\entity\EntityStorageControllerInterface interface. Leave blank
 *     to use the Drupal\entity\DatabaseStorageController implementation.
 *   - base table: (used by Drupal\entity\DatabaseStorageController) The
 *     name of the entity type's base table.
 *   - static cache: (used by Drupal\entity\DatabaseStorageController)
 *     FALSE to disable static caching of entities during a page request.
 *     Defaults to TRUE.
 *   - field cache: (used by Field API loading and saving of field data) FALSE
 *     to disable Field API's persistent cache of field data. Only recommended
 *     if a higher level persistent cache is available for the entity type.
 *     Defaults to TRUE.
 *   - load hook: The name of the hook which should be invoked by
 *     Drupal\entity\DatabaseStorageController::attachLoad(), for example
 *     'node_load'.
 *   - uri callback: A function taking an entity as argument and returning the
 *     uri elements of the entity, e.g. 'path' and 'options'. The actual entity
 *     uri can be constructed by passing these elements to url().
 *   - label callback: (optional) A function taking an entity as argument and
 *     returning the label of the entity. The entity label is the main string
 *     associated with an entity; for example, the title of a node or the
 *     subject of a comment. If there is an entity object property that defines
 *     the label, use the 'label' element of the 'entity keys' return
 *     value component to provide this information (see below). If more complex
 *     logic is needed to determine the label of an entity, you can instead
 *     specify a callback function here, which will be called to determine the
 *     entity label. See also the Drupal\entity\Entity::label() method, which
 *     implements this logic.
 *   - fieldable: Set to TRUE if you want your entity type to be fieldable.
 *   - translation: An associative array of modules registered as field
 *     translation handlers. Array keys are the module names, array values
 *     can be any data structure the module uses to provide field translation.
 *     Any empty value disallows the module to appear as a translation handler.
 *   - entity keys: An array describing how the Field API can extract the
 *     information it needs from the objects of the type. Elements:
 *     - id: The name of the property that contains the primary id of the
 *       entity. Every entity object passed to the Field API must have this
 *       property and its value must be numeric.
 *     - revision: The name of the property that contains the revision id of
 *       the entity. The Field API assumes that all revision ids are unique
 *       across all entities of a type. This entry can be omitted if the
 *       entities of this type are not versionable.
 *     - bundle: The name of the property that contains the bundle name for the
 *       entity. The bundle name defines which set of fields are attached to
 *       the entity (e.g. what nodes call "content type"). This entry can be
 *       omitted if this entity type exposes a single bundle (all entities have
 *       the same collection of fields). The name of this single bundle will be
 *       the same as the entity type.
 *     - label: The name of the property that contains the entity label. For
 *       example, if the entity's label is located in $entity->subject, then
 *       'subject' should be specified here. If complex logic is required to
 *       build the label, a 'label callback' should be defined instead (see
 *       the 'label callback' section above for details).
 *   - bundle keys: An array describing how the Field API can extract the
 *     information it needs from the bundle objects for this type (e.g
 *     $vocabulary objects for terms; not applicable for nodes). This entry can
 *     be omitted if this type's bundles do not exist as standalone objects.
 *     Elements:
 *     - bundle: The name of the property that contains the name of the bundle
 *       object.
 *   - bundles: An array describing all bundles for this object type. Keys are
 *     bundles machine names, as found in the objects' 'bundle' property
 *     (defined in the 'entity keys' entry above). Elements:
 *     - label: The human-readable name of the bundle.
 *     - uri callback: Same as the 'uri callback' key documented above for the
 *       entity type, but for the bundle only. When determining the URI of an
 *       entity, if a 'uri callback' is defined for both the entity type and
 *       the bundle, the one for the bundle is used.
 *     - admin: An array of information that allows Field UI pages to attach
 *       themselves to the existing administration pages for the bundle.
 *       Elements:
 *       - path: the path of the bundle's main administration page, as defined
 *         in hook_menu(). If the path includes a placeholder for the bundle,
 *         the 'bundle argument', 'bundle helper' and 'real path' keys below
 *         are required.
 *       - bundle argument: The position of the placeholder in 'path', if any.
 *       - real path: The actual path (no placeholder) of the bundle's main
 *         administration page. This will be used to generate links.
 *       - access callback: As in hook_menu(). 'user_access' will be assumed if
 *         no value is provided.
 *       - access arguments: As in hook_menu().
 *   - view modes: An array describing the view modes for the entity type. View
 *     modes let entities be displayed differently depending on the context.
 *     For instance, a node can be displayed differently on its own page
 *     ('full' mode), on the home page or taxonomy listings ('teaser' mode), or
 *     in an RSS feed ('rss' mode). Modules taking part in the display of the
 *     entity (notably the Field API) can adjust their behavior depending on
 *     the requested view mode. An additional 'default' view mode is available
 *     for all entity types. This view mode is not intended for actual entity
 *     display, but holds default display settings. For each available view
 *     mode, administrators can configure whether it should use its own set of
 *     field display settings, or just replicate the settings of the 'default'
 *     view mode, thus reducing the amount of display configurations to keep
 *     track of. Keys of the array are view mode names. Each view mode is
 *     described by an array with the following key/value pairs:
 *     - label: The human-readable name of the view mode
 *     - custom settings: A boolean specifying whether the view mode should by
 *       default use its own custom field display settings. If FALSE, entities
 *       displayed in this view mode will reuse the 'default' display settings
 *       by default (e.g. right after the module exposing the view mode is
 *       enabled), but administrators can later use the Field UI to apply custom
 *       display settings specific to the view mode.
 *
 * @see entity_load()
 * @see entity_load_multiple()
 * @see hook_entity_info_alter()
 */
function hook_entity_info() {
  $return = array(
    'node' => array(
      'label' => t('Node'),
      'entity class' => 'Drupal\node\Node',
      'controller class' => 'Drupal\node\NodeStorageController',
      'base table' => 'node',
      'revision table' => 'node_revision',
      'uri callback' => 'node_uri',
      'fieldable' => TRUE,
      'translation' => array(
        'locale' => TRUE,
      ),
      'entity keys' => array(
        'id' => 'nid',
        'revision' => 'vid',
        'bundle' => 'type',
      ),
      'bundle keys' => array(
        'bundle' => 'type',
      ),
      'bundles' => array(),
      'view modes' => array(
        'full' => array(
          'label' => t('Full content'),
          'custom settings' => FALSE,
        ),
        'teaser' => array(
          'label' => t('Teaser'),
          'custom settings' => TRUE,
        ),
        'rss' => array(
          'label' => t('RSS'),
          'custom settings' => FALSE,
        ),
      ),
    ),
  );

  // Search integration is provided by node.module, so search-related
  // view modes for nodes are defined here and not in search.module.
  if (module_exists('search')) {
    $return['node']['view modes'] += array(
      'search_index' => array(
        'label' => t('Search index'),
        'custom settings' => FALSE,
      ),
      'search_result' => array(
        'label' => t('Search result'),
        'custom settings' => FALSE,
      ),
    );
  }

  // Bundles must provide a human readable name so we can create help and error
  // messages, and the path to attach Field admin pages to.
  foreach (node_type_get_names() as $type => $name) {
    $return['node']['bundles'][$type] = array(
      'label' => $name,
      'admin' => array(
        'path' => 'admin/structure/types/manage/%node_type',
        'real path' => 'admin/structure/types/manage/' . $type,
        'bundle argument' => 4,
        'access arguments' => array('administer content types'),
      ),
    );
  }

  return $return;
}

/**
 * Alter the entity info.
 *
 * Modules may implement this hook to alter the information that defines an
 * entity. All properties that are available in hook_entity_info() can be
 * altered here.
 *
 * @param $entity_info
 *   The entity info array, keyed by entity name.
 *
 * @see hook_entity_info()
 */
function hook_entity_info_alter(&$entity_info) {
  // Set the controller class for nodes to an alternate implementation of the
  // Drupal\entity\EntityStorageControllerInterface interface.
  $entity_info['node']['controller class'] = 'Drupal\mymodule\MyCustomNodeStorageController';
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
 * @param Drupal\entity\EntityInterface $entity
 *   The entity object.
 */
function hook_entity_presave(Drupal\entity\EntityInterface $entity) {
  $entity->changed = REQUEST_TIME;
}

/**
 * Act on entities when inserted.
 *
 * @param Drupal\entity\EntityInterface $entity
 *   The entity object.
 */
function hook_entity_insert(Drupal\entity\EntityInterface $entity) {
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
 * @param Drupal\entity\EntityInterface $entity
 *   The entity object.
 */
function hook_entity_update(Drupal\entity\EntityInterface $entity) {
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
 * Act before entity deletion.
 *
 * This hook runs after the entity type-specific predelete hook.
 *
 * @param Drupal\entity\EntityInterface $entity
 *   The entity object for the entity that is about to be deleted.
 */
function hook_entity_predelete(Drupal\entity\EntityInterface $entity) {
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
 * @param Drupal\entity\EntityInterface $entity
 *   The entity object for the entity that has been deleted.
 */
function hook_entity_delete(Drupal\entity\EntityInterface $entity) {
  // Delete the entity's entry from a fictional table of all entities.
  db_delete('example_entity')
    ->condition('type', $entity->entityType())
    ->condition('id', $entity->id())
    ->execute();
}

/**
 * Alter or execute an Drupal\entity\EntityFieldQuery.
 *
 * @param Drupal\entity\EntityFieldQuery $query
 *   An EntityFieldQuery. One of the most important properties to be changed is
 *   EntityFieldQuery::executeCallback. If this is set to an existing function,
 *   this function will get the query as its single argument and its result
 *   will be the returned as the result of EntityFieldQuery::execute(). This can
 *   be used to change the behavior of EntityFieldQuery entirely. For example,
 *   the default implementation can only deal with one field storage engine, but
 *   it is possible to write a module that can query across field storage
 *   engines. Also, the default implementation presumes entities are stored in
 *   SQL, but the execute callback could instead query any other entity storage,
 *   local or remote.
 *
 *   Note the $query->altered attribute which is TRUE in case the query has
 *   already been altered once. This happens with cloned queries.
 *   If there is a pager, then such a cloned query will be executed to count
 *   all elements. This query can be detected by checking for
 *   ($query->pager && $query->count), allowing the driver to return 0 from
 *   the count query and disable the pager.
 */
function hook_entity_query_alter(Drupal\entity\EntityFieldQuery $query) {
  $query->executeCallback = 'my_module_query_callback';
}

/**
 * Act on entities being assembled before rendering.
 *
 * @param Drupal\entity\EntityInterface $entity
 *   The entity object.
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
function hook_entity_view(Drupal\entity\EntityInterface $entity, $view_mode, $langcode) {
  $entity->content['my_additional_field'] = array(
    '#markup' => $additional_field,
    '#weight' => 10,
    '#theme' => 'mymodule_my_additional_field',
  );
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
 * the particular entity type template, if there is one (e.g., node.tpl.php).
 * See drupal_render() and theme() for details.
 *
 * @param $build
 *   A renderable array representing the entity content.
 * @param Drupal\entity\EntityInterface $entity
 *   The entity object being rendered.
 *
 * @see hook_entity_view()
 * @see hook_comment_view_alter()
 * @see hook_node_view_alter()
 * @see hook_taxonomy_term_view_alter()
 * @see hook_user_view_alter()
 */
function hook_entity_view_alter(&$build, Drupal\entity\EntityInterface $entity) {
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
 * @param array $entities
 *   The entities keyed by entity ID.
 * @param string $entity_type
 *   The type of entities being viewed (i.e. node, user, comment).
 */
function hook_entity_prepare_view($entities, $entity_type) {
  // Load a specific node into the user object for later theming.
  if (!empty($entities) && $entity_type == 'user') {
    $nodes = mymodule_get_user_nodes(array_keys($entities));
    foreach ($entities as $uid => $entity) {
      $entity->user_node = $nodes[$uid];
    }
  }
}
