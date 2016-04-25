<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\Core\Plugin\CachedDiscoveryClearer' "core/lib/Drupal/Core".
 */

namespace Drupal\Core\ProxyClass\Plugin {

    /**
     * Provides a proxy class for \Drupal\Core\Plugin\CachedDiscoveryClearer.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class CachedDiscoveryClearer implements \Drupal\Core\Plugin\CachedDiscoveryClearerInterface
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
         * @var \Drupal\Core\Plugin\CachedDiscoveryClearer
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
        public function addCachedDiscovery(\Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface $cached_discovery)
        {
            return $this->lazyLoadItself()->addCachedDiscovery($cached_discovery);
        }

        /**
         * {@inheritdoc}
         */
        public function clearCachedDefinitions()
        {
            return $this->lazyLoadItself()->clearCachedDefinitions();
        }

    }

}
