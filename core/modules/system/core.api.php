<?php

/**
 * @file
 * Documentation landing page and topics, plus core library hooks.
 */

/**
 * @mainpage
 * Welcome to the Drupal API Documentation!
 *
 * This site is an API reference for Drupal, generated from comments embedded
 * in the source code. More in-depth documentation can be found at
 * https://drupal.org/developing/api.
 *
 * Here are some topics to help you get started developing with Drupal.
 *
 * @section essentials Essential background concepts
 *
 * - @link architecture Drupal's architecture @endlink
 * - @link oo_conventions Object-oriented conventions used in Drupal @endlink
 * - @link extending Extending and altering Drupal @endlink
 * - @link best_practices Security and best practices @endlink
 *
 * @section interface User interface
 *
 * - @link menu Routing, page controllers, and menu entries @endlink
 * - @link form_api Forms @endlink
 * - @link block_api Blocks @endlink
 * - @link ajax Ajax @endlink
 *
 * @section store_retrieve Storing and retrieving data
 *
 * - @link entity_api Entities @endlink
 * - @link config_api Configuration API @endlink
 * - @link state_api State API @endlink
 * - @link field Fields @endlink
 * - @link node_overview Node system @endlink
 * - @link views_overview Views @endlink
 * - @link database Database abstraction layer @endlink
 *
 * @section other_essentials Other essential APIs
 *
 * - @link i18n Internationalization @endlink
 * - @link cache Caching @endlink
 * - @link utility Utility classes and functions @endlink
 * - @link user_api User accounts, permissions, and roles @endlink
 * - @link theme_render Theme system and render API @endlink
 * - @link migration Migration @endlink
 *
 * @section additional Additional topics
 *
 * - @link batch Batch API @endlink
 * - @link queue Queue API @endlink
 * - @link container Services and the Dependency Injection Container @endlink
 * - @link typed_data Typed Data @endlink
 * - @link testing Automated tests @endlink
 * - @link third_party Integrating third-party applications @endlink
 *
 * @section more_info Further information
 *
 * - @link https://api.drupal.org/api/drupal/groups/8 All topics @endlink
 * - @link https://drupal.org/project/examples Examples project (sample modules) @endlink
 * - @link https://drupal.org/list-changes API change notices @endlink
 * - @link https://drupal.org/developing/api/8 Drupal 8 API longer references @endlink
 */

/**
 * @defgroup block_api Block API
 * @{
 * Information about the classes and interfaces that make up the Block API.
 *
 * Blocks are a combination of a configuration entity and a plugin. The
 * configuration entity stores placement information (theme, region, weight) and
 * any other configuration that is specific to the block. The block plugin does
 * the work of rendering the block's content for display.
 *
 * To define a block in a module you need to:
 * - Define a Block plugin by creating a new class that implements the
 *   \Drupal\block\BlockPluginInterface. For more information about how block
 *   plugins are discovered see the @link plugin_api Plugin API topic @endlink.
 * - Usually you will want to extend the \Drupal\block\BlockBase class, which
 *   provides a common configuration form and utility methods for getting and
 *   setting configuration in the block configuration entity.
 * - Block plugins use the annotations defined by
 *   \Drupal\block\Annotation\Block. See the
 *   @link annotation Annotations topic @endlink for more information about
 *   annotations.
 *
 * Further information and examples:
 * - \Drupal\system\Plugin\Block\SystemPoweredByBlock provides a simple example
 *   of defining a block.
 * - \Drupal\book\Plugin\Block\BookNavigationBlock is an example of a block with
 *   a custom configuration form.
 * - For a more in-depth discussion of the Block API see
 *   https://drupal.org/developing/api/8/block_api
 * - The examples project also provides a Block example in
 *   https://drupal.org/project/examples.
 */

/**
 * @defgroup third_party REST and Application Integration
 * @{
 * Integrating third-party applications using REST and related operations.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 * @}
 */

