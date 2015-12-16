<?php

/**
 * @file
 * Contains \Drupal\views_ui\ProxyClass\ParamConverter\ViewUIConverter.
 */

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\views_ui\ParamConverter\ViewUIConverter' "core/modules/views_ui/src".
 */

namespace Drupal\views_ui\ProxyClass\ParamConverter {

    /**
     * Provides a proxy class for \Drupal\views_ui\ParamConverter\ViewUIConverter.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class ViewUIConverter implements \Drupal\Core\ParamConverter\ParamConverterInterface
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
         * @var \Drupal\views_ui\ParamConverter\ViewUIConverter
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
        public function convert($value, $definition, $name, array $defaults)
        {
            return $this->lazyLoadItself()->convert($value, $definition, $name, $defaults);
        }

        /**
         * {@inheritdoc}
         */
        public function applies($definition, $name, \Symfony\Component\Routing\Route $route)
        {
            return $this->lazyLoadItself()->applies($definition, $name, $route);
        }

    }

}
