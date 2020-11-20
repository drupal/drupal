<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\Enhancer;

use Symfony\Cmf\Component\Routing\ContentRepositoryInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This enhancer uses a ContentRepositoryInterface to load a content if $target
 * is empty.
 *
 * $source specifies the field that contains the ID to load, $target specifies
 * the field where to put the content returned by the repository.
 *
 * @author Samusev Andrey
 */
class ContentRepositoryEnhancer implements RouteEnhancerInterface
{
    /**
     * @var ContentRepositoryInterface
     */
    private $contentRepository;

    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $source;

    /**
     * @param ContentRepositoryInterface $contentRepository repository to search for the content
     * @param string                     $target            the field name to set content
     * @param string                     $source            the field name of the request parameter that contains the id
     */
    public function __construct(
        ContentRepositoryInterface $contentRepository,
        $target = RouteObjectInterface::CONTENT_OBJECT,
        $source = RouteObjectInterface::CONTENT_ID
    ) {
        $this->contentRepository = $contentRepository;
        $this->target = $target;
        $this->source = $source;
    }

    /**
     * {@inheritdoc}
     */
    public function enhance(array $defaults, Request $request)
    {
        if (!isset($defaults[$this->target]) && isset($defaults[$this->source])) {
            $defaults[$this->target] = $this->contentRepository->findById($defaults[$this->source]);
        }

        return $defaults;
    }
}
