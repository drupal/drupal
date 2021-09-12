<?php

/**
 * @file
 * Hooks and documentation related to entities.
 */

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\DynamicallyFieldableEntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Render\Element;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\NodeType;

/**
 * @defgroup entity_crud Entity CRUD, editing, and view hooks
 * @{
 * Hooks used in various entity operations.
 *
 * Entity create, read, update, and delete (CRUD) operations are performed by
 * entity storage classes; see the
 * @link entity_api Entity API topic @endlink for more information. Most
 * entities use or extend the default classes:
 * \Drupal\Core\Entity\Sql\SqlContentEntityStorage for content entities, and
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
 *   interface, such as \Drupal\node\NodeInterface for nodes).
 * - $storage in the code examples is assumed to be an entity storage
 *   class. See the @link entity_api Entity API topic @endlink for
 *   information on how to instantiate the correct storage class for an
 *   entity type.
 * - $view_builder in the code examples is assumed to be an entity view builder
 *   class. See the @link entity_api Entity API topic @endlink for
 *   information on how to instantiate the correct view builder class for
 *   an entity type.
 * - During many operations, static methods are called on the entity class,
 *   which implements \Drupal\Core\Entity\EntityInterface.
 *
 * @section entities_revisions_translations Entities, revisions and translations
 * A content entity can have multiple stored variants: based on its definition,
 * it can be revisionable, translatable, or both.
 *
 * A revisionable entity can keep track of the changes that affect its data. In
 * fact all previous revisions of the entity can be stored and made available as
 * "historical" information. The "default" revision is the canonical variant of
 * the entity, the one that is loaded when no specific revision is requested.
 * Only changes to the default revision may be performed without triggering the
 * creation of a new revision, in any other case revision data is not supposed
 * to change. Aside from historical revisions, there can be "pending" revisions,
 * that contain changes that did not make their way into the default revision.
 * Typically these revisions contain data that is waiting for some form of
 * approval, before being accepted as canonical.
 * @see \Drupal\Core\Entity\RevisionableInterface
 * @see \Drupal\Core\Entity\RevisionableStorageInterface
 *
 * A translatable entity can contain multiple translations of the same content.
 * Content entity data is stored via fields, and each field can have one version
 * for each enabled language. Some fields may be defined as untranslatable,
 * which means that their values are shared among all translations. The
 * "default" translation is the canonical variant of the entity, the one whose
 * content will be accessible in the entity field data. Other translations
 * can be instantiated from the default one. Every translation has an "active
 * language" that is used to determine which field translation values should be
 * handled. Typically the default translation's active language is the language
 * of the content that was originally entered and served as source for the other
 * translations.
 * @see \Drupal\Core\Entity\TranslatableInterface
 * @see \Drupal\Core\Entity\TranslatableStorageInterface
 *
 * An entity that is both revisionable and translatable has all the features
 * described above: every revision can contain one or more translations. The
 * canonical variant of the entity is the default translation of the default
 * revision. Any revision will be initially loaded as the default translation,
 * the other revision translations can be instantiated from this one. If a
 * translation has changes in a certain revision, the translation is considered
 * "affected" by that revision, and will be flagged as such via the
 * "revision_translation_affected" field. With the built-in UI, every time a new
 * revision is saved, the changes for the edited translations will be stored,
 * while all field values for the other translations will be copied as-is.
 * However, if multiple translations of the default revision are being
 * subsequently modified without creating a new revision when saving, they will
 * all be affected by the default revision. Additionally, all revision
 * translations will be affected when saving a revision containing changes for
 * untranslatable fields. On the other hand, pending revisions are not supposed
 * to contain multiple affected translations, even when they are being
 * manipulated via the API.
 * @see \Drupal\Core\Entity\TranslatableRevisionableInterface
 * @see \Drupal\Core\Entity\TranslatableRevisionableStorageInterface
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
 * - When handling content entities, if a new translation is added to the entity
 *   object:
 *   - hook_ENTITY_TYPE_translation_create()
 *   - hook_entity_translation_create()
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
 * The "latest revision" of an entity is the most recently created one,
 * regardless of it being default or pending. If the entity is translatable,
 * revision translations are not taken into account either. In other words, any
 * time a new revision is created, that becomes the latest revision for the
 * entity overall, regardless of the affected translations. To load the latest
 * revision of an entity:
 * @code
 * $revision_id = $storage->getLatestRevisionId($entity_id);
 * $entity = $storage->loadRevision($revision_id);
 * @endcode
 * As usual, if the entity is translatable, this code instantiates into $entity
 * the default translation of the revision, even if the latest revision contains
 * only changes to a different translation:
 * @code
 * $is_default = $entity->isDefaultTranslation(); // returns TRUE
 * @endcode
 *
 * The "latest translation-affected revision" is the most recently created one
 * that affects the specified translation. For example, when a new revision
 * introducing some changes to an English translation is saved, that becomes the
 * new "latest revision". However, if an existing Italian translation was not
 * affected by those changes, then the "latest translation-affected revision"
 * for Italian remains what it was. To load the Italian translation at its
 * latest translation-affected revision:
 * @code
 * $revision_id = $storage->getLatestTranslationAffectedRevisionId($entity_id, 'it');
 * $it_translation = $storage->loadRevision($revision_id)->getTranslation('it');
 * @endcode
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
 * - Field configuration preSave(): hook_field_storage_config_update_forbid()
 * - Node postSave(): hook_node_access_records() and
 *   hook_node_access_records_alter()
 * - Config entities that are acting as entity bundles in postSave():
 *   hook_entity_bundle_create()
 * - Comment: hook_comment_publish() and hook_comment_unpublish() as
 *   appropriate.
 *
 * Note that all translations available for the entity are stored during a save
 * operation. When saving a new revision, a copy of every translation is stored,
 * regardless of it being affected by the revision.
 *
 * @section edit Editing operations
 * When an entity's add/edit form is used to add or edit an entity, there
 * are several hooks that are invoked:
 * - hook_entity_prepare_form()
 * - hook_ENTITY_TYPE_prepare_form()
 * - hook_entity_form_display_alter() (for content entities only)
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
 * $build = $view_builder->view($entity, 'view_mode_name', $language->getId());
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
 * @defgroup entity_api Entity API
 * @{
 * Describes how to define and manipulate content and configuration entities.
 *
 * Entities, in Drupal, are objects that are used for persistent storage of
 * content and configuration information. See the
 * @link info_types Information types topic @endlink for an overview of the
 * different types of information, and the
 * @link config_api Configuration API topic @endlink for more about the
 * configuration API.
 *
 * Each entity is an instance of a particular "entity type". Some content entity
 * types have sub-types, which are known as "bundles", while for other entity
 * types, there is only a single bundle. For example, the Node content entity
 * type, which is used for the main content pages in Drupal, has bundles that
 * are known as "content types", while the User content type, which is used for
 * user accounts, has only one bundle.
 *
 * The sections below have more information about entities and the Entity API;
 * for more detailed information, see
 * https://www.drupal.org/developing/api/entity.
 *
 * @section define Defining an entity type
 * Entity types are defined by modules, using Drupal's Plugin API (see the
 * @link plugin_api Plugin API topic @endlink for more information about plugins
 * in general). Here are the steps to follow to define a new entity type:
 * - Choose a unique machine name, or ID, for your entity type. This normally
 *   starts with (or is the same as) your module's machine name. It should be
 *   as short as possible, and may not exceed 32 characters.
 * - Define an interface for your entity's get/set methods, usually extending
 *   either \Drupal\Core\Config\Entity\ConfigEntityInterface or
 *   \Drupal\Core\Entity\ContentEntityInterface.
 * - Define a class for your entity, implementing your interface and extending
 *   either \Drupal\Core\Config\Entity\ConfigEntityBase or
 *   \Drupal\Core\Entity\ContentEntityBase, with annotation for
 *   \@ConfigEntityType or \@ContentEntityType in its documentation block.
 *   If you are defining a content entity type, it is recommended to extend the
 *   \Drupal\Core\Entity\EditorialContentEntityBase base class in order to get
 *   out-of-the-box support for Entity API's revisioning and publishing
 *   features, which will allow your entity type to be used with Drupal's
 *   editorial workflow provided by the Content Moderation module.
 * - In the annotation, the 'id' property gives the entity type ID, and the
 *   'label' property gives the human-readable name of the entity type. If you
 *   are defining a content entity type that uses bundles, the 'bundle_label'
 *   property gives the human-readable name to use for a bundle of this entity
 *   type (for example, "Content type" for the Node entity).
 * - The annotation will refer to several handler classes, which you will also
 *   need to define:
 *   - list_builder: Define a class that extends
 *     \Drupal\Core\Config\Entity\ConfigEntityListBuilder (for configuration
 *     entities) or \Drupal\Core\Entity\EntityListBuilder (for content
 *     entities), to provide an administrative overview for your entities.
 *   - add and edit forms, or default form: Define a class (or two) that
 *     extend(s) \Drupal\Core\Entity\EntityForm to provide add and edit forms
 *     for your entities. For content entities, base class
 *     \Drupal\Core\Entity\ContentEntityForm is a better starting point.
 *   - delete form: Define a class that extends
 *     \Drupal\Core\Entity\EntityConfirmFormBase to provide a delete
 *     confirmation form for your entities.
 *   - view_builder: For content entities and config entities that need to be
 *     viewed, define a class that implements
 *     \Drupal\Core\Entity\EntityViewBuilderInterface (usually extending
 *     \Drupal\Core\Entity\EntityViewBuilder), to display a single entity.
 *   - translation: For translatable content entities (if the 'translatable'
 *     annotation property has value TRUE), define a class that extends
 *     \Drupal\content_translation\ContentTranslationHandler, to translate
 *     the content. Configuration translation is handled automatically by the
 *     Configuration Translation module, without the need of a handler class.
 *   - access: If your configuration entity has complex permissions, you might
 *     need an access control handling, implementing
 *     \Drupal\Core\Entity\EntityAccessControlHandlerInterface, but most
 *     entities can just use the 'admin_permission' annotation property
 *     instead. Note that if you are creating your own access control handler,
 *     you should override the checkAccess() and checkCreateAccess() methods,
 *     not access().
 *   - storage: A class implementing
 *     \Drupal\Core\Entity\EntityStorageInterface. If not specified, content
 *     entities will use \Drupal\Core\Entity\Sql\SqlContentEntityStorage, and
 *     config entities will use \Drupal\Core\Config\Entity\ConfigEntityStorage.
 *     You can extend one of these classes to provide custom behavior.
 *   - views_data: A class implementing \Drupal\views\EntityViewsDataInterface
 *     to provide views data for the entity type. You can autogenerate most of
 *     the views data by extending \Drupal\views\EntityViewsData.
 * - For content entities, the annotation will refer to a number of database
 *   tables and their fields. These annotation properties, such as 'base_table',
 *   'data_table', 'entity_keys', etc., are documented on
 *   \Drupal\Core\Entity\EntityType.
 * - For content entities that are displayed on their own pages, the annotation
 *   will refer to a 'uri_callback' function, which takes an object of the
 *   entity interface you have defined as its parameter, and returns routing
 *   information for the entity page; see node_uri() for an example. You will
 *   also need to add a corresponding route to your module's routing.yml file;
 *   see the entity.node.canonical route in node.routing.yml for an example, and see
 *   @ref sec_routes below for some notes.
 * - Optionally, instead of defining routes, routes can be auto generated by
 *   providing a route handler. See @ref sec_routes. Otherwise, define routes
 *   and links for the various URLs associated with the entity.
 *   These go into the 'links' annotation, with the link type as the key, and
 *   the path of this link template as the value. The corresponding route
 *   requires the following route name:
 *   "entity.$entity_type_id.$link_template_type". See @ref sec_routes below for
 *   some routing notes. Typical link types are:
 *   - canonical: Default link, either to view (if entities are viewed on their
 *     own pages) or edit the entity.
 *   - delete-form: Confirmation form to delete the entity.
 *   - edit-form: Editing form.
 *   - Other link types specific to your entity type can also be defined.
 * - If your content entity is fieldable, provide the 'field_ui_base_route'
 *   annotation property, giving the name of the route that the Manage Fields,
 *   Manage Display, and Manage Form Display pages from the Field UI module
 *   will be attached to. This is usually the bundle settings edit page, or an
 *   entity type settings page if there are no bundles.
 * - If your content entity has bundles, you will also need to define a second
 *   plugin to handle the bundles. This plugin is itself a configuration entity
 *   type, so follow the steps here to define it. The machine name ('id'
 *   annotation property) of this configuration entity class goes into the
 *   'bundle_entity_type' annotation property on the entity type class. For
 *   example, for the Node entity, the bundle class is
 *   \Drupal\node\Entity\NodeType, whose machine name is 'node_type'. This is
 *   the annotation property 'bundle_entity_type' on the
 *   \Drupal\node\Entity\Node class. Also, the
 *   bundle config entity type annotation must have a 'bundle_of' property,
 *   giving the machine name of the entity type it is acting as a bundle for.
 *   These machine names are considered permanent, they may not be renamed.
 * - Additional annotation properties can be seen on entity class examples such
 *   as \Drupal\node\Entity\Node (content) and \Drupal\user\Entity\Role
 *   (configuration). These annotation properties are documented on
 *   \Drupal\Core\Entity\EntityType.
 *
 * @section sec_routes Entity routes
 * Entity routes can be defined in *.routing.yml files, like any other route:
 * see the @link routing Routing API @endlink topic for more information.
 * Another option for entity routes is to use a route provider class, and
 * reference it in the annotations on the entity class: see the end of this
 * section for an example.
 *
 * It's possible to use both a YAML file and a provider class for entity
 * routes, at the same time. Avoid duplicating route names between the two:
 * if a duplicate route name is found in both locations, the one in the YAML
 * file takes precedence; regardless, such duplication can be confusing.
 *
 * Here's an example YAML route specification, for the block configure form:
 * @code
 * entity.block.edit_form:
 *   path: '/admin/structure/block/manage/{block}'
 *   defaults:
 *     _entity_form: 'block.default'
 *     _title: 'Configure block'
 *   requirements:
 *     _entity_access: 'block.update'
 * @endcode
 * Some notes on this example:
 * - path: The {block} in the path is a placeholder, which (for an entity) must
 *   always take the form of {machine_name_of_entity_type}. In the URL, the
 *   placeholder value will be the ID of an entity item. When the route is used,
 *   the entity system will load the corresponding entity item and pass it in as
 *   an object to the controller for the route.
 * - defaults: For entity form routes, use _entity_form rather than the generic
 *   _controller or _form. The value is composed of the entity type machine name
 *   and a form handler type from the entity annotation (see @ref define above
 *   for more on handlers and annotation). So, in this example, block.default
 *   refers to the 'default' form handler on the block entity type, whose
 *   annotation contains:
 *   @code
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\block\BlockForm",
 *   @endcode
 * If instead of YAML you want to use a route provider class:
 * - \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider provides canonical,
 *   edit-form, and delete-form routes.
 * - \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider provides the same
 *   routes, set up to use the administrative theme for edit and delete pages.
 * - You can also create your own class, extending one of these two classes if
 *   you only want to modify their behavior slightly.
 *
 * To register any route provider class, add lines like the following to your
 * entity class annotation:
 * @code
 * handlers = {
 *   "route_provider" = {
 *     "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 * @endcode
 *
 * @section bundle Defining a content entity bundle
 * For entity types that use bundles, such as Node (bundles are content types)
 * and Taxonomy (bundles are vocabularies), modules and install profiles can
 * define bundles by supplying default configuration in their config/install
 * directories. (See the @link config_api Configuration API topic @endlink for
 * general information about configuration.)
 *
 * There are several good examples of this in Drupal Core:
 * - The Forum module defines a content type in node.type.forum.yml and a
 *   vocabulary in taxonomy.vocabulary.forums.yml
 * - The Book module defines a content type in node.type.book.yml
 * - The Standard install profile defines Page and Article content types in
 *   node.type.page.yml and node.type.article.yml, a Tags vocabulary in
 *   taxonomy.vocabulary.tags.yml, and a Node comment type in
 *   comment.type.comment.yml. This profile's configuration is especially
 *   instructive, because it also adds several fields to the Article type, and
 *   it sets up view and form display modes for the node types.
 *
 * @section load_query Loading, querying, and rendering entities
 * To load entities, use the entity storage manager, which is an object
 * implementing \Drupal\Core\Entity\EntityStorageInterface that you can
 * retrieve with:
 * @code
 * $storage = \Drupal::entityTypeManager()->getStorage('your_entity_type');
 * // Or if you have a $container variable:
 * $storage = $container->get('entity_type.manager')->getStorage('your_entity_type');
 * @endcode
 * Here, 'your_entity_type' is the machine name of your entity type ('id'
 * annotation property on the entity class), and note that you should use
 * dependency injection to retrieve this object if possible. See the
 * @link container Services and Dependency Injection topic @endlink for more
 * about how to properly retrieve services.
 *
 * To query to find entities to load, use an entity query, which is an object
 * implementing \Drupal\Core\Entity\Query\QueryInterface that you can retrieve
 * with:
 * @code
 * // Simple query:
 * $query = \Drupal::entityQuery('your_entity_type');
 * // Or, if you have a $container variable:
 * $storage = $container->get('entity_type.manager')->getStorage('your_entity_type');
 * $query = $storage->getQuery();
 * @endcode
 * If you need aggregation, there is an aggregate query available, which
 * implements \Drupal\Core\Entity\Query\QueryAggregateInterface:
 * @code
 * $query \Drupal::entityQueryAggregate('your_entity_type');
 * // Or:
 * $query = $storage->getAggregateQuery('your_entity_type');
 * @endcode
 *
 * In either case, you can then add conditions to your query, using methods
 * like condition(), exists(), etc. on $query; add sorting, pager, and range
 * if needed, and execute the query to return a list of entity IDs that match
 * the query.
 *
 * Here is an example, using the core File entity:
 * @code
 * $fids = Drupal::entityQuery('file')
 *   ->condition('status', FILE_STATUS_PERMANENT, '<>')
 *   ->condition('changed', REQUEST_TIME - $age, '<')
 *   ->range(0, 100)
 *   ->execute();
 * $files = $storage->loadMultiple($fids);
 * @endcode
 *
 * The normal way of viewing entities is by using a route, as described in the
 * sections above. If for some reason you need to render an entity in code in a
 * particular view mode, you can use an entity view builder, which is an object
 * implementing \Drupal\Core\Entity\EntityViewBuilderInterface that you can
 * retrieve with:
 * @code
 * $view_builder = \Drupal::entityTypeManager()->getViewBuilder('your_entity_type');
 * // Or if you have a $container variable:
 * $view_builder = $container->get('entity_type.manager')->getViewBuilder('your_entity_type');
 * @endcode
 * Then, to build and render the entity:
 * @code
 * // You can omit the language ID, by default the current content language will
 * // be used. If no translation is available for the current language, fallback
 * // rules will be used.
 * $build = $view_builder->view($entity, 'view_mode_name', $language->getId());
 * // $build is a render array.
 * $rendered = \Drupal::service('renderer')->render($build);
 * @endcode
 *
 * @section sec_access Access checking on entities
 * Entity types define their access permission scheme in their annotation.
 * Access permissions can be quite complex, so you should not assume any
 * particular permission scheme. Instead, once you have an entity object
 * loaded, you can check for permission for a particular operation (such as
 * 'view') at the entity or field level by calling:
 * @code
 * $entity->access($operation);
 * $entity->nameOfField->access($operation);
 * @endcode
 * The interface related to access checking in entities and fields is
 * \Drupal\Core\Access\AccessibleInterface.
 *
 * The default entity access control handler invokes two hooks while checking
 * access on a single entity: hook_entity_access() is invoked first, and
 * then hook_ENTITY_TYPE_access() (where ENTITY_TYPE is the machine name
 * of the entity type). If no module returns a TRUE or FALSE value from
 * either of these hooks, then the entity's default access checking takes
 * place. For create operations (creating a new entity), the hooks that
 * are invoked are hook_entity_create_access() and
 * hook_ENTITY_TYPE_create_access() instead.
 *
 * The access to an entity can be influenced in several ways:
 * - To explicitly allow access, return an AccessResultInterface object with
 * isAllowed() returning TRUE. Other modules can override this access by
 * returning TRUE for isForbidden().
 * - To explicitly forbid access, return an AccessResultInterface object with
 * isForbidden() returning TRUE. Access will be forbidden even if your module
 * (or another module) also returns TRUE for isNeutral() or isAllowed().
 * - To neither allow nor explicitly forbid access, return an
 * AccessResultInterface object with isNeutral() returning TRUE.
 * - If your module does not return an AccessResultInterface object, neutral
 * access will be assumed.
 *
 * The Node entity type has a complex system for determining access, which
 * developers can interact with. This is described in the
 * @link node_access Node access topic. @endlink
 *
 * @see i18n
 * @see entity_crud
 * @see \Drupal\Core\Entity\EntityRepositoryInterface::getTranslationFromContext()
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Control entity operation access.
 *
 * Note that this hook is not called for listings (e.g., from entity queries
 * and Views). For nodes, see @link node_access Node access rights @endlink for
 * a full explanation. For other entity types, see hook_query_TAG_alter().
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to check access to.
 * @param string $operation
 *   The operation that is to be performed on $entity. Usually one of:
 *   - "view"
 *   - "update"
 *   - "delete"
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account trying to access the entity.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *   The access result. The final result is calculated by using
 *   \Drupal\Core\Access\AccessResultInterface::orIf() on the result of every
 *   hook_entity_access() and hook_ENTITY_TYPE_access() implementation, and the
 *   result of the entity-specific checkAccess() method in the entity access
 *   control handler. Be careful when writing generalized access checks shared
 *   between routing and entity checks: routing uses the andIf() operator. So
 *   returning an isNeutral() does not determine entity access at all but it
 *   always ends up denying access while routing.
 *
 * @see \Drupal\Core\Entity\EntityAccessControlHandler
 * @see hook_entity_create_access()
 * @see hook_ENTITY_TYPE_access()
 * @see hook_query_TAG_alter()
 *
 * @ingroup entity_api
 */
