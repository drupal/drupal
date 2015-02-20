<?php

/**
 * @file
 * Contains \Drupal\book\Plugin\Block\BookNavigationBlock.
 */

namespace Drupal\book\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\book\BookManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides a 'Book navigation' block.
 *
 * @Block(
 *   id = "book_navigation",
 *   admin_label = @Translation("Book navigation"),
 *   category = @Translation("Menus")
 * )
 */
class BookNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new BookNavigationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, BookManagerInterface $book_manager, EntityStorageInterface $node_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->requestStack = $request_stack;
    $this->bookManager = $book_manager;
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('book.manager'),
      $container->get('entity.manager')->getStorage('node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'block_mode' => "all pages",
    );
  }

  /**
   * {@inheritdoc}
   */
  function blockForm($form, FormStateInterface $form_state) {
    $options = array(
      'all pages' => t('Show block on all pages'),
      'book pages' => t('Show block only on book pages'),
    );
    $form['book_block_mode'] = array(
      '#type' => 'radios',
      '#title' => t('Book navigation block display'),
      '#options' => $options,
      '#default_value' => $this->configuration['block_mode'],
      '#description' => t("If <em>Show block on all pages</em> is selected, the block will contain the automatically generated menus for all of the site's books. If <em>Show block only on book pages</em> is selected, the block will contain only the one menu corresponding to the current page's book. In this case, if the current page is not in a book, no block will be displayed. The <em>Page specific visibility settings</em> or other visibility settings can be used in addition to selectively display this block."),
      );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['block_mode'] = $form_state->getValue('book_block_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_bid = 0;

    if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
      $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];
    }
    if ($this->configuration['block_mode'] == 'all pages') {
      $book_menus = array();
      $pseudo_tree = array(0 => array('below' => FALSE));
      foreach ($this->bookManager->getAllBooks() as $book_id => $book) {
        if ($book['bid'] == $current_bid) {
          // If the current page is a node associated with a book, the menu
          // needs to be retrieved.
          $data = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book);
          $book_menus[$book_id] = $this->bookManager->bookTreeOutput($data);
        }
        else {
          // Since we know we will only display a link to the top node, there
          // is no reason to run an additional menu tree query for each book.
          $book['in_active_trail'] = FALSE;
          // Check whether user can access the book link.
          $book_node = $this->nodeStorage->load($book['nid']);
          $book['access'] = $book_node->access('view');
          $pseudo_tree[0]['link'] = $book;
          $book_menus[$book_id] = $this->bookManager->bookTreeOutput($pseudo_tree);
        }
        $book_menus[$book_id] += array(
          '#book_title' => $book['title'],
        );
      }
      if ($book_menus) {
        return array(
          '#theme' => 'book_all_books_block',
        ) + $book_menus;
      }
    }
    elseif ($current_bid) {
      // Only display this block when the user is browsing a book.
      $query = \Drupal::entityQuery('node');
      $nid = $query->condition('nid', $node->book['bid'], '=')->execute();

      // Only show the block if the user has view access for the top-level node.
      if ($nid) {
        $tree = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book);
        // There should only be one element at the top level.
        $data = array_shift($tree);
        $below = $this->bookManager->bookTreeOutput($data['below']);
        if (!empty($below)) {
          return $below;
        }
      }
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKeys() {
    // Add a key for the active book trail.
    $current_bid = 0;
    if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
      $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];
    }
    if ($current_bid === 0) {
      return parent::getCacheKeys();
    }
    $active_trail = $this->bookManager->getActiveTrailIds($node->book['bid'], $node->book);
    $active_trail_key = 'trail.' . implode('|', $active_trail);
    return array_merge(parent::getCacheKeys(), array($active_trail_key));
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredCacheContexts() {
    // The "Book navigation" block must be cached per role: different roles may
    // have access to different menu links.
    return array('user.roles');
  }

}