/**
 * @defgroup state_api State API
 * @{
 * Information about the State API.
 *
 * The State API is one of several methods in Drupal for storing information.
 * See @link architecture Drupal's architecture topic @endlink for an
 * overview of the different types of information.
 *
 * The basic entry point into the State API is \Drupal::state(), which returns
 * an object of class \Drupal\Core\State\StateInterface. This class has
 * methods for storing and retrieving state information; each piece of state
 * information is associated with a string-valued key. Example:
 * @code
 * // Get the state class.
 * $state = \Drupal::state();
 * // Find out when cron was last run; the key is 'system.cron_last'.
 * $time = $state->get('system.cron_last');
 * // Set the cron run time to the current request time.
 * $state->set('system_cron_last', REQUEST_TIME);
 * @endcode
 *
 * For more on the State API, see https://drupal.org/developing/api/8/state
 * @}
 */

/**
 * @defgroup config_api Configuration API
 * @{
 * Information about the Configuration API.
 *
 * The Configuration API is one of several methods in Drupal for storing
 * information. See @link architecture Drupal's architecture topic @endlink for
 * an overview of the different types of information. The sections below have
 * more information about the configuration API; see
 * https://drupal.org/developing/api/8/configuration for more details.
 *
 * @section sec_storage Configuration storage
 * In Drupal, there is a concept of the "active" configuration, which is the
 * configuration that is currently in use for a site. The storage used for the
 * active configuration is configurable: it could be in the database, in files
 * in a particular directory, or in other storage backends; the default storage
 * is in the database. Module developers must use the configuration API to
 * access the active configuration, rather than being concerned about the
 * details of where and how it is stored.
 *
 * Configuration is divided into individual objects, each of which has a
 * unique name or key. Some modules will have only one configuration object,
 * typically called 'mymodule.settings'; some modules will have many. Within
 * a configuration object, configuration settings have data types (integer,
 * string, Boolean, etc.) and settings can also exist in a nested hierarchy,
 * known as a "mapping".
 *
 * @section sec_yaml Configuration YAML files
 * Whether or not configuration files are being used for the active
 * configuration storage on a particular site, configuration files are always
 * used for:
 * - Defining the default configuration for a module, which is imported to the
 *   active storage when the module is enabled. Note that changes to this
 *   default configuration after a module is already enabled have no effect;
 *   to make a configuration change after a module is enabled, you would need
 *   to uninstall/reinstall or use a hook_update_N() function.
 * - Exporting and importing configuration.
 *
 * The file storage format for configuration information in Drupal is @link
 * http://en.wikipedia.org/wiki/YAML YAML files. @endlink Configuration is
 * divided into files, each containing one configuration object. The file name
 * for a configuration object is equal to the unique name of the configuration,
 * with a '.yml' extension. The default configuration files for each module are
 * placed in the config/install directory under the top-level module directory,
 * so look there in most Core modules for examples.
 *
 * Each configuration file has a specific structure, which is expressed as a
 * YAML-based configuration schema. The configuration schema details the
 * structure of the configuration, its data types, and which of its values need
 * to be translatable. Each module needs to define its configuration schema in
 * files in the config/schema directory under the top-level module directory, so
 * look there in most Core modules for examples.
 *
 * @section sec_simple Simple configuration
 * The simple configuration API should be used for information that will always
 * have exactly one copy or version. For instance, if your module has a
 * setting that is either on or off, then this is only defined once, and it
 * would be a Boolean-valued simple configuration setting.
 *
 * The first task in using the simple configuration API is to define the
 * configuration file structure, file name, and schema of your settings (see
 * @ref sec_yaml above). Once you have done that, you can retrieve the
 * active configuration object that corresponds to configuration file
 * mymodule.foo.yml with a call to:
 * @code
 * $config = \Drupal::config('mymodule.foo');
 * @endcode
 *
 * This will be an object of class \Drupal\Core\Config\Config, which has methods
 * for getting and setting configuration information.  For instance, if your
 * YAML file structure looks like this:
 * @code
 * enabled: '0'
 * bar:
 *   baz: 'string1'
 *   boo: 34
 * @endcode
 * you can make calls such as:
 * @code
 * // Get a single value.
 * $enabled = $config->get('enabled');
 * // Get an associative array.
 * $bar = $config->get('bar');
 * // Get one element of the array.
 * $bar_baz = $config->get('bar.baz');
 * // Update a value. Nesting works the same as get().
 * $config->set('bar.baz', 'string2');
 * // Nothing actually happens with set() until you call save().
 * $config->save();
 * @endcode
 *
 * @section sec_entity Configuration entities
 * In contrast to the simple configuration settings described in the previous
 * section, if your module allows users to create zero or more items (where
 * "items" are things like content type definitions, view definitions, and the
 * like), then you need to define a configuration entity type to store your
 * configuration. Creating an entity type, loading entites, and querying them
 * are outlined in the @link entity_api Entity API topic. @endlink Here are a
 * few additional steps and notes specific to configuration entities:
 * - For examples, look for classes that implement
 *   \Drupal\Core\Config\Entity\ConfigEntityInterface -- one good example is
 *   the \Drupal\user\Entity\Role entity type.
 * - In the entity type annotation, you will need to define a 'config_prefix'
 *   string. When Drupal stores a configuration item, it will be given a name
 *   composed of your module name, your chosen config prefix, and the ID of
 *   the individual item, separated by '.'. For example, in the Role entity,
 *   the config prefix is 'role', so one configuration item might be named
 *   user.role.anonymous, with configuration file user.role.anonymous.yml.
 * - You will need to define the schema for your configuration in your
 *   modulename.schema.yml file, with an entry for 'modulename.config_prefix.*'.
 *   For example, for the Role entity, the file user.schema.yml has an entry
 *   user.role.*; see @ref sec_yaml above for more information.
 * - Your module may also provide a few configuration items to be installed by
 *   default, by adding configuration files to the module's config/install
 *   directory; see @ref sec_yaml above for more information.
 * - Some configuration entities have dependencies on other configuration
 *   entities, and module developers need to consider this so that configuration
 *   can be imported, uninstalled, and synchronized in the right order. For
 *   example, a field display configuration entity would need to depend on
 *   field instance configuration, which depends on field and bundle
 *   configuration. Configuration entity classes expose dependencies by
 *   overriding the
 *   \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies()
 *   method.
 * @}
 */

