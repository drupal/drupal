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
 * https://www.drupal.org/developing/api.
 *
 * Here are some topics to help you get started developing with Drupal.
 *
 * @section essentials Essential background concepts
 *
 * - @link oo_conventions Object-oriented conventions used in Drupal @endlink
 * - @link extending Extending and altering Drupal @endlink
 * - @link best_practices Security and best practices @endlink
 * - @link info_types Types of information in Drupal @endlink
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
 * - @link field Fields @endlink
 * - @link config_api Configuration API @endlink
 * - @link state_api State API @endlink
 * - @link views_overview Views @endlink
 * - @link database Database abstraction layer @endlink
 *
 * @section other_essentials Other essential APIs
 *
 * - @link plugin_api Plugins @endlink
 * - @link container Services and the Dependency Injection Container @endlink
 * - @link events Events @endlink
 * - @link i18n Internationalization @endlink
 * - @link cache Caching @endlink
 * - @link utility Utility classes and functions @endlink
 * - @link user_api User accounts, permissions, and roles @endlink
 * - @link theme_render Render API @endlink
 * - @link themeable Theme system @endlink
 * - @link migration Migration @endlink
 *
 * @section additional Additional topics
 *
 * - @link batch Batch API @endlink
 * - @link queue Queue API @endlink
 * - @link typed_data Typed Data @endlink
 * - @link testing Automated tests @endlink
 * - @link third_party Integrating third-party applications @endlink
 *
 * @section more_info Further information
 *
 * - @link https://api.drupal.org/api/drupal/groups/8 All topics @endlink
 * - @link https://www.drupal.org/project/examples Examples project (sample modules) @endlink
 * - @link https://www.drupal.org/list-changes API change notices @endlink
 * - @link https://www.drupal.org/developing/api/8 Drupal 8 API longer references @endlink
 */

/**
 * @defgroup third_party REST and Application Integration
 * @{
 * Integrating third-party applications using REST and related operations.
 *
 * @section sec_overview Overview of web services
 * Web services make it possible for applications and web sites to read and
 * update information from other web sites. There are several standard
 * techniques for providing web services, including:
 * - SOAP: http://en.wikipedia.org/wiki/SOAP SOAP
 * - XML-RPC: http://en.wikipedia.org/wiki/XML-RPC
 * - REST: http://en.wikipedia.org/wiki/Representational_state_transfer
 * Drupal sites can both provide web services and integrate third-party web
 * services.
 *
 * @section sec_rest_overview Overview of REST
 * The REST technique uses basic HTTP requests to obtain and update data, where
 * each web service defines a specific API (HTTP GET and/or POST parameters and
 * returned response) for its HTTP requests. REST requests are separated into
 * several types, known as methods, including:
 * - GET: Requests to obtain data.
 * - PUT: Requests to update or create data.
 * - PATCH: Requests to update a subset of data, such as one field.
 * - DELETE: Requests to delete data.
 * The Drupal Core REST module provides support for GET, PUT, PATCH, and DELETE
 * quests on entities, GET requests on the database log from the Database
 * Logging module, and a plugin framework for providing REST support for other
 * data and other methods.
 *
 * REST requests can be authenticated. The Drupal Core Basic Auth module
 * provides authentication using the HTTP Basic protocol; the contributed module
 * OAuth (https://www.drupal.org/project/oauth) implements the OAuth
 * authentication protocol. You can also use cookie-based authentication, which
 * would require users to be logged into the Drupal site while using the
 * application on the third-party site that is using the REST service.
 *
 * @section sec_rest Enabling REST for entities and the log
 * Here are the steps to take to use the REST operations provided by Drupal
 * Core:
 * - Enable the REST module, plus Basic Auth (or another authentication method)
 *   and HAL.
 * - Node entity support is configured by default. If you would like to support
 *   other types of entities, you can copy
 *   core/modules/rest/config/install/rest.settings.yml to your staging
 *   configuration directory, appropriately modified for other entity types,
 *   and import it. Support for GET on the log from the Database Logging module
 *   can also be enabled in this way; in this case, the 'entity:node' line
 *   in the configuration would be replaced by the appropriate plugin ID,
 *   'dblog'.
 * - Set up permissions to allow the desired REST operations for a role, and set
 *   up one or more user accounts to perform the operations.
 * - To perform a REST operation, send a request to either the canonical URL
 *   for an entity (such as node/12345 for a node), or if the entity does not
 *   have a canonical URL, a URL like entity/(type)/(ID). The URL for a log
 *   entry is dblog/(ID). The request must have the following properties:
 *   - The request method must be set to the REST method you are using (POST,
 *     GET, PATCH, etc.).
 *   - The content type for the data you send, or the accept type for the
 *     data you are receiving, must be set to 'application/hal+json'.
 *   - If you are sending data, it must be JSON-encoded.
 *   - You'll also need to make sure the authentication information is sent
 *     with the request, unless you have allowed access to anonymous users.
 *
 * For more detailed information on setting up REST, see
 * https://www.drupal.org/documentation/modules/rest.
 *
 * @section sec_plugins Defining new REST plugins
 * The REST framework in the REST module has support built in for entities, but
 * it is also an extensible plugin-based system. REST plugins implement
 * interface \Drupal\rest\Plugin\ResourceInterface, and generally extend base
 * class \Drupal\rest\Plugin\ResourceBase. They are annotated with
 * \Drupal\rest\Annotation\RestResource annotation, and must be in plugin
 * namespace subdirectory Plugin\rest\resource. For more information on how to
 * create plugins, see the @link plugin_api Plugin API topic. @endlink
 *
 * If you create a new REST plugin, you will also need to enable it by
 * providing default configuration or configuration import, as outlined in
 * @ref sec_rest above.
 *
 * @section sec_integrate Integrating data from other sites into Drupal
 * If you want to integrate data from other web sites into Drupal, here are
 * some notes:
 * - There are contributed modules available for integrating many third-party
 *   sites into Drupal. Search on https://www.drupal.org/project/project_module
 * - If there is not an existing module, you will need to find documentation on
 *   the specific web services API for the site you are trying to integrate.
 * - There are several classes and functions that are useful for interacting
 *   with web services:
 *   - You should make requests using the 'http_client' service, which
 *     implements \GuzzleHttp\ClientInterface. See the
 *     @link container Services topic @endlink for more information on
 *     services. If you cannot use dependency injection to retrieve this
 *     service, the \Drupal::httpClient() method is available. A good example
 *     of how to use this service can be found in
 *     \Drupal\aggregator\Plugin\aggregator\fetcher\DefaultFetcher
 *   - \Drupal\Component\Serialization\Json (JSON encoding and decoding).
 *   - PHP has functions and classes for parsing XML; see
 *     http://php.net/manual/refs.xml.php
 * @}
 */

/**
 * @defgroup state_api State API
 * @{
 * Information about the State API.
 *
 * The State API is one of several methods in Drupal for storing information.
 * See the @link info_types Information types topic @endlink for an
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
 * $state->set('system.cron_last', REQUEST_TIME);
 * @endcode
 *
 * For more on the State API, see https://www.drupal.org/developing/api/8/state
 * @}
 */

/**
 * @defgroup config_api Configuration API
 * @{
 * Information about the Configuration API.
 *
 * The Configuration API is one of several methods in Drupal for storing
 * information. See the @link info_types Information types topic @endlink for
 * an overview of the different types of information. The sections below have
 * more information about the configuration API; see
 * https://www.drupal.org/developing/api/8/configuration for more details.
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
 * Configuration can also be overridden on a global, per-language, or
 * per-module basis. See https://www.drupal.org/node/1928898 for more
 * information.
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
 * The file storage format for configuration information in Drupal is
 * @link http://en.wikipedia.org/wiki/YAML YAML files. @endlink Configuration is
 * divided into files, each containing one configuration object. The file name
 * for a configuration object is equal to the unique name of the configuration,
 * with a '.yml' extension. The default configuration files for each module are
 * placed in the config/install directory under the top-level module directory,
 * so look there in most Core modules for examples.
 *
 * @section sec_schema Configuration schema and translation
 * Each configuration file has a specific structure, which is expressed as a
 * YAML-based configuration schema. The configuration schema details the
 * structure of the configuration, its data types, and which of its values need
 * to be translatable. Each module needs to define its configuration schema in
 * files in the config/schema directory under the top-level module directory, so
 * look there in most Core modules for examples.
 *
 * Configuration can be internationalized; see the
 * @link i18n Internationalization topic @endlink for more information. Data
 * types label, text, and date_format in configuration schema are translatable;
 * string is non-translatable text (the 'translatable' property on a schema
 * data type definition indicates that it is translatable).
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
 * configuration. Creating an entity type, loading entities, and querying them
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
 *   field configuration, which depends on field and bundle configuration.
 *   Configuration entity classes expose dependencies by overriding the
 *   \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies()
 *   method.
 * - On routes for paths staring with '/admin' or otherwise designated as
 *   administration paths (such as node editing when it is set as an admin
 *   operation), if they have configuration entity placeholders, configuration
 *   entities are normally loaded in their original language, without
 *   translations or other overrides. This is usually desirable, because most
 *   admin paths are for editing configuration, and you need that to be in the
 *   source language and to lack possibly dynamic overrides. If for some reason
 *   you need to have your configuration entity loaded in the currently-selected
 *   language on an admin path (for instance, if you go to
 *   example.com/es/admin/your_path and you need the entity to be in Spanish),
 *   then you can add a 'with_config_overrides' parameter option to your route.
 *   The same applies if you need to load the entity with overrides (or
 *   translated) on an admin path like '/node/add/article' (when configured to
 *   be an admin path). Here's an example using the configurable_language config
 *   entity:
 *   @code
 *   mymodule.myroute:
 *     path: '/admin/mypath/{configurable_language}'
 *     defaults:
 *       _controller: '\Drupal\mymodule\MyController::myMethod'
 *     options:
 *       parameters:
 *         configurable_language:
 *           type: entity:configurable_language
 *           with_config_overrides: TRUE
 *   @endcode
 *   With the route defined this way, the $configurable_language parameter to
 *   your controller method will come in translated to the current language.
 *   Without the parameter options section, it would be in the original
 *   language, untranslated.
 *
 * @see i18n
 *
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
 *   interface text language selected for page.
 * - Call the get() method to attempt a cache read, to see if the cache already
 *   contains your data.
 * - If your data is not already in the cache, compute it and add it to the
 *   cache using the set() method. The third argument of set() can be used to
 *   control the lifetime of your cache item.
 *
 * Example:
 * @code
 * $cid = 'mymodule_example:' . \Drupal::languageManager()->getCurrentLanguage()->getId();
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
 *   factory: cache_factory:get
 *   arguments: [nameofbin]
 * @endcode
 * See the @link container Services topic @endlink for more on defining
 * services.
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
 * which are used to identify which data is included in each cache item. A cache
 * item can have multiple cache tags (an array of cache tags), and each cache
 * tag is a string. The convention is to generate cache tags of the form
 * [prefix]:[suffix]. Usually, you'll want to associate the cache tags of
 * entities, or entity listings. You won't have to manually construct cache tags
 * for them — just get their cache tags via
 * \Drupal\Core\Entity\EntityInterface::getCacheTags() and
 * \Drupal\Core\Entity\EntityTypeInterface::getListCacheTags().
 * Data that has been tagged can be invalidated as a group: no matter the Cache
 * ID (cid) of the cache item, no matter in which cache bin a cache item lives;
 * as long as it is tagged with a certain cache tag, it will be invalidated.
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
 *   'my_custom_tag',
 *   'node:1',
 *   'node:3',
 *   'user:7',
 * );
 * \Drupal::cache()->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);
 *
 * // Invalidate all cache items with certain tags.
 * \Drupal\Core\Cache\Cache::invalidateTags(array('user:1'));
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
 * See \Drupal\Core\Entity\EntityInterface::getCacheTags(),
 * \Drupal\Core\Entity\EntityTypeInterface::getListCacheTags(),
 * \Drupal\Core\Entity\Entity::invalidateTagsOnSave() and
 * \Drupal\Core\Entity\Entity::invalidateTagsOnDelete().
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
 * @see https://www.drupal.org/node/1884796
 * @}
 */

