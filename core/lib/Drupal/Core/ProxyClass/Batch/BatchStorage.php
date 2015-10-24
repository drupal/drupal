<?php

/**
 * @file
 * Contains \Drupal\Core\ProxyClass\Batch\BatchStorage.
 */

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\Core\Batch\BatchStorage' "core/lib/Drupal/Core".
 */

namespace Drupal\Core\ProxyClass\Batch {

    /**
     * Provides a proxy class for \Drupal\Core\Batch\BatchStorage.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class BatchStorage implements \Drupal\Core\Batch\BatchStorageInterface
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
         * @var \Drupal\Core\Batch\BatchStorage
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
        public function load($id)
        {
            return $this->lazyLoadItself()->load($id);
        }

        /**
         * {@inheritdoc}
         */
        public function delete($id)
        {
            return $this->lazyLoadItself()->delete($id);
        }

        /**
         * {@inheritdoc}
         */
        public function update(array $batch)
        {
            return $this->lazyLoadItself()->update($batch);
        }

        /**
         * {@inheritdoc}
         */
        public function cleanup()
        {
            return $this->lazyLoadItself()->cleanup();
        }

        /**
         * {@inheritdoc}
         */
        public function create(array $batch)
        {
            return $this->lazyLoadItself()->create($batch);
        }

    }

}