/**
 * @defgroup entity_api Entity API
 * @{
 * Describes how to define and manipulate content and configuration entities.
 *
 * @todo Add an overview here, describing what an entity is, bundles, entity
 *   types, etc. at an overview level. And link to more detailed documentation:
 *   https://drupal.org/developing/api/entity
 *
 * @section types Types of entities
 * Entities can be used to store content or configuration information. See the
 * @link architecture Drupal's architecture topic @endlink for an overview of
 * the different types of information, and the
 * @link config_api Configuration API topic @endlink for more about the
 * configuration API. Defining and manipulating content and configuration
 * entities is very similar, and this is described in the sections below.
 *
 * @section define Defining an entity type
 *
 * @todo This section was written for Config entities. Add information about
 *   content entities to each item, and add additional items that are relevant
 *   to content entities, making sure to say they are not needed for config
 *   entities, such as view page controllers, etc.
 *
 * Here are the steps to follow to define a new entity type:
 * - Choose a unique machine name, or ID, for your entity type. This normally
 *   starts with (or is the same as) your module's machine name. It should be
 *   as short as possible, and may not exceed 32 characters.
 * - Define an interface for your entity's get/set methods, extending either
 *   \Drupal\Core\Config\Entity\ConfigEntityInterface or [content entity
 *   interface].
 * - Define a class for your entity, implementing your interface and extending
 *   either \Drupal\Core\Config\Entity\ConfigEntityBase or [content entity
 *   class] , with annotation for \@ConfigEntityType or [content entity
 *   annotation] in its documentation block.
 * - The 'id' annotation gives the entity type ID, and the 'label' annotation
 *   gives the human-readable name of the entity type.
 * - The annotation will refer to several controller classes, which you will
 *   also need to define:
 *   - list_builder: Define a class that extends
 *     \Drupal\Core\Config\Entity\ConfigEntityListBuilder or [content entity
       list builder], to provide an administrative overview for your entities.
 *   - add and edit forms, or default form: Define a class (or two) that
 *     extend(s) \Drupal\Core\Entity\EntityForm to provide add and edit forms
 *     for your entities.
 *   - delete form: Define a class that extends
 *     \Drupal\Core\Entity\EntityConfirmFormBase to provide a delete
 *     confirmation form for your entities.
 *   - access: If your configuration entity has complex permissions, you might
 *     need an access controller, implementing
 *     \Drupal\Core\Entity\EntityAccessControllerInterface, but most entities
 *     can just use the 'admin_permission' annotation instead.
 *
 * @section load_query Loading and querying entities
 * To load entities, use the entity storage manager, which is a class
 * implementing \Drupal\Core\Entity\EntityStorageInterface that you can
 * retrieve with:
 * @code
 * $storage = \Drupal::entityManager()->getStorage('your_entity_type');
 * @endcode
 *
 * To query to find entities to load, use an entity query, which is a class
 * implementing \Drupal\Core\Entity\Query\QueryInterface that you can retrieve
 * with:
 * @code
 * $storage = \Drupal::entityQuery('your_entity_type');
 * @endcode
 *
 * @todo Add additional relevant classes and interfaces to this topic using
 *   ingroup.
 * @}
 */