function hook_entity_access(\Drupal\Core\Entity\EntityInterface $entity, $operation, \Drupal\Core\Session\AccountInterface $account) {
  // No opinion.
  return AccessResult::neutral();
}

/**
 * Control entity operation access for a specific entity type.
 *
 * Note that this hook is not called for listings (e.g., from entity queries
 * and Views). For nodes, see @link node_access Node access rights @endlink for
 * a full explanation. For other entity types, see hook_query_TAG_alter().
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to check access to.
 * @param string $operation
 *   The operation that is to be performed on $entity. Usually one of:
 *   - "view"
 *   - "update"
 *   - "delete"
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account trying to access the entity.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *   The access result. hook_entity_access() has detailed documentation.
 *
 * @see \Drupal\Core\Entity\EntityAccessControlHandler
 * @see hook_ENTITY_TYPE_create_access()
 * @see hook_entity_access()
 * @see hook_query_TAG_alter()
 *
 * @ingroup entity_api
 */
function hook_ENTITY_TYPE_access(\Drupal\Core\Entity\EntityInterface $entity, $operation, \Drupal\Core\Session\AccountInterface $account) {
  // No opinion.
  return AccessResult::neutral();
}

/**
 * Control entity create access.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account trying to access the entity.
 * @param array $context
 *   An associative array of additional context values. By default it contains
 *   language and the entity type ID:
 *   - entity_type_id - the entity type ID.
 *   - langcode - the current language code.
 * @param string $entity_bundle
 *   The entity bundle name.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *   The access result.
 *
 * @see \Drupal\Core\Entity\EntityAccessControlHandler
 * @see hook_entity_access()
 * @see hook_ENTITY_TYPE_create_access()
 *
 * @ingroup entity_api
 */
