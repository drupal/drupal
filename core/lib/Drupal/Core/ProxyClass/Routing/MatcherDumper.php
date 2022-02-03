<?php
// phpcs:ignoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\Core\Routing\MatcherDumper' "core/lib/Drupal/Core".
 */

namespace Drupal\Core\ProxyClass\Routing {

    /**
     * Provides a proxy class for \Drupal\Core\Routing\MatcherDumper.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class MatcherDumper implements \Drupal\Core\Routing\MatcherDumperInterface
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
         * @var \Drupal\Core\Routing\MatcherDumper
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
        public function addRoutes(\Symfony\Component\Routing\RouteCollection $routes)
        {
            return $this->lazyLoadItself()->addRoutes($routes);
        }

        /**
         * {@inheritdoc}
         */
        public function dump(array $options = array (
        )): string
        {
            return $this->lazyLoadItself()->dump($options);
        }

        /**
         * {@inheritdoc}
         */
        public function getRoutes(): \Symfony\Component\Routing\RouteCollection
        {
            return $this->lazyLoadItself()->getRoutes();
        }

    }

}
