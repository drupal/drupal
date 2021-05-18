<?php
// phpcs:ignoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\Core\Render\BareHtmlPageRenderer' "core/lib/Drupal/Core".
 */

namespace Drupal\Core\ProxyClass\Render {

    /**
     * Provides a proxy class for \Drupal\Core\Render\BareHtmlPageRenderer.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class BareHtmlPageRenderer implements \Drupal\Core\Render\BareHtmlPageRendererInterface
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
         * @var \Drupal\Core\Render\BareHtmlPageRenderer
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
        public function renderBarePage(array $content, $title, $page_theme_property, array $page_additions = array (
        ))
        {
            return $this->lazyLoadItself()->renderBarePage($content, $title, $page_theme_property, $page_additions);
        }

    }

}
