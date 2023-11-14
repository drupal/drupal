<?php
// phpcs:ignoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\book\BookManager' "core/modules/book/src".
 */

namespace Drupal\book\ProxyClass {

    /**
     * Provides a proxy class for \Drupal\book\BookManager.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class BookManager implements \Drupal\book\BookManagerInterface
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
         * @var \Drupal\book\BookManager
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
        public function getAllBooks()
        {
            return $this->lazyLoadItself()->getAllBooks();
        }

        /**
         * {@inheritdoc}
         */
        public function getLinkDefaults($nid)
        {
            return $this->lazyLoadItself()->getLinkDefaults($nid);
        }

        /**
         * {@inheritdoc}
         */
        public function getParentDepthLimit(array $book_link)
        {
            return $this->lazyLoadItself()->getParentDepthLimit($book_link);
        }

        /**
         * {@inheritdoc}
         */
        public function addFormElements(array $form, \Drupal\Core\Form\FormStateInterface $form_state, \Drupal\node\NodeInterface $node, \Drupal\Core\Session\AccountInterface $account, $collapsed = true)
        {
            return $this->lazyLoadItself()->addFormElements($form, $form_state, $node, $account, $collapsed);
        }

        /**
         * {@inheritdoc}
         */
        public function checkNodeIsRemovable(\Drupal\node\NodeInterface $node)
        {
            return $this->lazyLoadItself()->checkNodeIsRemovable($node);
        }

        /**
         * {@inheritdoc}
         */
        public function updateOutline(\Drupal\node\NodeInterface $node)
        {
            return $this->lazyLoadItself()->updateOutline($node);
        }

        /**
         * {@inheritdoc}
         */
        public function getBookParents(array $item, array $parent = array (
        ))
        {
            return $this->lazyLoadItself()->getBookParents($item, $parent);
        }

        /**
         * {@inheritdoc}
         */
        public function getTableOfContents($bid, $depth_limit, array $exclude = array (
        ))
        {
            return $this->lazyLoadItself()->getTableOfContents($bid, $depth_limit, $exclude);
        }

        /**
         * {@inheritdoc}
         */
        public function deleteFromBook($nid)
        {
            return $this->lazyLoadItself()->deleteFromBook($nid);
        }

        /**
         * {@inheritdoc}
         */
        public function bookTreeAllData($bid, $link = NULL, $max_depth = NULL)
        {
            return $this->lazyLoadItself()->bookTreeAllData($bid, $link, $max_depth);
        }

        /**
         * {@inheritdoc}
         */
        public function getActiveTrailIds($bid, $link)
        {
            return $this->lazyLoadItself()->getActiveTrailIds($bid, $link);
        }

        /**
         * {@inheritdoc}
         */
        public function bookTreeOutput(array $tree)
        {
            return $this->lazyLoadItself()->bookTreeOutput($tree);
        }

        /**
         * {@inheritdoc}
         */
        public function bookTreeCollectNodeLinks(&$tree, &$node_links)
        {
            return $this->lazyLoadItself()->bookTreeCollectNodeLinks($tree, $node_links);
        }

        /**
         * {@inheritdoc}
         */
        public function bookTreeGetFlat(array $book_link)
        {
            return $this->lazyLoadItself()->bookTreeGetFlat($book_link);
        }

        /**
         * {@inheritdoc}
         */
        public function loadBookLink($nid, $translate = true)
        {
            return $this->lazyLoadItself()->loadBookLink($nid, $translate);
        }

        /**
         * {@inheritdoc}
         */
        public function loadBookLinks($nids, $translate = true)
        {
            return $this->lazyLoadItself()->loadBookLinks($nids, $translate);
        }

        /**
         * {@inheritdoc}
         */
        public function saveBookLink(array $link, $new)
        {
            return $this->lazyLoadItself()->saveBookLink($link, $new);
        }

        /**
         * {@inheritdoc}
         */
        public function bookTreeCheckAccess(&$tree, $node_links = array (
        ))
        {
            return $this->lazyLoadItself()->bookTreeCheckAccess($tree, $node_links);
        }

        /**
         * {@inheritdoc}
         */
        public function bookLinkTranslate(&$link)
        {
            return $this->lazyLoadItself()->bookLinkTranslate($link);
        }

        /**
         * {@inheritdoc}
         */
        public function bookSubtreeData($link)
        {
            return $this->lazyLoadItself()->bookSubtreeData($link);
        }

        /**
         * {@inheritdoc}
         */
        public function setStringTranslation(\Drupal\Core\StringTranslation\TranslationInterface $translation)
        {
            return $this->lazyLoadItself()->setStringTranslation($translation);
        }

    }

}
