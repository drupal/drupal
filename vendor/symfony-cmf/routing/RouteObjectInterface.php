<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing;

/**
 * Classes for entries in the routing table may implement this interface in
 * addition to extending Symfony\Component\Routing\Route.
 *
 * If they do, the DynamicRouter will request the route content and put it into
 * the RouteObjectInterface::CONTENT_OBJECT field. The DynamicRouter will also
 * request getRouteKey and this will be used instead of the symfony core compatible
 * route name and can contain any characters.
 *
 * Some fields in defaults have a special meaning in the getDefaults(). In addition
 * to the constants defined in this class, _locale and _controller are also used.
 */
interface RouteObjectInterface
{
    /**
     * Field name that will hold the route name that was matched.
     */
    const ROUTE_NAME = '_route';

    /**
     * Field name of the route object that was matched.
     */
    const ROUTE_OBJECT = '_route_object';

    /**
     * Field name for an explicit controller name to be used with this route.
     */
    const CONTROLLER_NAME = '_controller';

    /**
     * Field name for an explicit template to be used with this route.
     * i.e. CmfContentBundle:StaticContent:index.html.twig.
     */
    const TEMPLATE_NAME = '_template';

    /**
     * Field name for the content of the current route, if any.
     */
    const CONTENT_OBJECT = '_content';

    /**
     * Field name for the content id of the current route, if any.
     */
    const CONTENT_ID = '_content_id';

    /**
     * Get the content document this route entry stands for. If non-null,
     * the ControllerClassMapper uses it to identify a controller and
     * the content is passed to the controller.
     *
     * If there is no specific content for this url (i.e. its an "application"
     * page), may return null.
     *
     * @return object the document or entity this route entry points to
     */
    public function getContent();

    /**
     * Get the route name.
     *
     * Normal symfony routes do not know their name, the name is only known
     * from the route collection. In the CMF, it is possible to use route
     * documents outside of collections, and thus useful to have routes provide
     * their name.
     *
     * There are no limitations to allowed characters in the name.
     *
     * @return string|null the route name or null to use the default name
     *                     (e.g. from route collection if known)
     */
    public function getRouteKey();
}
