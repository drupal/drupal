<?php

/**
 * @file
 * Hooks and documentation related to the routing system.
 */

/**
 * @defgroup routing Routing API
 * @{
 * Route page requests to code based on URLs.
 *
 * @section sec_overview Overview and terminology
 * The Drupal routing system defines how Drupal responds to URL requests that
 * the web server passes on to Drupal. The routing system is based on the
 * @link http://symfony.com Symfony framework. @endlink The central idea is
 * that Drupal subsystems and modules can register routes (basically, URL
 * paths and context); they can also register to respond dynamically to
 * routes, for more flexibility. When Drupal receives a URL request, it will
 * attempt to match the request to a registered route, and query dynamic
 * responders. If a match is made, Drupal will then instantiate the required
 * classes, gather the data, format it, and send it back to the web browser.
 * Otherwise, Drupal will return a 404 or 403 response.
 *
 * The following sections of this topic provide an overview of the routing API.
 * For more detailed information, see
 * https://www.drupal.org/developing/api/8/routing
 *
 * @section sec_register Registering simple routes
 * To register a route, add lines similar to this to a module_name.routing.yml
 * file in your top-level module directory:
 * @code
 * dblog.overview:
 *   path: '/admin/reports/dblog'
 *   defaults:
 *     _controller: '\Drupal\dblog\Controller\DbLogController::overview'
 *     _title: 'Recent log messages'
 *   requirements:
 *     _permission: 'access site reports'
 * @endcode
 * Some notes:
 * - The first line is the machine name of the route. Typically, it is prefixed
 *   by the machine name of the module that defines the route, or the name of
 *   a subsystem.
 * - The 'path' line gives the URL path of the route (relative to the site's
 *   base URL).
 * - The 'defaults' section tells how to build the main content of the route,
 *   and can also give other information, such as the page title and additional
 *   arguments for the route controller method. There are several possibilities
 *   for how to build the main content, including:
 *   - _controller: A callable, usually a method on a page controller class
 *     (see @ref sec_controller below for details).
 *   - _form: A form controller class. See the
 *     @link form_api Form API topic @endlink for more information about
 *     form controllers.
 *   - _entity_form: A form for editing an entity. See the
 *     @link entity_api Entity API topic @endlink for more information.
 * - The 'requirements' section is used in Drupal to give access permission
 *   instructions (it has other uses in the Symfony framework). Most
 *   routes have a simple permission-based access scheme, as shown in this
 *   example. See the @link user_api Permission system topic @endlink for
 *   more information about permissions.
 *
 * See https://www.drupal.org/node/2092643 for more details about *.routing.yml
 * files, and https://www.drupal.org/node/2122201 for information on how to
 * set up dynamic routes. The @link events Events topic @endlink is also
 * relevant to dynamic routes.
 *
 * @section sec_placeholders Defining routes with placeholders
 * Some routes have placeholders in them, and these can also be defined in a
 * module_name.routing.yml file, as in this example from the Block module:
 * @code
 * entity.block.edit_form:
 *   path: '/admin/structure/block/manage/{block}'
 *   defaults:
 *     _entity_form: 'block.default'
 *     _title: 'Configure block'
 *   requirements:
 *     _entity_access: 'block.update'
 * @endcode
 * In the path, '{block}' is a placeholder - it will be replaced by the
 * ID of the block that is being configured by the entity system. See the
 * @link entity_api Entity API topic @endlink for more information.
 *
 * @section sec_controller Route controllers for simple routes
 * For simple routes, after you have defined the route in a *.routing.yml file
 * (see @ref sec_register above), the next step is to define a page controller
 * class and method. Page controller classes do not necessarily need to
 * implement any particular interface or extend any particular base class. The
 * only requirement is that the method specified in your *.routing.yml file
 * returns:
 * - A render array (see the
 *   @link theme_render Theme and render topic @endlink for more information).
 *   This render array is then rendered in the requested format (HTML, dialog,
 *   modal, AJAX are supported by default). In the case of HTML, it will be
 *   surrounded by blocks by default: the Block module is enabled by default,
 *   and hence its Page Display Variant that surrounds the main content with
 *   blocks is also used by default.
 * - A \Symfony\Component\HttpFoundation\Response object.
 * As a note, if your module registers multiple simple routes, it is usual
 * (and usually easiest) to put all of their methods on one controller class.
 *
 * If the route has placeholders (see @ref sec_placeholders above) the
 * placeholders will be passed to the method (using reflection) by name.
 * For example, the placeholder '{myvar}' in a route will become the $myvar
 * parameter to the method.
 *
 * Additionally, if a parameter is typed to one of the following special classes
 * the system will pass those values as well.
 *
 * - \Symfony\Component\HttpFoundation\Request: The raw Symfony request object.
 *   It is generally only useful if the controller needs access to the query
 *   parameters of the request. By convention, this parameter is usually named
 *   $request.
 * - \Psr\Http\Message\ServerRequestInterface: The raw request, represented
 *   using the PSR-7 ServerRequest format. This object is derived as necessary
 *   from the Symfony request, so if either will suffice the Symfony request
 *   will be slightly more performant. By convention this parameter is usually
 *   named $request.
 * - \Drupal\Core\Routing\RouteMatchInterface: The "route match" data from
 *   this request. This object contains various standard data derived from
 *   the request and routing process. Consult the interface for details.
 *
 * Most controllers will need to display some information stored in the Drupal
 * database, which will involve using one or more Drupal services (see the
 * @link container Services and container topic @endlink). In order to properly
 * inject services, a controller should implement
 * \Drupal\Core\DependencyInjection\ContainerInjectionInterface; simple
 * controllers can do this by extending the
 * \Drupal\Core\Controller\ControllerBase class. See
 * \Drupal\dblog\Controller\DbLogController for a straightforward example of
 * a controller class.
 *
 * @}
 */