/**
 * @defgroup user_api User accounts, permissions, and roles
 * @{
 * API for user accounts, access checking, roles, and permissions.
 *
 * @section sec_overview Overview and terminology
 * Drupal's permission system is based on the concepts of accounts, roles,
 * and permissions.
 *
 * Users (site visitors) have accounts, which include a user name, an email
 * address, a password (or some other means of authentication), and possibly
 * other fields (if defined on the site). Anonymous users have an implicit
 * account that does not have a real user name or any account information.
 *
 * Each user account is assigned one or more roles. The anonymous user account
 * automatically has the anonymous user role; real user accounts
 * automatically have the authenticated user role, plus any roles defined on
 * the site that they have been assigned.
 *
 * Each role, including the special anonymous and authenticated user roles, is
 * granted one or more named permissions, which allow them to perform certain
 * tasks or view certain content on the site. It is possible to designate a
 * role to be the "administrator" role; if this is set up, this role is
 * automatically granted all available permissions whenever a module is
 * enabled that defines permissions.
 *
 * All code in Drupal that allows users to perform tasks or view content must
 * check that the current user has the correct permission before allowing the
 * action. In the standard case, access checking consists of answering the
 * question "Does the current user have permission 'foo'?", and allowing or
 * denying access based on the answer. Note that access checking should nearly
 * always be done at the permission level, not by checking for a particular role
 * or user ID, so that site administrators can set up user accounts and roles
 * appropriately for their particular sites.
 *
 * @section sec_define Defining permissions
 * Modules define permissions via a $module.permissions.yml file. See
 * \Drupal\user\PermissionHandler for documentation of permissions.yml files.
 *
 * @section sec_access Access permission checking
 * Depending on the situation, there are several methods for ensuring that
 * access checks are done properly in Drupal:
 * - Routes: When you register a route, include a 'requirements' section that
 *   either gives the machine name of the permission that is needed to visit the
 *   URL of the route, or tells Drupal to use an access check method or service
 *   to check access. See the @link menu Routing topic @endlink for more
 *   information.
 * - Entities: Access for various entity operations is designated either with
 *   simple permissions or access control handler classes in the entity
 *   annotation. See the @link entity_api Entity API topic @endlink for more
 *   information.
 * - Other code: There is a 'current_user' service, which can be injected into
 *   classes to provide access to the current user account (see the
 *   @link container Services and Dependency Injection topic @endlink for more
 *   information on dependency injection). In code that cannot use dependency
 *   injection, you can access this service and retrieve the current user
 *   account object by calling \Drupal::currentUser(). Once you have a user
 *   object for the current user (implementing \Drupal\user\UserInterface), you
 *   can call inherited method
 *   \Drupal\Core\Session\AccountInterface::hasPermission() to check
 *   permissions, or pass this object into other functions/methods.
 * - Forms: Each element of a form array can have a Boolean '#access' property,
 *   which determines whether that element is visible and/or usable. This is a
 *   common need in forms, so the current user service (described above) is
 *   injected into the form base class as method
 *   \Drupal\Core\Form\FormBase::currentUser().
 *
 * @section sec_entities User and role objects
 * User objects in Drupal are entity items, implementing
 * \Drupal\user\UserInterface. Role objects in Drupal are also entity items,
 * implementing \Drupal\user\RoleInterface. See the
 * @link entity_api Entity API topic @endlink for more information about
 * entities in general (including how to load, create, modify, and query them).
 *
 * Roles often need to be manipulated in automated test code, such as to add
 * permissions to them. Here's an example:
 * @code
 * $role = \Drupal\user\Entity\Role::load('authenticated');
 * $role->grantPermission('access comments');
 * $role->save();
 * @endcode
 *
 * Other important interfaces:
 * - \Drupal\Core\Session\AccountInterface: The part of UserInterface that
 *   deals with access checking. In writing code that checks access, your
 *   method parameters should use this interface, not UserInterface.
 * - \Drupal\Core\Session\AccountProxyInterface: The interface for the
 *   current_user service (described above).
 * @}
 */

/**
 * @defgroup container Services and Dependency Injection Container
 * @{
 * Overview of the Dependency Injection Container and Services.
 *
 * @section sec_overview Overview of container, injection, and services
 * The Services and Dependency Injection Container concepts have been adopted by
 * Drupal from the @link http://symfony.com/ Symfony framework. @endlink A
 * "service" (such as accessing the database, sending email, or translating user
 * interface text) is defined (given a name and an interface or at least a
 * class that defines the methods that may be called), and a default class is
 * defined to provide the service. These two steps must be done together, and
 * can be done by Drupal Core or a module. Other modules can then define
 * alternative classes to provide the same services, overriding the default
 * classes. Classes and functions that need to use the service should always
 * instantiate the class via the dependency injection container (also known
 * simply as the "container"), rather than instantiating a particular service
 * provider class directly, so that they get the correct class (default or
 * overridden).
 *
 * See https://www.drupal.org/node/2133171 for more detailed information on
 * services and the dependency injection container.
 *
 * @section sec_discover Discovering existing services
 * Drupal core defines many core services in the core.services.yml file (in the
 * top-level core directory). Some Drupal Core modules and contributed modules
 * also define services in modulename.services.yml files. API reference sites
 * (such as https://api.drupal.org) generate lists of all existing services from
 * these files, or you can look through the individual files manually.
 *
 * A typical service definition in a *.services.yml file looks like this:
 * @code
 * path.alias_manager:
 *   class: Drupal\Core\Path\AliasManager
 *   arguments: ['@path.crud', '@path.alias_whitelist', '@language_manager']
 * @endcode
 * Some services use other services as factories; a typical service definition
 * is:
 * @code
 *   cache.entity:
 *     class: Drupal\Core\Cache\CacheBackendInterface
 *     tags:
 *       - { name: cache.bin }
 *     factory: cache_factory:get
 *     arguments: [entity]
 * @endcode
 *
 * The first line of a service definition gives the unique machine name of the
 * service. This is often prefixed by the module name if provided by a module;
 * however, by convention some service names are prefixed by a group name
 * instead, such as cache.* for cache bins and plugin.manager.* for plugin
 * managers.
 *
 * The class line either gives the default class that provides the service, or
 * if the service uses a factory class, the interface for the service. If the
 * class depends on other services, the arguments line lists the machine
 * names of the dependencies (preceded by '@'); objects for each of these
 * services are instantiated from the container and passed to the class
 * constructor when the service class is instantiated. Other arguments can also
 * be passed in; see the section at https://www.drupal.org/node/2133171 for more
 * detailed information.
 *
 * Services using factories can be defined as shown in the above example, if the
 * factory is itself a service. The factory can also be a class; details of how
 * to use service factories can be found in the section at
 * https://www.drupal.org/node/2133171.
 *
 * @section sec_container Accessing a service through the container
 * As noted above, if you need to use a service in your code, you should always
 * instantiate the service class via a call to the container, using the machine
 * name of the service, so that the default class can be overridden. There are
 * several ways to make sure this happens:
 * - For service-providing classes, see other sections of this documentation
 *   describing how to pass services as arguments to the constructor.
 * - Plugin classes, controllers, and similar classes have create() or
 *   createInstance() methods that are used to create an instance of the class.
 *   These methods come from different interfaces, and have different
 *   arguments, but they all include an argument $container of type
 *   \Symfony\Component\DependencyInjection\ContainerInterface.
 *   If you are defining one of these classes, in the create() or
 *   createInstance() method, call $container->get('myservice.name') to
 *   instantiate a service. The results of these calls are generally passed to
 *   the class constructor and saved as member variables in the class.
 * - For functions and class methods that do not have access to either of
 *   the above methods of dependency injection, you can use service location to
 *   access services, via a call to the global \Drupal class. This class has
 *   special methods for accessing commonly-used services, or you can call a
 *   generic method to access any service. Examples:
 *   @code
 *   // Retrieve the entity.manager service object (special method exists).
 *   $manager = \Drupal::entityManager();
 *   // Retrieve the service object for machine name 'foo.bar'.
 *   $foobar = \Drupal::service('foo.bar');
 *   @endcode
 *
 * As a note, you should always use dependency injection (via service arguments
 * or create()/createInstance() methods) if possible to instantiate services,
 * rather than service location (via the \Drupal class), because:
 * - Dependency injection facilitates writing unit tests, since the container
 *   argument can be mocked and the create() method can be bypassed by using
 *   the class constructor. If you use the \Drupal class, unit tests are much
 *   harder to write and your code has more dependencies.
 * - Having the service interfaces on the class constructor and member variables
 *   is useful for IDE auto-complete and self-documentation.
 *
 * @section sec_define Defining a service
 * If your module needs to define a new service, here are the steps:
 * - Choose a unique machine name for your service. Typically, this should
 *   start with your module name. Example: mymodule.myservice.
 * - Create a PHP interface to define what your service does.
 * - Create a default class implementing your interface that provides your
 *   service. If your class needs to use existing services (such as database
 *   access), be sure to make these services arguments to your class
 *   constructor, and save them in member variables. Also, if the needed
 *   services are provided by other modules and not Drupal Core, you'll want
 *   these modules to be dependencies of your module.
 * - Add an entry to a modulename.services.yml file for the service. See
 *   @ref sec_discover above, or existing *.services.yml files in Core, for the
 *   syntax; it will start with your machine name, refer to your default class,
 *   and list the services that need to be passed into your constructor.
 *
 * Services can also be defined dynamically, as in the
 * \Drupal\Core\CoreServiceProvider class, but this is less common for modules.
 *
 * @section sec_tags Service tags
 * Some services have tags, which are defined in the service definition. See
 * @link service_tag Service Tags @endlink for usage.
 *
 * @section sec_injection Overriding the default service class
 * Modules can override the default classes used for services. Here are the
 * steps:
 * - Define a class in the top-level namespace for your module
 *   (Drupal\my_module), whose name is the camel-case version of your module's
 *   machine name followed by "ServiceProvider" (for example, if your module
 *   machine name is my_module, the class must be named
 *   MyModuleServiceProvider).
 * - The class needs to implement
 *   \Drupal\Core\DependencyInjection\ServiceModifierInterface, which is
 *   typically done by extending
 *   \Drupal\Core\DependencyInjection\ServiceProviderBase.
 * - The class needs to contain one method: alter(). This method does the
 *   actual work of telling Drupal to use your class instead of the default.
 *   Here's an example:
 *   @code
 *   public function alter(ContainerBuilder $container) {
 *     // Override the language_manager class with a new class.
 *     $definition = $container->getDefinition('language_manager');
 *     $definition->setClass('Drupal\my_module\MyLanguageManager');
 *   }
 *   @endcode
 *   Note that $container here is an instance of
 *   \Drupal\Core\DependencyInjection\ContainerBuilder.
 *
 * @see https://www.drupal.org/node/2133171
 * @see core.services.yml
 * @see \Drupal
 * @see \Symfony\Component\DependencyInjection\ContainerInterface
 * @see plugin_api
 * @see menu
 * @}
 */

