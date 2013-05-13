<?php

namespace Symfony\Cmf\Component\Routing;

/**
 * Interface to be implemented by content that wants to be compatible with the
 * DynamicRouter
 */
interface RouteAwareInterface
{
    /**
     * Get the routes that point to this content.
     *
     * Note: For PHPCR ODM, as explained in RouteObjectInterface the route must use the
     * routeContent field to store the reference to the content so you can get the routes with
     * Referrers(referringDocument="Symfony\Cmf\Bundle\RoutingBundle\Document\Route", referencedBy="routeContent")
     *
     * @return \Symfony\Component\Routing\Route[] Route instances that point to this content
     */
    public function getRoutes();
}
