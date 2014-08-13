<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing;

/**
 * Interface used by the DynamicRouter to retrieve content by it's id when
 * generating routes from content-id.
 *
 * This can be easily implemented using i.e. the Doctrine PHPCR-ODM
 * DocumentManager.
 *
 * @author Uwe Jäger
 */
interface ContentRepositoryInterface
{
    /**
     * Return a content object by it's id or null if there is none.
     *
     * If the returned content implements RouteReferrersReadInterface, it will
     * be used to get the route from it to generate an URL.
     *
     * @param string $id id of the content object
     *
     * @return object A content that matches this id.
     */
    public function findById($id);

    /**
     * Return the content identifier for the provided content object for
     * debugging purposes.
     *
     * @param object $content A content instance
     *
     * @return string|null $id id of the content object or null if unable to determine an id
     */
    public function getContentId($content);
}