/**
 * @defgroup typed_data Typed Data API
 * @{
 * API for describing data based on a set of available data types.
 *
 * The Typed Data API was created to provide developers with a consistent
 * interface for interacting with data, as well as an API for metadata
 * (information about the data, such as the data type, whether it is
 * translatable, and who can access it). The Typed Data API is used in several
 * Drupal sub-systems, such as the Entity Field API and Configuration API.
 *
 * See https://www.drupal.org/node/1794140 for more information about the Typed
 * Data API.
 *
 * @section interfaces Interfaces and classes in the Typed Data API
 * There are several basic interfaces in the Typed Data API, representing
 * different types of data:
 * - \Drupal\Core\TypedData\PrimitiveInterface: Used for primitive data, such
 *   as strings, numeric types, etc. Drupal provides primitive types for
 *   integers, strings, etc. based on this interface, and you should
 *   not ever need to create new primitive types.
 * - \Drupal\Core\TypedData\TypedDataInterface: Used for single pieces of data,
 *   with some information about its context. Abstract base class
 *   \Drupal\Core\TypedData\TypedData is a useful starting point, and contains
 *   documentation on how to extend it.
 * - \Drupal\Core\TypedData\ComplexDataInterface: Used for complex data, which
 *   contains named and typed properties; extends TypedDataInterface. Examples
 *   of complex data include content entities and field items. See the
 *   @link entity_api Entity API topic @endlink for more information about
 *   entities; for most complex data, developers should use entities.
 * - \Drupal\Core\TypedData\ListInterface: Used for a sequential list of other
 *   typed data. Class \Drupal\Core\TypedData\Plugin\DataType\ItemList is a
 *   generic implementation of this interface, and it is used by default for
 *   data declared as a list of some other data type. You can also define a
 *   custom list class, in which case ItemList is a useful base class.
 *
 * @section defining Defining data types
 * To define a new data type:
 * - Create a class that implements one of the Typed Data interfaces.
 *   Typically, you will want to extend one of the classes listed in the
 *   section above as a starting point.
 * - Make your class into a DataType plugin. To do that, put it in namespace
 *   \Drupal\yourmodule\Plugin\DataType (where "yourmodule" is your module's
 *   short name), and add annotation of type
 *   \Drupal\Core\TypedData\Annotation\DataType to the documentation header.
 *   See the @link plugin_api Plugin API topic @endlink and the
 *   @link annotation Annotations topic @endlink for more information.
 *
 * @section using Using data types
 * The data types of the Typed Data API can be used in several ways, once they
 * have been defined:
 * - In the Field API, data types can be used as the class in the property
 *   definition of the field. See the @link field Field API topic @endlink for
 *   more information.
 * - In configuration schema files, you can use the unique ID ('id' annotation)
 *   from any DataType plugin class as the 'type' value for an entry. See the
 *   @link config_api Confuration API topic @endlink for more information.
 * @}
 */

/**
 * @defgroup testing Automated tests
 * @{
 * Overview of PHPUnit tests and Simpletest tests.
 *
 * The Drupal project has embraced a philosophy of using automated tests,
 * consisting of both unit tests (which test the functionality of classes at a
 * low level) and functional tests (which test the functionality of Drupal
 * systems at a higher level, usually involving web output). The goal is to
 * have test coverage for all or most of the components and features, and to
 * run the automated tests before any code is changed or added, to make sure
 * it doesn't break any existing functionality (regression testing).
 *
 * In order to implement this philosophy, developers need to do the following:
 * - When making a patch to fix a bug, make sure that the bug fix patch includes
 *   a test that fails without the code change and passes with the code change.
 *   This helps reviewers understand what the bug is, demonstrates that the code
 *   actually fixes the bug, and ensures the bug will not reappear due to later
 *   code changes.
 * - When making a patch to implement a new feature, include new unit and/or
 *   functional tests in the patch. This serves to both demonstrate that the
 *   code actually works, and ensure that later changes do not break the new
 *   functionality.
 *
 * @section write_unit Writing PHPUnit tests for classes
 * PHPUnit tests for classes are written using the industry-standard PHPUnit
 * framework. Use a PHPUnit test to test functionality of a class if the Drupal
 * environment (database, settings, etc.) and web browser are not needed for the
 * test, or if the Drupal environment can be replaced by a "mock" object. To
 * write a PHPUnit test:
 * - Define a class that extends \Drupal\Tests\UnitTestCase.
 * - The class name needs to end in the word Test.
 * - The namespace must be a subspace/subdirectory of \Drupal\yourmodule\Tests,
 *   where yourmodule is your module's machine name.
 * - The test class file must be named and placed under the yourmodule/tests/src
 *   directory, according to the PSR-4 standard.
 * - Your test class needs a phpDoc comment block with a description and
 *   a @group annotation, which gives information about the test.
 * - Methods in your test class whose names start with 'test' are the actual
 *   test cases. Each one should test a logical subset of the functionality.
 * For more details, see:
 * - https://www.drupal.org/phpunit for full documentation on how to write
 *   PHPUnit tests for Drupal.
 * - http://phpunit.de for general information on the PHPUnit framework.
 * - @link oo_conventions Object-oriented programming topic @endlink for more
 *   on PSR-4, namespaces, and where to place classes.
 *
 * @section write_functional Writing functional tests
 * Functional tests are written using a Drupal-specific framework that is, for
 * historical reasons, known as "Simpletest". Use a Simpletest test to test the
 * functionality of sub-system of Drupal, if the functionality depends on the
 * Drupal database and settings, or to test the web output of Drupal. To
 * write a Simpletest test:
 * - For functional tests of the web output of Drupal, define a class that
 *   extends \Drupal\simpletest\WebTestBase, which contains an internal web
 *   browser and defines many helpful test assertion methods that you can use
 *   in your tests. You can specify modules to be enabled by defining a
 *   $modules member variable -- keep in mind that by default, WebTestBase uses
 *   a "testing" install profile, with a minimal set of modules enabled.
 * - For functional tests that do not test web output, define a class that
 *   extends \Drupal\simpletest\KernelTestBase. This class is much faster
 *   than WebTestBase, because instead of making a full install of Drupal, it
 *   uses an in-memory pseudo-installation (similar to what the installer and
 *   update scripts use). To use this test class, you will need to create the
 *   database tables you need and install needed modules manually.
 * - The namespace must be a subspace/subdirectory of \Drupal\yourmodule\Tests,
 *   where yourmodule is your module's machine name.
 * - The test class file must be named and placed under the yourmodule/src/Tests
 *   directory, according to the PSR-4 standard.
 * - Your test class needs a phpDoc comment block with a description and
 *   a @group annotation, which gives information about the test.
 * - You may also override the default setUp() method, which can set be used to
 *   set up content types and similar procedures.
 * - In some cases, you may need to write a test module to support your test;
 *   put such modules under the yourmodule/tests/modules directory.
 * - Methods in your test class whose names start with 'test', and which have
 *   no arguments, are the actual test cases. Each one should test a logical
 *   subset of the functionality, and each one runs in a new, isolated test
 *   environment, so it can only rely on the setUp() method, not what has
 *   been set up by other test methods.
 * For more details, see:
 * - https://www.drupal.org/simpletest for full documentation on how to write
 *   functional tests for Drupal.
 * - @link oo_conventions Object-oriented programming topic @endlink for more
 *   on PSR-4, namespaces, and where to place classes.
 *
 * @section running Running tests
 * You can run both Simpletest and PHPUnit tests by enabling the core Testing
 * module (core/modules/simpletest). Once that module is enabled, tests can be
 * run using the core/scripts/run-tests.sh script, using
 * @link https://www.drupal.org/project/drush Drush @endlink, or from the
 *   Testing module user interface.
 *
 * PHPUnit tests can also be run from the command line, using the PHPUnit
 * framework. See https://www.drupal.org/node/2116263 for more information.
 * @}
 */

