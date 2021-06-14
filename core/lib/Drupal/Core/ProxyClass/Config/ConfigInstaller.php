<?php
// phpcs:ignoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\Core\Config\ConfigInstaller' "core/lib/Drupal/Core".
 */

namespace Drupal\Core\ProxyClass\Config {

    /**
     * Provides a proxy class for \Drupal\Core\Config\ConfigInstaller.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class ConfigInstaller implements \Drupal\Core\Config\ConfigInstallerInterface
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
         * @var \Drupal\Core\Config\ConfigInstaller
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
        public function installDefaultConfig($type, $name)
        {
            return $this->lazyLoadItself()->installDefaultConfig($type, $name);
        }

        /**
         * {@inheritdoc}
         */
        public function installOptionalConfig(?\Drupal\Core\Config\StorageInterface $storage = NULL, $dependency = array (
        ))
        {
            return $this->lazyLoadItself()->installOptionalConfig($storage, $dependency);
        }

        /**
         * {@inheritdoc}
         */
        public function installCollectionDefaultConfig($collection)
        {
            return $this->lazyLoadItself()->installCollectionDefaultConfig($collection);
        }

        /**
         * {@inheritdoc}
         */
        public function setSourceStorage(\Drupal\Core\Config\StorageInterface $storage)
        {
            return $this->lazyLoadItself()->setSourceStorage($storage);
        }

        /**
         * {@inheritdoc}
         */
        public function getSourceStorage()
        {
            return $this->lazyLoadItself()->getSourceStorage();
        }

        /**
         * {@inheritdoc}
         */
        public function setSyncing($status)
        {
            return $this->lazyLoadItself()->setSyncing($status);
        }

        /**
         * {@inheritdoc}
         */
        public function isSyncing()
        {
            return $this->lazyLoadItself()->isSyncing();
        }

        /**
         * {@inheritdoc}
         */
        public function checkConfigurationToInstall($type, $name)
        {
            return $this->lazyLoadItself()->checkConfigurationToInstall($type, $name);
        }

    }

}