/**
 * @defgroup node_overview Nodes Overview
 * @{
 * Overview of how to interact with the Node system
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic. This topic should
 * describe node access, the node classes/interfaces, and the node hooks that a
 * developer would need to know about, at a high level, and link to more
 * detailed information.
 *
 * @see node_access
 * @see node_api_hooks
 * @}
 */

/**
 * @defgroup views_overview Views overview
 * @{
 * Overview of the Views module API
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic. Should link to all
 * or most of the existing Views topics, and maybe this should be moved into
 * a different file? This topic should be an overview so that developers know
 * which of the many Views classes and topics are important if they want to
 * accomplish tasks that they may have.
 * @}
 */


/**
 * @defgroup i18n Internationalization
 * @{
 * Internationalization and translation
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 *
 * See https://drupal.org/node/2133321 and https://drupal.org/node/303984
 * @}
 */

/**
 * @defgroup cache Cache API
 * @{
 * Information about the Drupal Cache API
 *
 * @section basics Basics
 *
 * Note: If not specified, all of the methods mentioned here belong to
 * \Drupal\Core\Cache\CacheBackendInterface.
 *
 * The Cache API is used to store data that takes a long time to
 * compute. Caching can be permanent, temporary, or valid for a certain
 * timespan, and the cache can contain any type of data.
 *
 * To use the Cache API:
 * - Request a cache object through \Drupal::cache() or by injecting a cache
 *   service.
 * - Define a Cache ID (cid) value for your data. A cid is a string, which must
 *   contain enough information to uniquely identify the data. For example, if
 *   your data contains translated strings, then your cid value must include the
 *   current interface language.
 * - Call the get() method to attempt a cache read, to see if the cache already
 *   contains your data.
 * - If your data is not already in the cache, compute it and add it to the
 *   cache using the set() method. The third argument of set() can be used to
 *   control the lifetime of your cache item.
 *
 * Example:
 * @code
 * $cid = 'mymodule_example:' . \Drupal::languageManager()->getCurrentLanguage()->id();
 *
 * $data = NULL;
 * if ($cache = \Drupal::cache()->get($cid)) {
 *   $data = $cache->data;
 * }
 * else {
 *   $data = my_module_complicated_calculation();
 *   \Drupal::cache()->set($cid, $data);
 * }
 * @endcode
 *
 * Note the use of $data and $cache->data in the above example. Calls to
 * \Drupal::cache()->get() return a record that contains the information stored
 * by \Drupal::cache()->set() in the data property as well as additional meta
 * information about the cached data. In order to make use of the cached data
 * you can access it via $cache->data.
 *
 * @section bins Cache bins
 *
 * Cache storage is separated into "bins", each containing various cache items.
 * Each bin can be configured separately; see @ref configuration.
 *
 * When you request a cache object, you can specify the bin name in your call to
 * \Drupal::cache(). Alternatively, you can request a bin by getting service
 * "cache.nameofbin" from the container. The default bin is called "default", with
 * service name "cache.default", it is used to store common and frequently used
 * caches.
 *
 * Other common cache bins are the following:
 *   - bootstrap: Small caches needed for the bootstrap on every request.
 *   - render: Contains cached HTML strings like cached pages and blocks, can
 *     grow to large size.
 *   - data: Contains data that can vary by path or similar context.
 *   - discovery: Contains cached discovery data for things such as plugins,
 *     views_data, or YAML discovered data such as library info.
 *
 * A module can define a cache bin by defining a service in its
 * modulename.services.yml file as follows (substituting the desired name for
 * "nameofbin"):
 * @code
 * cache.nameofbin:
 *   class: Drupal\Core\Cache\CacheBackendInterface
 *   tags:
 *     - { name: cache.bin }
 *   factory_method: get
 *   factory_service: cache_factory
 *   arguments: [nameofbin]
 * @endcode
 *
 * @section delete Deletion
 *
 * There are two ways to remove an item from the cache:
 * - Deletion (using delete(), deleteMultiple() or deleteAll()) permanently
 *   removes the item from the cache.
 * - Invalidation (using invalidate(), invalidateMultiple() or invalidateAll())
 *   is a "soft" delete that only marks items as "invalid", meaning "not fresh"
 *   or "not fresh enough". Invalid items are not usually returned from the
 *   cache, so in most ways they behave as if they have been deleted. However,
 *   it is possible to retrieve invalid items, if they have not yet been
 *   permanently removed by the garbage collector, by passing TRUE as the second
 *   argument for get($cid, $allow_invalid).
 *
 * Use deletion if a cache item is no longer useful; for instance, if the item
 * contains references to data that has been deleted. Use invalidation if the
 * cached item may still be useful to some callers until it has been updated
 * with fresh data. The fact that it was fresh a short while ago may often be
 * sufficient.
 *
 * Invalidation is particularly useful to protect against stampedes. Rather than
 * having multiple concurrent requests updating the same cache item when it
 * expires or is deleted, there can be one request updating the cache, while the
 * other requests can proceed using the stale value. As soon as the cache item
 * has been updated, all future requests will use the updated value.
 *
 * @section tags Cache Tags
 *
 * The fourth argument of the set() method can be used to specify cache tags,
 * which are used to identify what type of data is included in each cache item.
 * Each cache item can have multiple cache tags, and each cache tag has a string
 * key and a value. The value can be:
 * - TRUE, to indicate that this type of data is present in the cache item.
 * - An array of values. For example, the "node" tag indicates that particular
 *   node's data is present in the cache item, so its value is an array of node
 *   IDs.
 * Data that has been tagged can be deleted or invalidated as a group: no matter
 * the Cache ID (cid) of the cache item, no matter in which cache bin a cache
 * item lives; as long as it is tagged with a certain cache tag, it will be
 * deleted or invalidated.
 *
 * Because of that, cache tags are a solution to the cache invalidation problem:
 * - For caching to be effective, each cache item must only be invalidated when
 *   absolutely necessary. (i.e. maximizing the cache hit ratio.)
 * - For caching to be correct, each cache item that depends on a certain thing
 *   must be invalidated whenever that certain thing is modified.
 *
 * A typical scenario: a user has modified a node that appears in two views,
 * three blocks and on twelve pages. Without cache tags, we couldn't possibly
 * know which cache items to invalidate, so we'd have to invalidate everything:
 * we had to sacrifice effectiveness to achieve correctness. With cache tags, we
 * can have both.
 *
 * Example:
 * @code
 * // A cache item with nodes, users, and some custom module data.
 * $tags = array(
 *   'my_custom_tag' => TRUE,
 *   'node' => array(1, 3),
 *   'user' => array(7),
 * );
 * \Drupal::cache()->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);
 *
 * // Delete or invalidate all cache items with certain tags.
 * \Drupal\Core\Cache\Cache::deleteTags(array('node' => array(1));
 * \Drupal\Core\Cache\Cache::invalidateTags(array('user' => array(1)));
 * @endcode
 *
 * Drupal is a content management system, so naturally you want changes to your
 * content to be reflected everywhere, immediately. That's why we made sure that
 * every entity type in Drupal 8 automatically has support for cache tags: when
 * you save an entity, you can be sure that the cache items that have the
 * corresponding cache tags will be invalidated.
 * This also is the case when you define your own entity types: you'll get the
 * exact same cache tag invalidation as any of the built-in entity types, with
 * the ability to override any of the default behavior if needed.
 * See \Drupal\Core\Entity\EntityInterface::getCacheTag(),
 * \Drupal\Core\Entity\EntityInterface::getListCacheTags(),
 * \Drupal\Core\Entity\Entity::invalidateTagsOnSave() and
 * \Drupal\Core\Entity\Entity::invalidateTagsOnDelete().
 *
 * @todo Update cache tag deletion in https://drupal.org/node/918538
 *
 * @section configuration Configuration
 *
 * By default cached data is stored in the database. This can be configured
 * though so that all cached data, or that of an individual cache bin, uses a
 * different cache backend, such as APC or Memcache, for storage.
 *
 * In a settings.php file, you can override the service used for a particular
 * cache bin. For example, if your service implementation of
 * \Drupal\Core\Cache\CacheBackendInterface was called cache.custom, the
 * following line would make Drupal use it for the 'cache_render' bin:
 * @code
 *  $settings['cache']['bins']['render'] = 'cache.custom';
 * @endcode
 *
 * Additionally, you can register your cache implementation to be used by
 * default for all cache bins with:
 * @code
 *  $settings['cache']['default'] = 'cache.custom';
 * @endcode
 *
 * @see https://drupal.org/node/1884796
 * @}
 */

