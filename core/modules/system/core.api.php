<?php

/**
 * @file
 * Documentation landing page and topics, plus core library hooks.
 */

/**
 * @mainpage
 * Welcome to the Drupal API Documentation!
 *
 * This documentation is generated from specially-formatted comments embedded in
 * the Drupal source code. Here are some topics to get you started.
 *
 * @section essentials Essential background concepts
 *
 * - @link architecture Drupal's architecture @endlink
 * - @link extending Extending Drupal @endlink
 * - @link oo_conventions Object-oriented conventions used in Drupal @endlink
 * - @link best_practices Best practices @endlink
 *
 * @section interfacing Interfacing with the outside world
 *
 * - @link menu Routing, page controllers, and menu entries @endlink
 * - @link form_api Forms @endlink
 * - @link block_api Blocks @endlink
 * - @link ajax Ajax @endlink
 * - @link third_party Integrating third-party applications @endlink
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
 * @section utility Other essential APIs
 *
 * - @link i18n Internationalization @endlink
 * - @link cache Caching @endlink
 * - @link user_api User accounts, permissions, and roles @endlink
 * - @link theme_render Theme system and render API @endlink
 * - @link migration Migration @endlink
 *
 * @section advanced Advanced topics
 *
 * - @link container Services and the Dependency Injection Container @endlink
 * - @link typed_data Typed Data @endlink
 * - @link testing Automated tests @endlink
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
 * See https://drupal.org/node/2133321
 * @}
 */

/**
 * @defgroup cache Cache API
 * @{
 * Information about the Drupal Cache API
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
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
 * See https://drupal.org/simpletest
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
 * Overview of best practices for developers
 *
 * @todo write this
 *
 * Additional documentation paragraphs need to be written, and functions,
 * classes, and interfaces need to be added to this topic.
 *
 * See https://drupal.org/developing/best-practices -- this should touch upon
 * (and link to more information on): internationalization, security, automated
 * tests, documentation.
 * @}
 */
