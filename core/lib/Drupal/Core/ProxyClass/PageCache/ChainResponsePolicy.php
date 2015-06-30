<?php

/**
 * @file
 * Contains Drupal\Core\ProxyClass\PageCache\ChainResponsePolicy.
 */

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\Core\PageCache\ChainResponsePolicy' "core/lib/Drupal/Core".
 */

namespace Drupal\Core\ProxyClass\PageCache {

    /**
     * Provides a proxy class for \Drupal\Core\PageCache\ChainResponsePolicy.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class ChainResponsePolicy implements \Drupal\Core\PageCache\ChainResponsePolicyInterface
    {

        use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

        /**
         * The id of the original proxied service.
         *
         * @var string
         */
        protected $drupalProxyOriginalServiceId;

        /**
         * The real proxied service, after it was lazy loaded.
         *
         * @var \Drupal\Core\PageCache\ChainResponsePolicy
         */
        protected $service;

        /**
         * The service container.
         *
         * @var \Symfony\Component\DependencyInjection\ContainerInterface
         */
        protected $container;

        /**
         * Constructs a ProxyClass Drupal proxy object.
         *
         * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
         *   The container.
         * @param string $drupal_proxy_original_service_id
         *   The service ID of the original service.
         */
        public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id)
        {
            $this->container = $container;
            $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
        }

        /**
         * Lazy loads the real service from the container.
         *
         * @return object
         *   Returns the constructed real service.
         */
        protected function lazyLoadItself()
        {
            if (!isset($this->service)) {
                $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
            }

            return $this->service;
        }

        /**
         * {@inheritdoc}
         */
        public function check(\Symfony\Component\HttpFoundation\Response $response, \Symfony\Component\HttpFoundation\Request $request)
        {
            return $this->lazyLoadItself()->check($response, $request);
        }

        /**
         * {@inheritdoc}
         */
        public function addPolicy(\Drupal\Core\PageCache\ResponsePolicyInterface $policy)
        {
            return $this->lazyLoadItself()->addPolicy($policy);
        }

    }

}