/**
 * @defgroup info_types Information types
 * @{
 * Types of information in Drupal.
 *
 * Drupal has several distinct types of information, each with its own methods
 * for storage and retrieval:
 * - Content: Information meant to be displayed on your site: articles, basic
 *   pages, images, files, custom blocks, etc. Content is stored and accessed
 *   using @link entity_api Entities @endlink.
 * - Session: Information about individual users' interactions with the site,
 *   such as whether they are logged in. This is really "state" information, but
 *   it is not stored the same way so it's a separate type here. Session
 *   information is available from the Request object. The session implements
 *   \Symfony\Component\HttpFoundation\Session\SessionInterface.
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
 * @see cache
 * @see i18n
 * @}
 */

/**
 * @defgroup extending Extending and altering Drupal
 * @{
 * Overview of extensions and alteration methods for Drupal.
 *
 * @section sec_types Types of extensions
 * Drupal's core behavior can be extended and altered via these three basic
 * types of extensions:
 * - Themes: Themes alter the appearance of Drupal sites. They can include
 *   template files, which alter the HTML markup and other raw output of the
 *   site; CSS files, which alter the styling applied to the HTML; and
 *   JavaScript, Flash, images, and other files. For more information, see the
 *   @link theme_render Theme system and render API topic @endlink and
 *   https://www.drupal.org/theme-guide/8
 * - Modules: Modules add to or alter the behavior and functionality of Drupal,
 *   by using one or more of the methods listed below. For more information
 *   about creating modules, see https://www.drupal.org/developing/modules/8
 * - Installation profiles: Installation profiles can be used to
 *   create distributions, which are complete specific-purpose packages of
 *   Drupal including additional modules, themes, and data. For more
 *   information, see https://www.drupal.org/developing/distributions.
 *
 * @section sec_alter Alteration methods for modules
 * Here is a list of the ways that modules can alter or extend Drupal's core
 * behavior, or the behavior of other modules:
 * - Hooks: Specially-named functions that a module defines, which are
 *   discovered and called at specific times, usually to alter behavior or data.
 *   See the @link hooks Hooks topic @endlink for more information.
 * - Plugins: Classes that a module defines, which are discovered and
 *   instantiated at specific times to add functionality. See the
 *   @link plugin_api Plugin API topic @endlink for more information.
 * - Entities: Special plugins that define entity types for storing new types
 *   of content or configuration in Drupal. See the
 *   @link entity_api Entity API topic @endlink for more information.
 * - Services: Classes that perform basic operations within Drupal, such as
 *   accessing the database and sending email. See the
 *   @link container Dependency Injection Container and Services topic @endlink
 *   for more information.
 * - Routing: Providing or altering "routes", which are URLs that Drupal
 *   responds to, or altering routing behavior with event listener classes.
 *   See the @link menu Routing and menu topic @endlink for more information.
 * - Events: Modules can register as event subscribers; when an event is
 *   dispatched, a method is called on each registered subscriber, allowing each
 *   one to react. See the @link events Events topic @endlink for more
 *   information.
 *
 * @section sec_sample *.info.yml files
 * Extensions must each be located in a directory whose name matches the short
 * name (or machine name) of the extension, and this directory must contain a
 * file named machine_name.info.yml (where machine_name is the machine name of
 * the extension). See \Drupal\Core\Extension\InfoParserInterface::parse() for
 * documentation of the format of .info.yml files.
 * @}
 */

/**
 * @defgroup plugin_api Plugin API
 * @{
 * Using the Plugin API
 *
 * @section sec_overview Overview and terminology
 *
 * The basic idea of plugins is to allow a particular module or subsystem of
 * Drupal to provide functionality in an extensible, object-oriented way. The
 * controlling module or subsystem defines the basic framework (interface) for
 * the functionality, and other modules can create plugins (implementing the
 * interface) with particular behaviors. The controlling module instantiates
 * existing plugins as needed, and calls methods to invoke their functionality.
 * Examples of functionality in Drupal Core that use plugins include: the block
 * system (block types are plugins), the entity/field system (entity types,
 * field types, field formatters, and field widgets are plugins), the image
 * manipulation system (image effects and image toolkits are plugins), and the
 * search system (search page types are plugins).
 *
 * Plugins are grouped into plugin types, each generally defined by an
 * interface. Each plugin type is managed by a plugin manager service, which
 * uses a plugin discovery method to discover provided plugins of that type and
 * instantiate them using a plugin factory.
 *
 * Some plugin types make use of the following concepts or components:
 * - Plugin derivatives: Allows a single plugin class to present itself as
 *   multiple plugins. Example: the Menu module provides a block for each
 *   defined menu via a block plugin derivative.
 * - Plugin mapping: Allows a plugin class to map a configuration string to an
 *   instance, and have the plugin automatically instantiated without writing
 *   additional code.
 * - Plugin collections: Provide a way to lazily instantiate a set of plugin
 *   instances from a single plugin definition.
 *
 * There are several things a module developer may need to do with plugins:
 * - Define a completely new plugin type: see @ref sec_define below.
 * - Create a plugin of an existing plugin type: see @ref sec_create below.
 * - Perform tasks that involve plugins: see @ref sec_use below.
 *
 * See https://www.drupal.org/developing/api/8/plugins for more detailed
 * documentation on the plugin system. There are also topics for a few
 * of the many existing types of plugins:
 * - @link block_api Block API @endlink
 * - @link entity_api Entity API @endlink
 * - @link field Various types of field-related plugins @endlink
 * - @link views_plugins Views plugins @endlink (has links to topics covering
 *   various specific types of Views plugins).
 * - @link search Search page plugins @endlink
 *
 * @section sec_define Defining a new plugin type
 * To define a new plugin type:
 * - Define an interface for the plugin. This describes the common set of
 *   behavior, and the methods you will call on each plugin class that is
 *   instantiated. Usually this interface will extend one or more of the
 *   following interfaces:
 *   - \Drupal\Component\Plugin\PluginInspectionInterface
 *   - \Drupal\Component\Plugin\ConfigurablePluginInterface
 *   - \Drupal\Component\Plugin\ContextAwarePluginInterface
 *   - \Drupal\Core\Plugin\PluginFormInterface
 *   - \Drupal\Core\Executable\ExecutableInterface
 * - (optional) Create a base class that provides a partial implementation of
 *   the interface, for the convenience of developers wishing to create plugins
 *   of your type. The base class usually extends
 *   \Drupal\Core\Plugin\PluginBase, or one of the base classes that extends
 *   this class.
 * - Choose a method for plugin discovery, and define classes as necessary.
 *   See @ref sub_discovery below.
 * - Create a plugin manager/factory class and service, which will discover and
 *   instantiate plugins. See @ref sub_manager below.
 * - Use the plugin manager to instantiate plugins. Call methods on your plugin
 *   interface to perform the tasks of your plugin type.
 * - (optional) If appropriate, define a plugin collection. See @ref
 *    sub_collection below for more information.
 *
 * @subsection sub_discovery Plugin discovery
 * Plugin discovery is the process your plugin manager uses to discover the
 * individual plugins of your type that have been defined by your module and
 * other modules. Plugin discovery methods are classes that implement
 * \Drupal\Component\Plugin\Discovery\DiscoveryInterface. Most plugin types use
 * one of the following discovery mechanisms:
 * - Annotation: Plugin classes are annotated and placed in a defined namespace
 *   subdirectory. Most Drupal Core plugins use this method of discovery.
 * - Hook: Plugin modules need to implement a hook to tell the manager about
 *   their plugins.
 * - YAML: Plugins are listed in YAML files. Drupal Core uses this method for
 *   discovering local tasks and local actions. This is mainly useful if all
 *   plugins use the same class, so it is kind of like a global derivative.
 * - Static: Plugin classes are registered within the plugin manager class
 *   itself. Static discovery is only useful if modules cannot define new
 *   plugins of this type (if the list of available plugins is static).
 *
 * It is also possible to define your own custom discovery mechanism or mix
 * methods together. And there are many more details, such as annotation
 * decorators, that apply to some of the discovery methods. See
 * https://www.drupal.org/developing/api/8/plugins for more details.
 *
 * The remainder of this documentation will assume Annotation-based discovery,
 * since this is the most common method.
 *
 * @subsection sub_manager Defining a plugin manager class and service
 * To define an annotation-based plugin manager:
 * - Choose a namespace subdirectory for your plugin. For example, search page
 *   plugins go in directory Plugin/Search under the module namespace.
 * - Define an annotation class for your plugin type. This class should extend
 *   \Drupal\Component\Annotation\Plugin, and for most plugin types, it should
 *   contain member variables corresponding to the annotations plugins will
 *   need to provide. All plugins have at least $id: a unique string
 *   identifier.
 * - Define an alter hook for altering the discovered plugin definitions. You
 *   should document the hook in a *.api.php file.
 * - Define a plugin manager class. This class should implement
 *   \Drupal\Component\Plugin\PluginManagerInterface; most plugin managers do
 *   this by extending \Drupal\Core\Plugin\DefaultPluginManager. If you do
 *   extend the default plugin manager, the only method you will probably need
 *   to define is the class constructor, which will need to call the parent
 *   constructor to provide information about the annotation class and plugin
 *   namespace for discovery, set up the alter hook, and possibly set up
 *   caching. See classes that extend DefaultPluginManager for examples.
 * - Define a service for your plugin manager. See the
 *   @link container Services topic for more information. @endlink Your service
 *   definition should look something like this, referencing your manager
 *   class and the parent (default) plugin manager service to inherit
 *   constructor arguments:
 *   @code
 *   plugin.manager.mymodule:
 *     class: Drupal\mymodule\MyPluginManager
 *     parent: default_plugin_manager
 *   @endcode
 * - If your plugin is configurable, you will also need to define the
 *   configuration schema and possibly a configuration entity type. See the
 *   @link config_api Configuration API topic @endlink for more information.
 *
 * @subsection sub_collection Defining a plugin collection
 * Some configurable plugin types allow administrators to create zero or more
 * instances of each plugin, each with its own configuration. For example,
 * a single block plugin can be configured several times, to display in
 * different regions of a theme, with different visibility settings, a
 * different title, or other plugin-specific settings. To make this possible,
 * a plugin type can make use of what's known as a plugin collection.
 *
 * A plugin collection is a class that extends
 * \Drupal\Component\Plugin\LazyPluginCollection or one of its subclasses; there
 * are several examples in Drupal Core. If your plugin type uses a plugin
 * collection, it will usually also have a configuration entity, and the entity
 * class should implement
 * \Drupal\Core\Entity\EntityWithPluginCollectionInterface. Again, there are
 * several examples in Drupal Core; see also the @link config_api Configuration
 * API topic @endlink for more information about configuration entities.
 *
 * @section sec_create Creating a plugin of an existing type
 * Assuming the plugin type uses annotation-based discovery, in order to create
 * a plugin of an existing type, you will be creating a class. This class must:
 * - Implement the plugin interface, so that it has the required methods
 *   defined. Usually, you'll want to extend the plugin base class, if one has
 *   been provided.
 * - Have the right annotation in its documentation header. See the
 *   @link annotation Annotation topic @endlink for more information about
 *   annotation.
 * - Be in the right plugin namespace, in order to be discovered.
 * Often, the easiest way to make sure this happens is to find an existing
 * example of a working plugin class of the desired type, and copy it into your
 * module as a starting point.
 *
 * You can also create a plugin derivative, which allows your plugin class
 * to present itself to the user interface as multiple plugins. To do this,
 * in addition to the plugin class, you'll need to create a separate plugin
 * derivative class implementing
 * \Drupal\Component\Plugin\Derivative\DerivativeInterface. The classes
 * \Drupal\system\Plugin\Block\SystemMenuBlock (plugin class) and
 * \Drupal\system\Plugin\Derivative\SystemMenuBlock (derivative class) are a
 * good example to look at.
 *
 * @section sec_use Performing tasks involving plugins
 * Here are the steps to follow to perform a task that involves plugins:
 * - Locate the machine name of the plugin manager service, and instantiate the
 *   service. See the @link container Services topic @endlink for more
 *   information on how to do this.
 * - On the plugin manager class, use methods like getDefinition(),
 *   getDefinitions(), or other methods specific to particular plugin managers
 *   to retrieve information about either specific plugins or the entire list of
 *   defined plugins.
 * - Call the createInstance() method on the plugin manager to instantiate
 *   individual plugin objects.
 * - Call methods on the plugin objects to perform the desired tasks.
 *
 * @see annotation
 * @}
 */