/**
 * @defgroup user_api User Accounts System
 * @{
 * API for user accounts, access checking, roles, and permissions.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 * @}
 */

/**
 * @defgroup theme_render Theme system and Render API
 * @{
 * Overview of the theme system and render API
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 * @}
 */

/**
 * @defgroup container Services and Dependency Injection Container
 * @{
 * Overview of the Dependency Injection Container and Services.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 *
 * See https://drupal.org/node/2133171
 * @}
 */

/**
 * @defgroup typed_data Typed Data API
 * @{
 * API for defining what type of data is used in fields, configuration, etc.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 *
 * See https://drupal.org/node/1794140
 * @}
 */

/**
 * @defgroup migration Migration API
 * @{
 * Overview of the Migration API, which migrates data into Drupal.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 *
 * See https://drupal.org/node/2127611
 * @}
 */

/**
 * @defgroup testing Automated tests
 * @{
 * Overview of PHPUnit tests and Simpletest tests.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 *
 * See https://drupal.org/simpletest and https://drupal.org/phpunit
 * @}
 */

/**
 * @defgroup architecture Architecture overview
 * @{
 * Overview of Drupal's architecture for developers.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 *
 * Should include: modules, info.yml files, location of files, etc.
 *
 * @section Types of information in Drupal
 * Drupal has several distinct types of information, each with its own methods
 * for storage and retrieval:
 * - Content: Information meant to be displayed on your site: articles, basic
 *   pages, images, files, custom blocks, etc. Content is stored and accessed
 *   using @link entity_api Entities @endlink.
 * - Session: Information about individual users' interactions with the site,
 *   such as whether they are logged in. This is really "state" information, but
 *   it is not stored the same way so it's a separate type here. Session
 *   information is managed ...
 * - State: Information of a temporary nature, generally machine-generated and
 *   not human-edited, about the current state of your site. Examples: the time
 *   when Cron was last run, whether node access permissions need rebuilding,
 *   etc. See @link state_api the State API topic @endlink for more information.
 * - Configuration: Information about your site that is generally (or at least
 *   can be) human-edited, but is not Content, and is meant to be relatively
 *   permanent. Examples: the name of your site, the content types and views
 *   you have defined, etc. See
 *   @link config_api the Configuration API topic @endlink for more information.
 *
 * @todo Add something relevant to the list item about sessions.
 * @todo Add some information about Settings, the key-value store in general,
 *   and maybe the cache to this list (not sure if cache belongs here?).
 * @}
 */