function hook_entity_create_access(\Drupal\Core\Session\AccountInterface $account, array $context, $entity_bundle) {
  // No opinion.
  return AccessResult::neutral();
}

/**
 * Control entity create access for a specific entity type.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account trying to access the entity.
 * @param array $context
 *   An associative array of additional context values. By default it contains
 *   language:
 *   - langcode - the current language code.
 * @param string $entity_bundle
 *   The entity bundle name.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *   The access result.
 *
 * @see \Drupal\Core\Entity\EntityAccessControlHandler
 * @see hook_ENTITY_TYPE_access()
 * @see hook_entity_create_access()
 *
 * @ingroup entity_api
 */
function hook_ENTITY_TYPE_create_access(\Drupal\Core\Session\AccountInterface $account, array $context, $entity_bundle) {
  // No opinion.
  return AccessResult::neutral();
}

/**
 * Add to entity type definitions.
 *
 * Modules may implement this hook to add information to defined entity types,
 * as defined in \Drupal\Core\Entity\EntityTypeInterface.
 *
 * To alter existing information or to add information dynamically, use
 * hook_entity_type_alter().
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
 *   An associative array of all entity type definitions, keyed by the entity
 *   type name. Passed by reference.
 *
 * @see \Drupal\Core\Entity\Entity
 * @see \Drupal\Core\Entity\EntityTypeInterface
 * @see hook_entity_type_alter()
 */