/**
 * @defgroup oo_conventions Objected-oriented programming conventions
 * @{
 * PSR-4, namespaces, class naming, and other conventions.
 *
 * A lot of the PHP code in Drupal is object oriented (OO), making use of
 * @link http://php.net/manual/language.oop5.php PHP classes, interfaces, and traits @endlink
 * (which are loosely referred to as "classes" in the rest of this topic). The
 * following conventions and standards apply to this version of Drupal:
 * - Each class must be in its own file.
 * - Classes must be namespaced. If a module defines a class, the namespace
 *   must start with \Drupal\module_name. If it is defined by Drupal Core for
 *   use across many modules, the namespace should be \Drupal\Core or
 *   \Drupal\Component, with the exception of the global class \Drupal. See
 *   https://www.drupal.org/node/1353118 for more about namespaces.
 * - In order for the PSR-4-based class auto-loader to find the class, it must
 *   be located in a directory corresponding to the namespace. For
 *   module-defined classes, if the namespace is \Drupal\module_name\foo\bar,
 *   then the class goes under the main module directory in directory
 *   src/foo/bar. For Drupal-wide classes, if the namespace is
 *   \Drupal\Core\foo\bar, then it goes in directory
 *   core/lib/Drupal/Core/foo/bar. See https://www.drupal.org/node/2156625 for
 *   more information about PSR-4.
 * - Some classes have annotations added to their documentation headers. See
 *   the @link annotation Annotation topic @endlink for more information.
 * - Standard plugin discovery requires particular namespaces and annotation
 *   for most plugin classes. See the
 *   @link plugin_api Plugin API topic @endlink for more information.
 * - There are project-wide coding standards for OO code, including naming:
 *   https://www.drupal.org/node/608152
 * - Documentation standards for classes are covered on:
 *   https://www.drupal.org/coding-standards/docs#classes
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
 * @link https://www.drupal.org/developing/best-practices Best practices on Drupal.org @endlink
 *
 * Standards and best practices that developers should be aware of include:
 * - Security: https://www.drupal.org/writing-secure-code and the
 *   @link sanitization Sanitization functions topic @endlink
 * - Coding standards: https://www.drupal.org/coding-standards
 *   and https://www.drupal.org/coding-standards/docs
 * - Accessibility: https://www.drupal.org/node/1637990 (modules) and
 *   https://www.drupal.org/node/464472 (themes)
 * - Usability: https://www.drupal.org/ui-standards
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
 * @see php_wrappers
 * @see sanitization
 * @see transliteration
 * @see validation
 * @}
 */

/**
 * @defgroup hooks Hooks
 * @{
 * Define functions that alter the behavior of Drupal core.
 *
 * One way for modules to alter the core behavior of Drupal (or another module)
 * is to use hooks. Hooks are specially-named functions that a module defines
 * (this is known as "implementing the hook"), which are discovered and called
 * at specific times to alter or add to the base behavior or data (this is
 * known as "invoking the hook"). Each hook has a name (example:
 * hook_batch_alter()), a defined set of parameters, and a defined return value.
 * Your modules can implement hooks that are defined by Drupal core or other
 * modules that they interact with. Your modules can also define their own
 * hooks, in order to let other modules interact with them.
 *
 * To implement a hook:
 * - Locate the documentation for the hook. Hooks are documented in *.api.php
 *   files, by defining functions whose name starts with "hook_" (these
 *   files and their functions are never loaded by Drupal -- they exist solely
 *   for documentation). The function should have a documentation header, as
 *   well as a sample function body. For example, in the core file
 *   system.api.php, you can find hooks such as hook_batch_alter(). Also, if
 *   you are viewing this documentation on an API reference site, the Core
 *   hooks will be listed in this topic.
 * - Copy the function to your module's .module file.
 * - Change the name of the function, substituting your module's short name
 *   (name of the module's directory, and .info.yml file without the extension)
 *   for the "hook" part of the sample function name. For instance, to implement
 *   hook_batch_alter(), you would rename it to my_module_batch_alter().
 * - Edit the documentation for the function (normally, your implementation
 *   should just have one line saying "Implements hook_batch_alter().").
 * - Edit the body of the function, substituting in what you need your module
 *   to do.
 *
 * To define a hook:
 * - Choose a unique name for your hook. It should start with "hook_", followed
 *   by your module's short name.
 * - Provide documentation in a *.api.php file in your module's main
 *   directory. See the "implementing" section above for details of what this
 *   should contain (parameters, return value, and sample function body).
 * - Invoke the hook in your module's code.
 *
 * To invoke a hook, use methods on
 * \Drupal\Core\Extension\ModuleHandlerInterface such as alter(), invoke(),
 * and invokeAll(). You can obtain a module handler by calling
 * \Drupal::moduleHandler(), or getting the 'module_handler' service on an
 * injected container.
 *
 * @see extending
 * @see themeable
 * @see callbacks
 * @see \Drupal\Core\Extension\ModuleHandlerInterface
 * @see \Drupal::moduleHandler()
 *
 * @}
 */

/**
 * @defgroup callbacks Callbacks
 * @{
 * Callback function signatures.
 *
 * Drupal's API sometimes uses callback functions to allow you to define how
 * some type of processing happens. A callback is a function with a defined
 * signature, which you define in a module. Then you pass the function name as
 * a parameter to a Drupal API function or return it as part of a hook
 * implementation return value, and your function is called at an appropriate
 * time. For instance, when setting up batch processing you might need to
 * provide a callback function for each processing step and/or a callback for
 * when processing is finished; you would do that by defining these functions
 * and passing their names into the batch setup function.
 *
 * Callback function signatures, like hook definitions, are described by
 * creating and documenting dummy functions in a *.api.php file; normally, the
 * dummy callback function's name should start with "callback_", and you should
 * document the parameters and return value and provide a sample function body.
 * Then your API documentation can refer to this callback function in its
 * documentation. A user of your API can usually name their callback function
 * anything they want, although a standard name would be to replace "callback_"
 * with the module name.
 *
 * @see hooks
 * @see themeable
 *
 * @}
 */