/**
 * @defgroup extending Extending and altering Drupal
 * @{
 * Overview of add-ons and alteration methods for Drupal.
 *
 * Drupal's core behavior can be extended and altered via these three basic
 * types of add-ons:
 * - Themes: Themes alter the appearance of Drupal sites. They can include
 *   template files, which alter the HTML markup and other raw output of the
 *   site; CSS files, which alter the styling applied to the HTML; and
 *   JavaScript, Flash, images, and other files. For more information, see the
 *   @link theme_render Theme system and render API topic @endlink and
 *   https://drupal.org/theme-guide/8
 * - Modules: Modules add to or alter the behavior and functionality of Drupal,
 *   by using one or more of the methods listed below. For more information
 *   about creating modules, see https://drupal.org/developing/modules/8
 * - Installation profiles: Installation profiles can be used to
 *   create distributions, which are complete specific-purpose packages of
 *   Drupal including additional modules, themes, and data. For more
 *   information, see https://drupal.org/documentation/build/distributions.
 *
 * Here is a list of the ways that modules can alter or extend Drupal's core
 * behavior, or the behavior of other modules:
 * - Hooks: Specially-named functions that a module defines, which are
 *   discovered and called at specific times, usually to alter behavior or data.
 *   See the @link hooks Hooks topic @endlink for more information.
 * - Plugins: Classes that a module defines, which are discovered and
 *   instantiated at specific times to add functionality. See the
 *   @link plugins Plugin API topic @endlink for more information.
 * - Entities: Special plugins that define entity types for storing new types
 *   of content or configuration in Drupal. See the
 *   @link entity_api Entity API topic @endlink for more information.
 * - Services: Classes that perform basic operations within Drupal, such as
 *   accessing the database and sending e-mail. See the
 *   @link container Dependency Injection Container and Services topic @endlink
 *   for more information.
 * - Routing: Providing or altering "routes", which are URLs that Drupal
 *   responds to, or altering routing behavior with event listener classes.
 *   See the @link menu Routing and menu topic @endlink for more information.
 * @}
 */

