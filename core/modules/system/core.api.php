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
 * - @link extending Extending Drupal @endlink
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
 * - @link config_state Configuration and State systems @endlink
 * - @link entity_api Entities @endlink
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
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and classes and
 * interfaces need to be added to this topic.
 *
 * See https://drupal.org/node/2168137
 * @}
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
 * @defgroup config_state Configuration and State Systems
 * @{
 * Information about the Configuration system and the State system.
 *
 * @todo write this
 *
 * This topic needs to describe simple configuration, configuration entities,
 * and the state system, at least at an overview level, and link to more
 * information (either drupal.org or more detailed topics in the API docs).
 *
 * See https://drupal.org/node/1667894
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 * @}
 */

/**
 * @defgroup entity_api Entity API
 * @{
 * Describes how to define and manipulate content and configuration entities.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic. Should describe
 * bundles, entity types, configuration vs. content entities, etc. at an
 * overview level. And link to more detailed documentation.
 *
 * See https://drupal.org/developing/api/entity
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
 * @}
 */

/**
 * @defgroup extending Extending Drupal
 * @{
 * Overview of hooks, plugins, annotations, event listeners, and services.
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic. This should be
 * high-level and link to more detailed topics.
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