/**
 * @defgroup form_api Form generation
 * @{
 * Describes how to generate and manipulate forms and process form submissions.
 *
 * Drupal provides a Form API in order to achieve consistency in its form
 * processing and presentation, while simplifying code and reducing the amount
 * of HTML that must be explicitly generated by a module.
 *
 * @section generating_forms Creating forms
 * Forms are defined as classes that implement the
 * \Drupal\Core\Form\FormInterface and are built using the
 * \Drupal\Core\Form\FormBuilder class. Drupal provides a couple of utility
 * classes that can be extended as a starting point for most basic forms, the
 * most commonly used of which is \Drupal\Core\Form\FormBase. FormBuilder
 * handles the low level processing of forms such as rendering the necessary
 * HTML, initial processing of incoming $_POST data, and delegating to your
 * implementation of FormInterface for validation and processing of submitted
 * data.
 *
 * Here is an example of a Form class:
 * @code
 * namespace Drupal\mymodule\Form;
 *
 * use Drupal\Core\Form\FormBase;
 * use Drupal\Core\Form\FormStateInterface;
 *
 * class ExampleForm extends FormBase {
 *   public function getFormId() {
 *     // Unique ID of the form.
 *     return 'example_form';
 *   }
 *
 *   public function buildForm(array $form, FormStateInterface $form_state) {
 *     // Create a $form API array.
 *     $form['phone_number'] = array(
 *       '#type' => 'tel',
 *       '#title' => $this->t('Your phone number')
 *     );
 *     return $form;
 *   }
 *
 *   public function validateForm(array &$form, FormStateInterface $form_state) {
 *     // Validate submitted form data.
 *   }
 *
 *   public function submitForm(array &$form, FormStateInterface $form_state) {
 *     // Handle submitted form data.
 *   }
 * }
 * @endcode
 *
 * @section retrieving_forms Retrieving and displaying forms
 * \Drupal::formBuilder()->getForm() should be used to handle retrieving,
 * processing, and displaying a rendered HTML form. Given the ExampleForm
 * defined above,
 * \Drupal::formBuilder()->getForm('Drupal\mymodule\Form\ExampleForm') would
 * return the rendered HTML of the form defined by ExampleForm::buildForm(), or
 * call the validateForm() and submitForm(), methods depending on the current
 * processing state.
 *
 * The argument to \Drupal::formBuilder()->getForm() is the name of a class that
 * implements FormBuilderInterface. Any additional arguments passed to the
 * getForm() method will be passed along as additional arguments to the
 * ExampleForm::buildForm() method.
 *
 * For example:
 * @code
 * $extra = '612-123-4567';
 * $form = \Drupal::formBuilder()->getForm('Drupal\mymodule\Form\ExampleForm', $extra);
 * ...
 * public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL)
 *   $form['phone_number'] = array(
 *     '#type' => 'tel',
 *     '#title' => $this->t('Your phone number'),
 *     '#value' => $extra,
 *   );
 *   return $form;
 * }
 * @endcode
 *
 * Alternatively, forms can be built directly via the routing system which will
 * take care of calling \Drupal::formBuilder()->getForm(). The following example
 * demonstrates the use of a routing.yml file to display a form at the given
 * route.
 *
 * @code
 * example.form:
 *   path: '/example-form'
 *   defaults:
 *     _title: 'Example form'
 *     _form: '\Drupal\mymodule\Form\ExampleForm'
 * @endcode
 *
 * The $form argument to form-related functions is a structured array containing
 * the elements and properties of the form. For information on the array
 * components and format, and more detailed explanations of the Form API
 * workflow, see the
 * @link forms_api_reference.html Form API reference @endlink
 * and the
 * @link https://www.drupal.org/node/2117411 Form API documentation section. @endlink
 * In addition, there is a set of Form API tutorials in
 * @link form_example_tutorial.inc the Form Example Tutorial @endlink which
 * provide basics all the way up through multistep forms.
 *
 * In the form builder, validation, submission, and other form methods,
 * $form_state is the primary influence on the processing of the form and is
 * passed to most methods, so they can use it to communicate with the form
 * system and each other. $form_state is an object that implements
 * \Drupal\Core\Form\FormStateInterface.
 * @}
 */

/**
 * @defgroup queue Queue operations
 * @{
 * Queue items to allow later processing.
 *
 * The queue system allows placing items in a queue and processing them later.
 * The system tries to ensure that only one consumer can process an item.
 *
 * Before a queue can be used it needs to be created by
 * Drupal\Core\Queue\QueueInterface::createQueue().
 *
 * Items can be added to the queue by passing an arbitrary data object to
 * Drupal\Core\Queue\QueueInterface::createItem().
 *
 * To process an item, call Drupal\Core\Queue\QueueInterface::claimItem() and
 * specify how long you want to have a lease for working on that item.
 * When finished processing, the item needs to be deleted by calling
 * Drupal\Core\Queue\QueueInterface::deleteItem(). If the consumer dies, the
 * item will be made available again by the Drupal\Core\Queue\QueueInterface
 * implementation once the lease expires. Another consumer will then be able to
 * receive it when calling Drupal\Core\Queue\QueueInterface::claimItem().
 * Due to this, the processing code should be aware that an item might be handed
 * over for processing more than once.
 *
 * The $item object used by the Drupal\Core\Queue\QueueInterface can contain
 * arbitrary metadata depending on the implementation. Systems using the
 * interface should only rely on the data property which will contain the
 * information passed to Drupal\Core\Queue\QueueInterface::createItem().
 * The full queue item returned by Drupal\Core\Queue\QueueInterface::claimItem()
 * needs to be passed to Drupal\Core\Queue\QueueInterface::deleteItem() once
 * processing is completed.
 *
 * There are two kinds of queue backends available: reliable, which preserves
 * the order of messages and guarantees that every item will be executed at
 * least once. The non-reliable kind only does a best effort to preserve order
 * in messages and to execute them at least once but there is a small chance
 * that some items get lost. For example, some distributed back-ends like
 * Amazon SQS will be managing jobs for a large set of producers and consumers
 * where a strict FIFO ordering will likely not be preserved. Another example
 * would be an in-memory queue backend which might lose items if it crashes.
 * However, such a backend would be able to deal with significantly more writes
 * than a reliable queue and for many tasks this is more important. See
 * aggregator_cron() for an example of how to effectively use a non-reliable
 * queue. Another example is doing Twitter statistics -- the small possibility
 * of losing a few items is insignificant next to power of the queue being able
 * to keep up with writes. As described in the processing section, regardless
 * of the queue being reliable or not, the processing code should be aware that
 * an item might be handed over for processing more than once (because the
 * processing code might time out before it finishes).
 * @}
 */

/**
 * @defgroup annotation Annotations
 * @{
 * Annotations for class discovery and metadata description.
 *
 * The Drupal plugin system has a set of reusable components that developers
 * can use, override, and extend in their modules. Most of the plugins use
 * annotations, which let classes register themselves as plugins and describe
 * their metadata. (Annotations can also be used for other purposes, though
 * at the moment, Drupal only uses them for the plugin system.)
 *
 * To annotate a class as a plugin, add code similar to the following to the
 * end of the documentation block immediately preceding the class declaration:
 * @code
 * * @ContentEntityType(
 * *   id = "comment",
 * *   label = @Translation("Comment"),
 * *   ...
 * *   base_table = "comment"
 * * )
 * @endcode
 *
 * Note that you must use double quotes; single quotes will not work in
 * annotations.
 *
 * Some annotation types, which extend the "@ PluginID" annotation class, have
 * only a single 'id' key in their annotation. For these, it is possible to use
 * a shorthand annotation. For example:
 * @code
 * * @ViewsArea("entity")
 * @endcode
 * in place of
 * @code
 * * @ViewsArea(
 * *   id = "entity"
 * *)
 * @endcode
 *
 * The available annotation classes are listed in this topic, and can be
 * identified when you are looking at the Drupal source code by having
 * "@ Annotation" in their documentation blocks (without the space after @). To
 * find examples of annotation for a particular annotation class, such as
 * EntityType, look for class files that have an @ annotation section using the
 * annotation class.
 *
 * @see plugin_translatable
 * @see plugin_context
 *
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform periodic actions.
 *
 * Modules that require some commands to be executed periodically can
 * implement hook_cron(). The engine will then call the hook whenever a cron
 * run happens, as defined by the administrator. Typical tasks managed by
 * hook_cron() are database maintenance, backups, recalculation of settings
 * or parameters, automated mailing, and retrieving remote data.
 *
 * Short-running or non-resource-intensive tasks can be executed directly in
 * the hook_cron() implementation.
 *
 * Long-running tasks and tasks that could time out, such as retrieving remote
 * data, sending email, and intensive file tasks, should use the queue API
 * instead of executing the tasks directly. To do this, first define one or
 * more queues via a \Drupal\Core\Annotation\QueueWorker plugin. Then, add items
 * that need to be processed to the defined queues.
 */
function hook_cron() {
  // Short-running operation example, not using a queue:
  // Delete all expired records since the last cron run.
  $expires = \Drupal::state()->get('mymodule.cron_last_run', REQUEST_TIME);
  db_delete('mymodule_table')
    ->condition('expires', $expires, '>=')
    ->execute();
  \Drupal::state()->set('mymodule.cron_last_run', REQUEST_TIME);

  // Long-running operation example, leveraging a queue:
  // Fetch feeds from other sites.
  $result = db_query('SELECT * FROM {aggregator_feed} WHERE checked + refresh < :time AND refresh <> :never', array(
    ':time' => REQUEST_TIME,
    ':never' => AGGREGATOR_CLEAR_NEVER,
  ));
  $queue = \Drupal::queue('aggregator_feeds');
  foreach ($result as $feed) {
    $queue->createItem($feed);
  }
}

/**
 * Alter available data types for typed data wrappers.
 *
 * @param array $data_types
 *   An array of data type information.
 *
 * @see hook_data_type_info()
 */
function hook_data_type_info_alter(&$data_types) {
  $data_types['email']['class'] = '\Drupal\mymodule\Type\Email';
}

/**
 * Alter cron queue information before cron runs.
 *
 * Called by \Drupal\Core\Cron to allow modules to alter cron queue settings
 * before any jobs are processesed.
 *
 * @param array $queues
 *   An array of cron queue information.
 *
 * @see \Drupal\Core\QueueWorker\QueueWorkerInterface
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see \Drupal\Core\Cron
 */
function hook_queue_info_alter(&$queues) {
  // This site has many feeds so let's spend 90 seconds on each cron run
  // updating feeds instead of the default 60.
  $queues['aggregator_feeds']['cron']['time'] = 90;
}

/**
 * Alter an email message created with MailManagerInterface->mail().
 *
 * hook_mail_alter() allows modification of email messages created and sent
 * with MailManagerInterface->mail(). Usage examples include adding and/or
 * changing message text, message fields, and message headers.
 *
 * Email messages sent using functions other than MailManagerInterface->mail()
 * will not invoke hook_mail_alter(). For example, a contributed module directly
 * calling the MailInterface->mail() or PHP mail() function will not invoke
 * this hook. All core modules use MailManagerInterface->mail() for messaging,
 * it is best practice but not mandatory in contributed modules.
 *
 * @param $message
 *   An array containing the message data. Keys in this array include:
 *  - 'id':
 *     The MailManagerInterface->mail() id of the message. Look at module source
 *     code or MailManagerInterface->mail() for possible id values.
 *  - 'to':
 *     The address or addresses the message will be sent to. The
 *     formatting of this string must comply with RFC 2822.
 *  - 'from':
 *     The address the message will be marked as being from, which is
 *     either a custom address or the site-wide default email address.
 *  - 'subject':
 *     Subject of the email to be sent. This must not contain any newline
 *     characters, or the email may not be sent properly.
 *  - 'body':
 *     An array of strings containing the message text. The message body is
 *     created by concatenating the individual array strings into a single text
 *     string using "\n\n" as a separator.
 *  - 'headers':
 *     Associative array containing mail headers, such as From, Sender,
 *     MIME-Version, Content-Type, etc.
 *  - 'params':
 *     An array of optional parameters supplied by the caller of
 *     MailManagerInterface->mail() that is used to build the message before
 *     hook_mail_alter() is invoked.
 *  - 'language':
 *     The language object used to build the message before hook_mail_alter()
 *     is invoked.
 *  - 'send':
 *     Set to FALSE to abort sending this email message.
 *
 * @see \Drupal\Core\Mail\MailManagerInterface::mail()
 */