/**
 * @defgroup plugins Plugin API
 * @{
 * Overview of the Plugin API
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 *
 * See https://drupal.org/developing/api/8/plugins and links therein for
 * references. This should be an overview and link to details.
 *
 * @see annotation
 * @}
 */

/**
 * @defgroup oo_conventions Objected-oriented programming conventions
 * @{
 * PSR-4, namespaces, class naming, and other conventions.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 *
 * See https://drupal.org/node/608152 and links therein for references. This
 * should be an overview and link to details. It needs to cover: PSR-*,
 * namespaces, link to reference on OO, class naming conventions (base classes,
 * etc.), and other things developers should know related to object-oriented
 * coding.
 * @}
 */

/**
 * @defgroup best_practices Best practices for developers
 * @{
 * Overview of standards and best practices for developers
 *
 * Ideally, all code that is included in Drupal Core and contributed modules,
 * themes, and distributions will be secure, internationalized, maintainable,
 * and efficient. In order to facilitate this, the Drupal community has
 * developed a set of guidelines and standards for developers to follow. Most of
 * these standards can be found under
 * @link https://drupal.org/developing/best-practices Best practices on Drupal.org @endlink
 *
 * Standards and best practices that developers should be aware of include:
 * - Security: https://drupal.org/writing-secure-code and the
 *   @link sanitization Sanitization functions topic @endlink
 * - Coding standards: https://drupal.org/coding-standards
 *   and https://drupal.org/coding-standards/docs
 * - Accessibility: https://drupal.org/node/1637990 (modules) and
 *   https://drupal.org/node/464472 (themes)
 * - Usability: https://drupal.org/ui-standards
 * - Internationalization: @link i18n Internationalization topic @endlink
 * - Automated testing: @link testing Automated tests topic @endlink
 * @}
 */

/**
 * @defgroup utility Utility classes and functions
 * @{
 * Overview of utility classes and functions for developers.
 *
 * Drupal provides developers with a variety of utility functions that make it
 * easier and more efficient to perform tasks that are either really common,
 * tedious, or difficult. Utility functions help to reduce code duplication and
 * should be used in place of one-off code whenever possible.
 *
 * @see common.inc
 * @see file
 * @see format
 * @see mail.inc
 * @see php_wrappers
 * @see sanitization
 * @see transliteration
 * @see validation
 * @}
 */