function hook_entity_type_build(array &$entity_types) {
  /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
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
 * Do not use this hook to add information to entity types, unless one of the
 * following is true:
 * - You are filling in default values.
 * - You need to dynamically add information only in certain circumstances.
 * - Your hook needs to run after hook_entity_type_build() implementations.
 * Use hook_entity_type_build() instead in all other cases.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
 *   An associative array of all entity type definitions, keyed by the entity
 *   type name. Passed by reference.
 *
 * @see \Drupal\Core\Entity\Entity
 * @see \Drupal\Core\Entity\EntityTypeInterface
 */
function hook_entity_type_alter(array &$entity_types) {
  /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
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
 * @see \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getAllViewModes()
 * @see \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModes()
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
 *   - uri_callback: (optional) The same as the 'uri_callback' key defined for
 *     the entity type in the EntityTypeManager, but for the bundle only. When
 *     determining the URI of an entity, if a 'uri_callback' is defined for both
 *     the entity type and the bundle, the one for the bundle is used.
 *   - translatable: (optional) A boolean value specifying whether this bundle
 *     has translation support enabled. Defaults to FALSE.
 *
 * @see \Drupal\Core\Entity\EntityTypeBundleInfo::getBundleInfo()
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
 * @see Drupal\Core\Entity\EntityTypeBundleInfo::getBundleInfo()
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
 * Acts when creating a new entity.
 *
 * This hook runs after a new entity object has just been instantiated.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_create()
 */
function hook_entity_create(\Drupal\Core\Entity\EntityInterface $entity) {
  \Drupal::logger('example')->info('Entity created: @label', ['@label' => $entity->label()]);
}

/**
 * Acts when creating a new entity of a specific type.
 *
 * This hook runs after a new entity object has just been instantiated.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_create()
 */
function hook_ENTITY_TYPE_create(\Drupal\Core\Entity\EntityInterface $entity) {
  \Drupal::logger('example')->info('ENTITY_TYPE created: @label', ['@label' => $entity->label()]);
}

/**
 * Respond to entity revision creation.
 *
 * @param \Drupal\Core\Entity\EntityInterface $new_revision
 *   The new revision that was created.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The original entity that was used to create the revision from.
 * @param bool|null $keep_untranslatable_fields
 *   Whether untranslatable field values were kept (TRUE) or copied from the
 *   default revision (FALSE) when generating a merged revision. If no value was
 *   explicitly specified (NULL), a default value of TRUE should be assumed if
 *   the provided entity is the default translation and untranslatable fields
 *   should only affect the default translation, FALSE otherwise.
 *
 * @ingroup entity_crud
 * @see \Drupal\Core\Entity\RevisionableStorageInterface::createRevision()
 * @see \Drupal\Core\Entity\TranslatableRevisionableStorageInterface::createRevision()
 */
function hook_entity_revision_create(\Drupal\Core\Entity\EntityInterface $new_revision, \Drupal\Core\Entity\EntityInterface $entity, $keep_untranslatable_fields) {
  // Retain the value from an untranslatable field, which are by default
  // synchronized from the default revision.
  $new_revision->set('untranslatable_field', $entity->get('untranslatable_field'));
}

/**
 * Respond to entity revision creation.
 *
 * @param \Drupal\Core\Entity\EntityInterface $new_revision
 *   The new revision that was created.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The original entity that was used to create the revision from.
 * @param bool|null $keep_untranslatable_fields
 *   Whether untranslatable field values were kept (TRUE) or copied from the
 *   default revision (FALSE) when generating a merged revision. If no value was
 *   explicitly specified (NULL), a default value of TRUE should be assumed if
 *   the provided entity is the default translation and untranslatable fields
 *   should only affect the default translation, FALSE otherwise.
 *
 * @ingroup entity_crud
 * @see \Drupal\Core\Entity\RevisionableStorageInterface::createRevision()
 * @see \Drupal\Core\Entity\TranslatableRevisionableStorageInterface::createRevision()
 */
function hook_ENTITY_TYPE_revision_create(\Drupal\Core\Entity\EntityInterface $new_revision, \Drupal\Core\Entity\EntityInterface $entity, $keep_untranslatable_fields) {
  // Retain the value from an untranslatable field, which are by default
  // synchronized from the default revision.
  $new_revision->set('untranslatable_field', $entity->get('untranslatable_field'));
}

/**
 * Act on an array of entity IDs before they are loaded.
 *
 * This hook can be used by modules that need, for example, to return a
 * different revision than the default one.
 *
 * @param array $ids
 *   An array of entity IDs that have to be loaded.
 * @param string $entity_type_id
 *   The type of entities being loaded (i.e. node, user, comment).
 *
 * @return \Drupal\Core\Entity\EntityInterface[]
 *   An array of pre-loaded entity objects.
 *
 * @ingroup entity_crud
 */
function hook_entity_preload(array $ids, $entity_type_id) {
  $entities = [];

  foreach ($ids as $id) {
    $entities[] = mymodule_swap_revision($id);
  }

  return $entities;
}

/**
 * Act on entities when loaded.
 *
 * This is a generic load hook called for all entity types loaded via the
 * entity API.
 *
 * hook_entity_storage_load() should be used to load additional data for
 * content entities.
 *
 * @param \Drupal\Core\Entity\EntityInterface[] $entities
 *   The entities keyed by entity ID.
 * @param string $entity_type_id
 *   The type of entities being loaded (i.e. node, user, comment).
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_load()
 */
function hook_entity_load(array $entities, $entity_type_id) {
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
 * Act on content entities when loaded from the storage.
 *
 * The results of this hook will be cached.
 *
 * @param \Drupal\Core\Entity\EntityInterface[] $entities
 *   The entities keyed by entity ID.
 * @param string $entity_type
 *   The type of entities being loaded (i.e. node, user, comment).
 *
 * @see hook_entity_load()
 */
function hook_entity_storage_load(array $entities, $entity_type) {
  foreach ($entities as $entity) {
    $entity->foo = mymodule_add_something_uncached($entity);
  }
}

/**
 * Act on content entities of a given type when loaded from the storage.
 *
 * The results of this hook will be cached if the entity type supports it.
 *
 * @param \Drupal\Core\Entity\EntityInterface[] $entities
 *   The entities keyed by entity ID.
 *
 * @see hook_entity_storage_load()
 */
function hook_ENTITY_TYPE_storage_load(array $entities) {
  foreach ($entities as $entity) {
    $entity->foo = mymodule_add_something_uncached($entity);
  }
}

/**
 * Act on an entity before it is created or updated.
 *
 * You can get the original entity object from $entity->original when it is an
 * update of the entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_presave()
 */
function hook_entity_presave(\Drupal\Core\Entity\EntityInterface $entity) {
  if ($entity instanceof ContentEntityInterface && $entity->isTranslatable()) {
    $route_match = \Drupal::routeMatch();
    \Drupal::service('content_translation.synchronizer')->synchronizeFields($entity, $entity->language()->getId(), $route_match->getParameter('source_langcode'));
  }
}

/**
 * Act on a specific type of entity before it is created or updated.
 *
 * You can get the original entity object from $entity->original when it is an
 * update of the entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_presave()
 */
function hook_ENTITY_TYPE_presave(\Drupal\Core\Entity\EntityInterface $entity) {
  if ($entity->isTranslatable()) {
    $route_match = \Drupal::routeMatch();
    \Drupal::service('content_translation.synchronizer')->synchronizeFields($entity, $entity->language()->getId(), $route_match->getParameter('source_langcode'));
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
function hook_entity_insert(\Drupal\Core\Entity\EntityInterface $entity) {
  // Insert the new entity into a fictional table of all entities.
  \Drupal::database()->insert('example_entity')
    ->fields([
      'type' => $entity->getEntityTypeId(),
      'id' => $entity->id(),
      'created' => REQUEST_TIME,
      'updated' => REQUEST_TIME,
    ])
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
function hook_ENTITY_TYPE_insert(\Drupal\Core\Entity\EntityInterface $entity) {
  // Insert the new entity into a fictional table of this type of entity.
  \Drupal::database()->insert('example_entity')
    ->fields([
      'id' => $entity->id(),
      'created' => REQUEST_TIME,
      'updated' => REQUEST_TIME,
    ])
    ->execute();
}

/**
 * Respond to updates to an entity.
 *
 * This hook runs once the entity storage has been updated. Note that hook
 * implementations may not alter the stored entity data. Get the original entity
 * object from $entity->original.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_update()
 */
function hook_entity_update(\Drupal\Core\Entity\EntityInterface $entity) {
  // Update the entity's entry in a fictional table of all entities.
  \Drupal::database()->update('example_entity')
    ->fields([
      'updated' => REQUEST_TIME,
    ])
    ->condition('type', $entity->getEntityTypeId())
    ->condition('id', $entity->id())
    ->execute();
}

/**
 * Respond to updates to an entity of a particular type.
 *
 * This hook runs once the entity storage has been updated. Note that hook
 * implementations may not alter the stored entity data. Get the original entity
 * object from $entity->original.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_update()
 */
function hook_ENTITY_TYPE_update(\Drupal\Core\Entity\EntityInterface $entity) {
  // Update the entity's entry in a fictional table of this type of entity.
  \Drupal::database()->update('example_entity')
    ->fields([
      'updated' => REQUEST_TIME,
    ])
    ->condition('id', $entity->id())
    ->execute();
}

/**
 * Acts when creating a new entity translation.
 *
 * This hook runs after a new entity translation object has just been
 * instantiated.
 *
 * @param \Drupal\Core\Entity\EntityInterface $translation
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_translation_create()
 */
function hook_entity_translation_create(\Drupal\Core\Entity\EntityInterface $translation) {
  \Drupal::logger('example')->info('Entity translation created: @label', ['@label' => $translation->label()]);
}

/**
 * Acts when creating a new entity translation of a specific type.
 *
 * This hook runs after a new entity translation object has just been
 * instantiated.
 *
 * @param \Drupal\Core\Entity\EntityInterface $translation
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_translation_create()
 */
function hook_ENTITY_TYPE_translation_create(\Drupal\Core\Entity\EntityInterface $translation) {
  \Drupal::logger('example')->info('ENTITY_TYPE translation created: @label', ['@label' => $translation->label()]);
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
  $variables = [
    '@language' => $translation->language()->getName(),
    '@label' => $translation->getUntranslated()->label(),
  ];
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
  $variables = [
    '@language' => $translation->language()->getName(),
    '@label' => $translation->getUntranslated()->label(),
  ];
  \Drupal::logger('example')->notice('The @language translation of @label has just been stored.', $variables);
}

/**
 * Respond to entity translation deletion.
 *
 * This hook runs once the entity translation has been deleted from storage.
 *
 * @param \Drupal\Core\Entity\EntityInterface $translation
 *   The original entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_translation_delete()
 */
function hook_entity_translation_delete(\Drupal\Core\Entity\EntityInterface $translation) {
  $variables = [
    '@language' => $translation->language()->getName(),
    '@label' => $translation->label(),
  ];
  \Drupal::logger('example')->notice('The @language translation of @label has just been deleted.', $variables);
}

/**
 * Respond to entity translation deletion of a particular type.
 *
 * This hook runs once the entity translation has been deleted from storage.
 *
 * @param \Drupal\Core\Entity\EntityInterface $translation
 *   The original entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_translation_delete()
 */
function hook_ENTITY_TYPE_translation_delete(\Drupal\Core\Entity\EntityInterface $translation) {
  $variables = [
    '@language' => $translation->language()->getName(),
    '@label' => $translation->label(),
  ];
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
function hook_entity_predelete(\Drupal\Core\Entity\EntityInterface $entity) {
  $connection = \Drupal::database();
  // Count references to this entity in a custom table before they are removed
  // upon entity deletion.
  $id = $entity->id();
  $type = $entity->getEntityTypeId();
  $count = \Drupal::database()->select('example_entity_data')
    ->condition('type', $type)
    ->condition('id', $id)
    ->countQuery()
    ->execute()
    ->fetchField();

  // Log the count in a table that records this statistic for deleted entities.
  $connection->merge('example_deleted_entity_statistics')
    ->key(['type' => $type, 'id' => $id])
    ->fields(['count' => $count])
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
function hook_ENTITY_TYPE_predelete(\Drupal\Core\Entity\EntityInterface $entity) {
  $connection = \Drupal::database();
  // Count references to this entity in a custom table before they are removed
  // upon entity deletion.
  $id = $entity->id();
  $type = $entity->getEntityTypeId();
  $count = \Drupal::database()->select('example_entity_data')
    ->condition('type', $type)
    ->condition('id', $id)
    ->countQuery()
    ->execute()
    ->fetchField();

  // Log the count in a table that records this statistic for deleted entities.
  $connection->merge('example_deleted_entity_statistics')
    ->key(['type' => $type, 'id' => $id])
    ->fields(['count' => $count])
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
function hook_entity_delete(\Drupal\Core\Entity\EntityInterface $entity) {
  // Delete the entity's entry from a fictional table of all entities.
  \Drupal::database()->delete('example_entity')
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
function hook_ENTITY_TYPE_delete(\Drupal\Core\Entity\EntityInterface $entity) {
  // Delete the entity's entry from a fictional table of all entities.
  \Drupal::database()->delete('example_entity')
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
function hook_entity_revision_delete(\Drupal\Core\Entity\EntityInterface $entity) {
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
function hook_ENTITY_TYPE_revision_delete(\Drupal\Core\Entity\EntityInterface $entity) {
  $referenced_files_by_field = _editor_get_file_uuids_by_field($entity);
  foreach ($referenced_files_by_field as $field => $uuids) {
    _editor_delete_file_usage($uuids, $entity, 1);
  }
}

/**
 * Act on entities being assembled before rendering.
 *
 * @param &$build
 *   A renderable array representing the entity content. The module may add
 *   elements to $build prior to rendering. The structure of $build is a
 *   renderable array as expected by
 *   \Drupal\Core\Render\RendererInterface::render().
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 * @param $view_mode
 *   The view mode the entity is rendered in.
 *
 * @see hook_entity_view_alter()
 * @see hook_ENTITY_TYPE_view()
 *
 * @ingroup entity_crud
 */
function hook_entity_view(array &$build, \Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode) {
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a 'mymodule_addition' extra field has been defined for the
  // entity bundle in hook_entity_extra_field_info().
  if ($display->getComponent('mymodule_addition')) {
    $build['mymodule_addition'] = [
      '#markup' => mymodule_addition($entity),
      '#theme' => 'mymodule_my_additional_field',
    ];
  }
}

/**
 * Act on entities of a particular type being assembled before rendering.
 *
 * @param &$build
 *   A renderable array representing the entity content. The module may add
 *   elements to $build prior to rendering. The structure of $build is a
 *   renderable array as expected by
 *   \Drupal\Core\Render\RendererInterface::render().
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 * @param $view_mode
 *   The view mode the entity is rendered in.
 *
 * @see hook_ENTITY_TYPE_view_alter()
 * @see hook_entity_view()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_view(array &$build, \Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode) {
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a 'mymodule_addition' extra field has been defined for the
  // entity bundle in hook_entity_extra_field_info().
  if ($display->getComponent('mymodule_addition')) {
    $build['mymodule_addition'] = [
      '#markup' => mymodule_addition($entity),
      '#theme' => 'mymodule_my_additional_field',
    ];
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
 *
 * See the @link themeable Default theme implementations topic @endlink and
 * \Drupal\Core\Render\RendererInterface::render() for details.
 *
 * @param array &$build
 *   A renderable array representing the entity content.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object being rendered.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 *
 * @ingroup entity_crud
 *
 * @see hook_entity_view()
 * @see hook_ENTITY_TYPE_view_alter()
 */
function hook_entity_view_alter(array &$build, \Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display) {
  if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;

    // Add a #post_render callback to act on the rendered HTML of the entity.
    // The object must implement \Drupal\Core\Security\TrustedCallbackInterface.
    $build['#post_render'][] = '\Drupal\my_module\NodeCallback::postRender';
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
 *
 * See the @link themeable Default theme implementations topic @endlink and
 * \Drupal\Core\Render\RendererInterface::render() for details.
 *
 * @param array &$build
 *   A renderable array representing the entity content.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object being rendered.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 *
 * @ingroup entity_crud
 *
 * @see hook_ENTITY_TYPE_view()
 * @see hook_entity_view_alter()
 */
function hook_ENTITY_TYPE_view_alter(array &$build, \Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display) {
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
    $ids = [];
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
 *
 * @ingroup entity_crud
 */
function hook_entity_view_mode_alter(&$view_mode, \Drupal\Core\Entity\EntityInterface $entity) {
  // For nodes, change the view mode when it is teaser.
  if ($entity->getEntityTypeId() == 'node' && $view_mode == 'teaser') {
    $view_mode = 'my_custom_view_mode';
  }
}

/**
 * Alter entity renderable values before cache checking during rendering.
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
 *
 * @see \Drupal\Core\Render\RendererInterface::render()
 * @see \Drupal\Core\Entity\EntityViewBuilder
 * @see hook_entity_build_defaults_alter()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_build_defaults_alter(array &$build, \Drupal\Core\Entity\EntityInterface $entity, $view_mode) {

}

/**
 * Alter entity renderable values before cache checking during rendering.
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
 *
 * @see \Drupal\Core\Render\RendererInterface::render()
 * @see \Drupal\Core\Entity\EntityViewBuilder
 * @see hook_ENTITY_TYPE_build_defaults_alter()
 *
 * @ingroup entity_crud
 */
function hook_entity_build_defaults_alter(array &$build, \Drupal\Core\Entity\EntityInterface $entity, $view_mode) {

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
 *   - view_mode: The view mode, e.g., 'full', 'teaser', etc.
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
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see \Drupal\Core\Entity\EntityForm::prepareEntity()
 * @see hook_ENTITY_TYPE_prepare_form()
 *
 * @ingroup entity_crud
 */
function hook_entity_prepare_form(\Drupal\Core\Entity\EntityInterface $entity, $operation, \Drupal\Core\Form\FormStateInterface $form_state) {
  if ($operation == 'edit') {
    $entity->label->value = 'Altered label';
    $form_state->set('label_altered', TRUE);
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
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see \Drupal\Core\Entity\EntityForm::prepareEntity()
 * @see hook_entity_prepare_form()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_prepare_form(\Drupal\Core\Entity\EntityInterface $entity, $operation, \Drupal\Core\Form\FormStateInterface $form_state) {
  if ($operation == 'edit') {
    $entity->label->value = 'Altered label';
    $form_state->set('label_altered', TRUE);
  }
}

/**
 * Change the form mode used to build an entity form.
 *
 * @param string $form_mode
 *   The form_mode that is to be used to build the entity form.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity for which the form is being built.
 *
 * @ingroup entity_crud
 */
function hook_entity_form_mode_alter(&$form_mode, \Drupal\Core\Entity\EntityInterface $entity) {
  // Change the form mode for users with Administrator role.
  if ($entity->getEntityTypeId() == 'user' && $entity->hasRole('administrator')) {
    $form_mode = 'my_custom_form_mode';
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
 *   - form_mode: The form mode; e.g., 'default', 'profile', 'register', etc.
 *
 * @ingroup entity_crud
 */
function hook_entity_form_display_alter(\Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display, array $context) {
  // Hide the 'user_picture' field from the register form.
  if ($context['entity_type'] == 'user' && $context['form_mode'] == 'register') {
    $form_display->setComponent('user_picture', [
      'region' => 'hidden',
    ]);
  }
}

/**
 * Provides custom base field definitions for a content entity type.
 *
 * Field (storage) definitions returned by this hook must run through the
 * regular field storage life-cycle operations: they need to be properly
 * installed, updated, and uninstalled. This would typically be done through the
 * Entity Update API provided by the entity definition update manager.
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
 * @see \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldDefinitions()
 * @see \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
 * @see https://www.drupal.org/node/3034742
 */
function hook_entity_base_field_info(\Drupal\Core\Entity\EntityTypeInterface $entity_type) {
  if ($entity_type->id() == 'node') {
    $fields = [];
    $fields['mymodule_text'] = BaseFieldDefinition::create('string')
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
 *
 * @todo WARNING: This hook will be changed in
 * https://www.drupal.org/node/2346329.
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
 * @see \Drupal\Core\Field\FieldDefinition
 * @see \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldDefinitions()
 *
 * @todo WARNING: This hook will be changed in
 * https://www.drupal.org/node/2346347.
 */
function hook_entity_bundle_field_info(\Drupal\Core\Entity\EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
  // Add a property only to nodes of the 'article' bundle.
  if ($entity_type->id() == 'node' && $bundle == 'article') {
    $fields = [];
    $storage_definitions = mymodule_entity_field_storage_info($entity_type);
    $fields['mymodule_bundle_field'] = FieldDefinition::createFromFieldStorageDefinition($storage_definitions['mymodule_bundle_field'])
      ->setLabel(t('Bundle Field'));
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
 *
 * @todo WARNING: This hook will be changed in
 * https://www.drupal.org/node/2346347.
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
 * Field storage definitions returned by this hook must run through the regular
 * field storage life-cycle operations: they need to be properly installed,
 * updated, and uninstalled. This would typically be done through the Entity
 * Update API provided by the entity definition update manager.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type definition.
 *
 * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
 *   An array of field storage definitions, keyed by field name.
 *
 * @see hook_entity_field_storage_info_alter()
 * @see \Drupal\Core\Field\FieldStorageDefinitionInterface
 * @see \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldStorageDefinitions()
 * @see \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
 * @see https://www.drupal.org/node/3034742
 */
function hook_entity_field_storage_info(\Drupal\Core\Entity\EntityTypeInterface $entity_type) {
  if (\Drupal::entityTypeManager()->getStorage($entity_type->id()) instanceof DynamicallyFieldableEntityStorageInterface) {
    // Query by filtering on the ID as this is more efficient than filtering
    // on the entity_type property directly.
    $ids = \Drupal::entityQuery('field_storage_config')
      ->condition('id', $entity_type->id() . '.', 'STARTS_WITH')
      ->execute();
    // Fetch all fields and key them by field name.
    $field_storages = FieldStorageConfig::loadMultiple($ids);
    $result = [];
    foreach ($field_storages as $field_storage) {
      $result[$field_storage->getName()] = $field_storage;
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
 *   EntityListBuilderInterface::getOperations().
 *
 * @see \Drupal\Core\Entity\EntityListBuilderInterface::getOperations()
 */
function hook_entity_operation(\Drupal\Core\Entity\EntityInterface $entity) {
  $operations = [];
  $operations['translate'] = [
    'title' => t('Translate'),
    'url' => \Drupal\Core\Url::fromRoute('foo_module.entity.translate'),
    'weight' => 50,
  ];

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
  $operations['translate']['title'] = t('Translate @entity_type', [
    '@entity_type' => $entity->getEntityTypeId(),
  ]);
  $operations['translate']['weight'] = 99;
}

/**
 * Control access to fields.
 *
 * This hook is invoked from
 * \Drupal\Core\Entity\EntityAccessControlHandler::fieldAccess() to let modules
 * grant or deny operations on fields.
 *
 * @param string $operation
 *   The operation to be performed. See
 *   \Drupal\Core\Entity\EntityAccessControlHandlerInterface::fieldAccess()
 *   for possible values.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
 *   The field definition.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user account to check.
 * @param \Drupal\Core\Field\FieldItemListInterface $items
 *   (optional) The entity field object for which to check access, or NULL if
 *   access is checked for the field definition, without any specific value
 *   available. Defaults to NULL.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *   The access result.
 *
 * @see \Drupal\Core\Entity\EntityAccessControlHandlerInterface::fieldAccess()
 */
function hook_entity_field_access($operation, \Drupal\Core\Field\FieldDefinitionInterface $field_definition, \Drupal\Core\Session\AccountInterface $account, \Drupal\Core\Field\FieldItemListInterface $items = NULL) {
  if ($field_definition->getName() == 'field_of_interest' && $operation == 'edit') {
    return AccessResult::allowedIfHasPermission($account, 'update field of interest');
  }
  return AccessResult::neutral();
}

/**
 * Alter the default access behavior for a given field.
 *
 * Use this hook to override access grants from another module. Note that the
 * original default access flag is masked under the ':default' key.
 *
 * @param \Drupal\Core\Access\AccessResultInterface[] $grants
 *   An array of grants gathered by hook_entity_field_access(). The array is
 *   keyed by the module that defines the field's access control; the values are
 *   grant responses for each module (\Drupal\Core\Access\AccessResult).
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
  if ($field_definition->getName() == 'field_of_interest' && $grants['node']->isForbidden()) {
    // Override node module's restriction to no opinion (neither allowed nor
    // forbidden). We don't want to provide our own access hook, we only want to
    // take out node module's part in the access handling of this field. We also
    // don't want to switch node module's grant to
    // AccessResultInterface::isAllowed() , because the grants of other modules
    // should still decide on their own if this field is accessible or not
    $grants['node'] = AccessResult::neutral()->inheritCacheability($grants['node']);
  }
}

/**
 * Acts when initializing a fieldable entity object.
 *
 * This hook runs after a new entity object or a new entity translation object
 * has just been instantiated. It can be used to set initial values, e.g. to
 * provide defaults.
 *
 * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_ENTITY_TYPE_field_values_init()
 */
function hook_entity_field_values_init(\Drupal\Core\Entity\FieldableEntityInterface $entity) {
  if ($entity instanceof \Drupal\Core\Entity\ContentEntityInterface && !$entity->foo->value) {
    $entity->foo->value = 'some_initial_value';
  }
}

/**
 * Acts when initializing a fieldable entity object.
 *
 * This hook runs after a new entity object or a new entity translation object
 * has just been instantiated. It can be used to set initial values, e.g. to
 * provide defaults.
 *
 * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
 *   The entity object.
 *
 * @ingroup entity_crud
 * @see hook_entity_field_values_init()
 */
function hook_ENTITY_TYPE_field_values_init(\Drupal\Core\Entity\FieldableEntityInterface $entity) {
  if (!$entity->foo->value) {
    $entity->foo->value = 'some_initial_value';
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
 *   \Drupal\Core\Entity\EntityFieldManagerInterface::getExtraFields().
 */
function hook_entity_extra_field_info() {
  $extra = [];
  $module_language_enabled = \Drupal::moduleHandler()->moduleExists('language');
  $description = t('Node module element');

  foreach (NodeType::loadMultiple() as $bundle) {

    // Add also the 'language' select if Language module is enabled and the
    // bundle has multilingual support.
    // Visibility of the ordering of the language selector is the same as on the
    // node/add form.
    if ($module_language_enabled) {
      $configuration = ContentLanguageSettings::loadByEntityTypeBundle('node', $bundle->id());
      if ($configuration->isLanguageAlterable()) {
        $extra['node'][$bundle->id()]['form']['language'] = [
          'label' => t('Language'),
          'description' => $description,
          'weight' => 0,
        ];
      }
    }
    $extra['node'][$bundle->id()]['display']['language'] = [
      'label' => t('Language'),
      'description' => $description,
      'weight' => 0,
      'visible' => FALSE,
    ];
  }

  return $extra;
}

/**
 * Alter "pseudo-field" components on content entities.
 *
 * @param array $info
 *   The array structure is identical to that of the return value of
 *   \Drupal\Core\Entity\EntityFieldManagerInterface::getExtraFields().
 *
 * @see hook_entity_extra_field_info()
 */
function hook_entity_extra_field_info_alter(&$info) {
  // Force node title to always be at the top of the list by default.
  foreach (NodeType::loadMultiple() as $bundle) {
    if (isset($info['node'][$bundle->id()]['form']['title'])) {
      $info['node'][$bundle->id()]['form']['title']['weight'] = -20;
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