function hook_mail_alter(&$message) {
  if ($message['id'] == 'modulename_messagekey') {
    if (!example_notifications_optin($message['to'], $message['id'])) {
      // If the recipient has opted to not receive such messages, cancel
      // sending.
      $message['send'] = FALSE;
      return;
    }
    $message['body'][] = "--\nMail sent out from " . \Drupal::config('system.site')->get('name');
  }
}

/**
 * Prepares a message based on parameters;
 *
 * This hook is called from MailManagerInterface->mail(). Note that hook_mail(),
 * unlike hook_mail_alter(), is only called on the $module argument to
 * MailManagerInterface->mail(), not all modules.
 *
 * @param $key
 *   An identifier of the mail.
 * @param $message
 *   An array to be filled in. Elements in this array include:
 *   - id: An ID to identify the mail sent. Look at module source code or
 *     MailManagerInterface->mail() for possible id values.
 *   - to: The address or addresses the message will be sent to. The
 *     formatting of this string must comply with RFC 2822.
 *   - subject: Subject of the email to be sent. This must not contain any
 *     newline characters, or the mail may not be sent properly.
 *     MailManagerInterface->mail() sets this to an empty
 *     string when the hook is invoked.
 *   - body: An array of lines containing the message to be sent. Drupal will
 *     format the correct line endings for you. MailManagerInterface->mail()
 *     sets this to an empty array when the hook is invoked.
 *   - from: The address the message will be marked as being from, which is
 *     set by MailManagerInterface->mail() to either a custom address or the
 *     site-wide default email address when the hook is invoked.
 *   - headers: Associative array containing mail headers, such as From,
 *     Sender, MIME-Version, Content-Type, etc.
 *     MailManagerInterface->mail() pre-fills several headers in this array.
 * @param $params
 *   An array of parameters supplied by the caller of
 *   MailManagerInterface->mail().
 *
 * @see \Drupal\Core\Mail\MailManagerInterface->mail()
 */
function hook_mail($key, &$message, $params) {
  $account = $params['account'];
  $context = $params['context'];
  $variables = array(
    '%site_name' => \Drupal::config('system.site')->get('name'),
    '%username' => user_format_name($account),
  );
  if ($context['hook'] == 'taxonomy') {
    $entity = $params['entity'];
    $vocabulary = Vocabulary::load($entity->id());
    $variables += array(
      '%term_name' => $entity->name,
      '%term_description' => $entity->description,
      '%term_id' => $entity->id(),
      '%vocabulary_name' => $vocabulary->label(),
      '%vocabulary_description' => $vocabulary->getDescription(),
      '%vocabulary_id' => $vocabulary->id(),
    );
  }

  // Node-based variable translation is only available if we have a node.
  if (isset($params['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $params['node'];
    $variables += array(
      '%uid' => $node->getOwnerId(),
      '%url' => $node->url('canonical', array('absolute' => TRUE)),
      '%node_type' => node_get_type_label($node),
      '%title' => $node->getTitle(),
      '%teaser' => $node->teaser,
      '%body' => $node->body,
    );
  }
  $subject = strtr($context['subject'], $variables);
  $body = strtr($context['message'], $variables);
  $message['subject'] .= str_replace(array("\r", "\n"), '', $subject);
  $message['body'][] = MailFormatHelper::htmlToText($body);
}

/**
 * Alter the list of mail backend plugin definitions.
 *
 * @param array $info
 *   The mail backend plugin definitions to be altered.
 *
 * @see \Drupal\Core\Annotation\Mail
 * @see \Drupal\Core\Mail\MailManager
 */
function hook_mail_backend_info_alter(&$info) {
  unset($info['test_mail_collector']);
}

/**
 * Alter the default country list.
 *
 * @param $countries
 *   The associative array of countries keyed by two-letter country code.
 *
 * @see \Drupal\Core\Locale\CountryManager::getList().
 */
function hook_countries_alter(&$countries) {
  // Elbonia is now independent, so add it to the country list.
  $countries['EB'] = 'Elbonia';
}

/**
 * Alter display variant plugin definitions.
 *
 * @param array $definitions
 *   The array of display variant definitions, keyed by plugin ID.
 *
 * @see \Drupal\Core\Display\VariantManager
 * @see \Drupal\Core\Display\Annotation\DisplayVariant
 */
function hook_display_variant_plugin_alter(array &$definitions) {
  $definitions['full_page']['admin_label'] = t('Block layout');
}

/**
 * Flush all persistent and static caches.
 *
 * This hook asks your module to clear all of its static caches,
 * in order to ensure a clean environment for subsequently
 * invoked data rebuilds.
 *
 * Do NOT use this hook for rebuilding information. Only use it to flush custom
 * caches.
 *
 * Static caches using drupal_static() do not need to be reset manually.
 * However, all other static variables that do not use drupal_static() must be
 * manually reset.
 *
 * This hook is invoked by drupal_flush_all_caches(). It runs before module data
 * is updated and before hook_rebuild().
 *
 * @see drupal_flush_all_caches()
 * @see hook_rebuild()
 */
function hook_cache_flush() {
  if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE == 'update') {
    _update_cache_clear();
  }
}

/**
 * Rebuild data based upon refreshed caches.
 *
 * This hook allows your module to rebuild its data based on the latest/current
 * module data. It runs after hook_cache_flush() and after all module data has
 * been updated.
 *
 * This hook is only invoked after the system has been completely cleared;
 * i.e., all previously cached data is known to be gone and every API in the
 * system is known to return current information, so your module can safely rely
 * on all available data to rebuild its own.
 *
 * @see hook_cache_flush()
 * @see drupal_flush_all_caches()
 */
function hook_rebuild() {
  $themes = \Drupal::service('theme_handler')->listInfo();
  foreach ($themes as $theme) {
    _block_rehash($theme->getName());
  }
}

/**
 * Alter the configuration synchronization steps.
 *
 * @param array $sync_steps
 *   A one-dimensional array of \Drupal\Core\Config\ConfigImporter method names
 *   or callables that are invoked to complete the import, in the order that
 *   they will be processed. Each callable item defined in $sync_steps should
 *   either be a global function or a public static method. The callable should
 *   accept a $context array by reference. For example:
 *   <code>
 *     function _additional_configuration_step(&$context) {
 *       // Do stuff.
 *       // If finished set $context['finished'] = 1.
 *     }
 *   </code>
 *   For more information on creating batches, see the
 *   @link batch Batch operations @endlink documentation.
 *
 * @see callback_batch_operation()
 * @see \Drupal\Core\Config\ConfigImporter::initialize()
 */
function hook_config_import_steps_alter(&$sync_steps, \Drupal\Core\Config\ConfigImporter $config_importer) {
  $deletes = $config_importer->getUnprocessedConfiguration('delete');
  if (isset($deletes['field.storage.node.body'])) {
    $sync_steps[] = '_additional_configuration_step';
  }
}

/**
 * Alter config typed data definitions.
 *
 * For example you can alter the typed data types representing each
 * configuration schema type to change default labels or form element renderers
 * used for configuration translation.
 *
 * If implementations of this hook add or remove configuration schema a
 * ConfigSchemaAlterException will be thrown. Keep in mind that there are tools
 * that may use the configuration schema for static analysis of configuration
 * files, like the string extractor for the localization system. Such systems
 * won't work with dynamically defined configuration schemas.
 *
 * For adding new data types use configuration schema YAML files instead.
 *
 * @param $definitions
 *   Associative array of configuration type definitions keyed by schema type
 *   names. The elements are themselves array with information about the type.
 *
 * @see \Drupal\Core\Config\TypedConfigManager
 * @see \Drupal\Core\Config\Schema\ConfigSchemaAlterException
 */
function hook_config_schema_info_alter(&$definitions) {
  // Enhance the text and date type definitions with classes to generate proper
  // form elements in ConfigTranslationFormBase. Other translatable types will
  // appear as a one line textfield.
  $definitions['text']['form_element_class'] = '\Drupal\config_translation\FormElement\Textarea';
  $definitions['date_format']['form_element_class'] = '\Drupal\config_translation\FormElement\DateFormat';
}

/**
 * @} End of "addtogroup hooks".
 */

/**
 * @defgroup ajax Ajax API
 * @{
 * Overview for Drupal's Ajax API.
 *
 * @section sec_overview Overview of Ajax
 * Ajax is the process of dynamically updating parts of a page's HTML based on
 * data from the server. When a specified event takes place, a PHP callback is
 * triggered, which performs server-side logic and may return updated markup or
 * JavaScript commands to run. After the return, the browser runs the JavaScript
 * or updates the markup on the fly, with no full page refresh necessary.
 *
 * Many different events can trigger Ajax responses, including:
 * - Clicking a button
 * - Pressing a key
 * - Moving the mouse
 *
 * @section sec_framework Ajax responses in forms
 * Forms that use the Drupal Form API (see the
 * @link form_api Form API topic @endlink for more information about forms) can
 * trigger AJAX responses. Here is an outline of the steps:
 * - Add property '#ajax' to a form element in your form array, to trigger an
 *   Ajax response.
 * - Write an Ajax callback to process the input and respond.
 * See sections below for details on these two steps.
 *
 * @subsection sub_form Adding Ajax triggers to a form
 * As an example of adding Ajax triggers to a form, consider editing a date
 * format, where the user is provided with a sample of the generated date output
 * as they type. To accomplish this, typing in the text field should trigger an
 * Ajax response. This is done in the text field form array element
 * in \Drupal\config_translation\FormElement\DateFormat::getFormElement():
 * @code
 * '#ajax' => array(
 *   'callback' => 'Drupal\config_translation\FormElement\DateFormat::ajaxSample',
 *   'event' => 'keyup',
 *   'progress' => array(
 *     'type' => 'throbber',
 *     'message' => NULL,
 *   ),
 * ),
 * @endcode
 *
 * As you can see from this example, the #ajax property for a form element is
 * an array. Here are the details of its elements, all of which are optional:
 * - callback: The callback to invoke to handle the server side of the
 *   Ajax event. More information on callbacks is below in @ref sub_callback.
 * - path: The URL path to use for the request. If omitted, defaults to
 *   'system/ajax', which invokes the default Drupal Ajax processing (this will
 *   call the callback supplied in the 'callback' element). If you supply a
 *   path, you must set up a routing entry to handle the request yourself and
 *   return output described in @ref sub_callback below. See the
 *   @link menu Routing topic @endlink for more information on routing.
 * - wrapper: The HTML 'id' attribute of the area where the content returned by
 *   the callback should be placed. Note that callbacks have a choice of
 *   returning content or JavaScript commands; 'wrapper' is used for content
 *   returns.
 * - method: The jQuery method for placing the new content (used with
 *   'wrapper'). Valid options are 'replaceWith' (default), 'append', 'prepend',
 *   'before', 'after', or 'html'. See
 *   http://api.jquery.com/category/manipulation/ for more information on these
 *   methods.
 * - effect: The jQuery effect to use when placing the new HTML (used with
 *   'wrapper'). Valid options are 'none' (default), 'slide', or 'fade'.
 * - speed: The effect speed to use (used with 'effect' and 'wrapper'). Valid
 *   options are 'slow' (default), 'fast', or the number of milliseconds the
 *   effect should run.
 * - event: The JavaScript event to respond to. This is selected automatically
 *   for the type of form element; provide a value to override the default.
 * - prevent: A JavaScript event to prevent when the event is triggered. For
 *   example, if you use event 'mousedown' on a button, you might want to
 *   prevent 'click' events from also being triggered.
 * - progress: An array indicating how to show Ajax processing progress. Can
 *   contain one or more of these elements:
 *   - type: Type of indicator: 'throbber' (default) or 'bar'.
 *   - message: Translated message to display.
 *   - url: For a bar progress indicator, URL path for determining progress.
 *   - interval: For a bar progress indicator, how often to update it.
 *
 * @subsection sub_callback Setting up a callback to process Ajax
 * Once you have set up your form to trigger an Ajax response (see @ref sub_form
 * above), you need to write some PHP code to process the response. If you use
 * 'path' in your Ajax set-up, your route controller will be triggered with only
 * the information you provide in the URL. If you use 'callback', your callback
 * method is a function, which will receive the $form and $form_state from the
 * triggering form. You can use $form_state to get information about the
 * data the user has entered into the form. For instance, in the above example
 * for the date format preview,
 * \Drupal\config_translation\FormElement\DateFormat\ajaxSample() does this to
 * get the format string entered by the user:
 * @code
 * $format_value = \Drupal\Component\Utility\NestedArray::getValue(
 *   $form_state->getValues(),
 *   $form_state->getTriggeringElement()['#array_parents']);
 * @endcode
 *
 * Once you have processed the input, you have your choice of returning HTML
 * markup or a set of Ajax commands. If you choose to return HTML markup, you
 * can return it as a string or a renderable array, and it will be placed in
 * the defined 'wrapper' element (see documentation above in @ref sub_form).
 * In addition, any messages returned by drupal_get_messages(), themed as in
 * status-messages.html.twig, will be prepended.
 *
 * To return commands, you need to set up an object of class
 * \Drupal\Core\Ajax\AjaxResponse, and then use its addCommand() method to add
 * individual commands to it. In the date format preview example, the format
 * output is calculated, and then it is returned as replacement markup for a div
 * like this:
 * @code
 * $response = new AjaxResponse();
 * $response->addCommand(new ReplaceCommand(
 *   '#edit-date-format-suffix',
 *   '<small id="edit-date-format-suffix">' . $format . '</small>'));
 * return $response;
 * @endcode
 *
 * The individual commands that you can return implement interface
 * \Drupal\Core\Ajax\CommandInterface. Available commands provide the ability
 * to pop up alerts, manipulate text and markup in various ways, redirect
 * to a new URL, and the generic \Drupal\Core\Ajax\InvokeCommand, which
 * invokes an arbitrary jQuery command.
 *
 * As noted above, status messages are prepended automatically if you use the
 * 'wrapper' method and return HTML markup. This is not the case if you return
 * commands, but if you would like to show status messages, you can add
 * @code
 * array('#type' => 'status_messages')
 * @endcode
 * to a render array, use drupal_render() to render it, and add a command to
 * place the messages in an appropriate location.
 *
 * @section sec_other Other methods for triggering Ajax
 * Here are some additional methods you can use to trigger Ajax responses in
 * Drupal:
 * - Add class 'use-ajax' to a link. The link will be loaded using an Ajax
 *   call. When using this method, the href of the link can contain '/nojs/' as
 *   part of the path. When the Ajax JavaScript processes the page, it will
 *   convert this to '/ajax/'. The server is then able to easily tell if this
 *   request was made through an actual Ajax request or in a degraded state, and
 *   respond appropriately.
 * - Add class 'use-ajax-submit' to a submit button in a form. The form will
 *   then be submitted via Ajax to the path specified in the #action.  Like the
 *   ajax-submit class on links, this path will have '/nojs/' replaced with
 *   '/ajax/' so that the submit handler can tell if the form was submitted in a
 *   degraded state or not.
 * - Add property '#autocomplete_route_name' to a text field in a form. The
 *   route controller for this route must return an array of options for
 *   autocomplete, as a \Symfony\Component\HttpFoundation\JsonResponse object.
 *   See the @link menu Routing topic @endlink for more information about
 *   routing.
 */

/**
 * @} End of "defgroup ajax".
 */

/**
 * @defgroup service_tag Service Tags
 * @{
 * Service tags overview
 *
 * Some services have tags, which are defined in the service definition. Tags
 * are used to define a group of related services, or to specify some aspect of
 * how the service behaves. Typically, if you tag a service, your service class
 * must also implement a corresponding interface. Some common examples:
 * - access_check: Indicates a route access checking service; see the
 *   @link menu Menu and routing system topic @endlink for more information.
 * - cache.bin: Indicates a cache bin service; see the
 *   @link cache Cache topic @endlink for more information.
 * - event_subscriber: Indicates an event subscriber service. Event subscribers
 *   can be used for dynamic routing and route altering; see the
 *   @link menu Menu and routing system topic @endlink for more information.
 *   They can also be used for other purposes; see
 *   http://symfony.com/doc/current/cookbook/doctrine/event_listeners_subscribers.html
 *   for more information.
 * - needs_destruction: Indicates that a destruct() method needs to be called
 *   at the end of a request to finalize operations, if this service was
 *   instantiated. Services should implement \Drupal\Core\DestructableInterface
 *   in this case.
 *
 * Creating a tag for a service does not do anything on its own, but tags
 * can be discovered or queried in a compiler pass when the container is built,
 * and a corresponding action can be taken. See
 * \Drupal\Core\Render\MainContent\MainContentRenderersPass for an example of
 * finding tagged services.
 *
 * See @link container Services and Dependency Injection Container @endlink for
 * information on services and the dependency injection container.
 *
 * @}
 */

/**
 * @defgroup events Events
 * @{
 * Overview of event dispatch and subscribing
 *
 * @section sec_intro Introduction and terminology
 * Events are part of the Symfony framework: they allow for different components
 * of the system to interact and communicate with each other. Each event has a
 * unique string name. One system component dispatches the event at an
 * appropriate time; many events are dispatched by Drupal core and the Symfony
 * framework in every request. Other system components can register as event
 * subscribers; when an event is dispatched, a method is called on each
 * registered subscriber, allowing each one to react. For more on the general
 * concept of events, see
 * http://symfony.com/doc/current/components/event_dispatcher/introduction.html
 *
 * @section sec_dispatch Dispatching events
 * To dispatch an event, call the
 * \Symfony\Component\EventDispatcher\EventDispatchInterface::dispatch() method
 * on the 'event_dispatcher' service (see the
 * @link container Services topic @endlink for more information about how to
 * interact with services). The first argument is the unique event name, which
 * you should normally define as a constant in a separate static class (see
 * \Symfony\Component\HttpKernel\KernelEvents and
 * \Drupal\Core\Config\ConfigEvents for examples). The second argument is a
 * \Symfony\Component\EventDispatcher\Event object; normally you will need to
 * extend this class, so that your event class can provide data to the event
 * subscribers.
 *
 * @section sec_subscribe Registering event subscribers
 * Here are the steps to register an event subscriber:
 * - Define a service in your module, tagged with 'event_subscriber' (see the
 *   @link container Services topic @endlink for instructions).
 * - Define a class for your subscriber service that implements
 *   \Symfony\Component\EventDispatcher\EventSubscriberInterface
 * - In your class, the getSubscribedEvents method returns a list of the events
 *   this class is subscribed to, and which methods on the class should be
 *   called for each one. Example:
 *   @code
 *   public function getSubscribedEvents() {
 *     // Subscribe to kernel terminate with priority 100.
 *     $events[KernelEvents::TERMINATE][] = array('onTerminate', 100);
 *     // Subscribe to kernel request with default priority of 0.
 *     $events[KernelEvents::REQUEST][] = array('onRequest');
 *     return $events;
 *   }
 *   @endcode
 * - Write the methods that respond to the events; each one receives the
 *   event object provided in the dispatch as its one argument. In the above
 *   example, you would need to write onTerminate() and onRequest() methods.
 *
 * Note that in your getSubscribedEvents() method, you can optionally set the
 * priority of your event subscriber (see terminate example above). Event
 * subscribers with higher priority numbers get executed first; the default
 * priority is zero. If two event subscribers for the same event have the same
 * priority, the one defined in a module with a lower module weight will fire
 * first. Subscribers defined in the same services file are fired in
 * definition order. If order matters defining a priority is strongly advised
 * instead of relying on these two tie breaker rules as they might change in a
 * minor release.
 * @}
 */
