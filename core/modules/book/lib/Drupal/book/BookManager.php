<?php
/**
 * @file
 * Contains \Drupal\book\BookManager.
 */

namespace Drupal\book;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\node\NodeInterface;

/**
 * Book Manager Service.
 */
class BookManager {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Entity manager Service Object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * Config Factory Service Object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Books Array.
   *
   * @var array
   */
  protected $books;

  /**
   * Constructs a BookManager object.
   */
  public function __construct(Connection $connection, EntityManagerInterface $entity_manager, TranslationInterface $translation, ConfigFactory $config_factory) {
    $this->connection = $connection;
    $this->entityManager = $entity_manager;
    $this->translation =  $translation;
    $this->configFactory = $config_factory;
  }

  /**
   * Returns an array of all books.
   *
   * This list may be used for generating a list of all the books, or for building
   * the options for a form select.
   *
   * @return
   *   An array of all books.
   */
  public function getAllBooks() {
    if (!isset($this->books)) {
      $this->loadBooks();
    }
    return $this->books;
  }

  /**
   * Loads Books Array.
   */
  protected function loadBooks() {
    $this->books = array();
    $nids = $this->connection->query("SELECT DISTINCT(bid) FROM {book}")->fetchCol();

    if ($nids) {
      $query = $this->connection->select('book', 'b', array('fetch' => \PDO::FETCH_ASSOC));
      $query->join('menu_links', 'ml', 'b.mlid = ml.mlid');
      $query->fields('b');
      $query->fields('ml');
      $query->condition('b.nid', $nids);
      $query->orderBy('ml.weight');
      $query->orderBy('ml.link_title');
      $query->addTag('node_access');
      $query->addMetaData('base_table', 'book');
      $book_links = $query->execute();

      $nodes = $this->entityManager->getStorageController('node')->loadMultiple($nids);

      foreach ($book_links as $link) {
        $nid = $link['nid'];
        if (isset($nodes[$nid]) && $nodes[$nid]->status) {
          $link['href'] = $link['link_path'];
          $link['options'] = unserialize($link['options']);
          $link['title'] = $nodes[$nid]->label();
          $link['type'] = $nodes[$nid]->bundle();
          $this->books[$link['bid']] = $link;
        }
      }
    }
  }

  /**
   * Returns an array with default values for a book page's menu link.
   *
   * @param string|int $nid
   *   The ID of the node whose menu link is being created.
   *
   * @return array
   *   The default values for the menu link.
   */
  public function getLinkDefaults($nid) {
    return array(
      'original_bid' => 0,
      'menu_name' => '',
      'nid' => $nid,
      'bid' => 0,
      'router_path' => 'node/%',
      'plid' => 0,
      'mlid' => 0,
      'has_children' => 0,
      'weight' => 0,
      'module' => 'book',
      'options' => array(),
    );
  }

  /**
   * Finds the depth limit for items in the parent select.
   *
   * @param array $book_link
   *   A fully loaded menu link that is part of the book hierarchy.
   *
   * @return int
   *   The depth limit for items in the parent select.
   */
  public function getParentDepthLimit(array $book_link) {
    return MENU_MAX_DEPTH - 1 - (($book_link['mlid'] && $book_link['has_children']) ? $this->entityManager->getStorageController('menu_link')->findChildrenRelativeDepth($book_link) : 0);
  }

  /**
   * Builds the common elements of the book form for the node and outline forms.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param \Drupal\node\NodeInterface $node
   *   The node whose form is being viewed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account viewing the form.
   *
   * @return array
   *   The form structure, with the book elements added.
   */
  public function addFormElements(array $form, array &$form_state, NodeInterface $node, AccountInterface $account) {
    // If the form is being processed during the Ajax callback of our book bid
    // dropdown, then $form_state will hold the value that was selected.
    if (isset($form_state['values']['book'])) {
      $node->book = $form_state['values']['book'];
    }
    $form['book'] = array(
      '#type' => 'details',
      '#title' => $this->t('Book outline'),
      '#weight' => 10,
      '#collapsed' => TRUE,
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('book-outline-form'),
      ),
      '#attached' => array(
        'library' => array(array('book', 'drupal.book')),
      ),
      '#tree' => TRUE,
    );
    foreach (array('menu_name', 'mlid', 'nid', 'router_path', 'has_children', 'options', 'module', 'original_bid', 'parent_depth_limit') as $key) {
      $form['book'][$key] = array(
        '#type' => 'value',
        '#value' => $node->book[$key],
      );
    }

    $form['book']['plid'] = $this->addParentSelectFormElements($node->book);

    // @see \Drupal\book\Form\BookAdminEditForm::bookAdminTableTree(). The
    // weight may be larger than 15.
    $form['book']['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $node->book['weight'],
      '#delta' => max(15, abs($node->book['weight'])),
      '#weight' => 5,
      '#description' => $this->t('Pages at a given level are ordered first by weight and then by title.'),
    );
    $options = array();
    $nid = !$node->isNew() ? $node->id() : 'new';
    if ($node->id() && ($nid == $node->book['original_bid']) && ($node->book['parent_depth_limit'] == 0)) {
      // This is the top level node in a maximum depth book and thus cannot be moved.
      $options[$node->id()] = $node->label();
    }
    else {
      foreach ($this->getAllBooks() as $book) {
        $options[$book['nid']] = $book['title'];
      }
    }

    if ($account->hasPermission('create new books') && ($nid == 'new' || ($nid != $node->book['original_bid']))) {
      // The node can become a new book, if it is not one already.
      $options = array($nid => $this->t('- Create a new book -')) + $options;
    }
    if (!$node->book['mlid']) {
      // The node is not currently in the hierarchy.
      $options = array(0 => $this->t('- None -')) + $options;
    }

    // Add a drop-down to select the destination book.
    $form['book']['bid'] = array(
      '#type' => 'select',
      '#title' => $this->t('Book'),
      '#default_value' => $node->book['bid'],
      '#options' => $options,
      '#access' => (bool) $options,
      '#description' => $this->t('Your page will be a part of the selected book.'),
      '#weight' => -5,
      '#attributes' => array('class' => array('book-title-select')),
      '#ajax' => array(
        'callback' => 'book_form_update',
        'wrapper' => 'edit-book-plid-wrapper',
        'effect' => 'fade',
        'speed' => 'fast',
      ),
    );
    return $form;
  }

  /**
   * Determines if a node can be removed from the book.
   *
   * A node can be removed from a book if it is actually in a book and it either
   * is not a top-level page or is a top-level page with no children.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to remove from the outline.
   *
   * @return bool
   *   TRUE if a node can be removed from the book, FALSE otherwise.
   */
  public function checkNodeIsRemovable(NodeInterface $node) {
    return (!empty($node->book['bid']) && (($node->book['bid'] != $node->id()) || !$node->book['has_children']));
  }

  /**
   * Handles additions and updates to the book outline.
   *
   * This common helper function performs all additions and updates to the book
   * outline through node addition, node editing, node deletion, or the outline
   * tab.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that is being saved, added, deleted, or moved.
   *
   * @return bool
   *   TRUE if the menu link was saved; FALSE otherwise.
   */
  public function updateOutline(NodeInterface $node) {
    if (empty($node->book['bid'])) {
      return FALSE;
    }
    $new = empty($node->book['mlid']);

    $node->book['link_path'] = 'node/' . $node->id();
    $node->book['link_title'] = $node->label();
    $node->book['parent_mismatch'] = FALSE; // The normal case.

    if ($node->book['bid'] == $node->id()) {
      $node->book['plid'] = 0;
      $node->book['menu_name'] = $this->createMenuName($node->id());
    }
    else {
      // Check in case the parent is not is this book; the book takes precedence.
      if (!empty($node->book['plid'])) {
        $parent = $this->connection->query("SELECT * FROM {book} WHERE mlid = :mlid", array(
          ':mlid' => $node->book['plid'],
        ))->fetchAssoc();
      }
      if (empty($node->book['plid']) || !$parent || $parent['bid'] != $node->book['bid']) {
        $node->book['plid'] = $this->connection->query("SELECT mlid FROM {book} WHERE nid = :nid", array(
          ':nid' => $node->book['bid'],
        ))->fetchField();
        $node->book['parent_mismatch'] = TRUE; // Likely when JS is disabled.
      }
    }

    $node->book = $this->entityManager
      ->getStorageController('menu_link')->create($node->book);
    if ($node->book->save()) {
      if ($new) {
        // Insert new.
        $this->connection->insert('book')
          ->fields(array(
            'nid' => $node->id(),
            'mlid' => $node->book['mlid'],
            'bid' => $node->book['bid'],
          ))
          ->execute();
      }
      else {
        if ($node->book['bid'] != $this->connection->query("SELECT bid FROM {book} WHERE nid = :nid", array(
          ':nid' => $node->id(),
        ))->fetchField()) {
          // Update the bid for this page and all children.
          $this->updateId($node->book);
        }
      }

      return TRUE;
    }

    // Failed to save the menu link.
    return FALSE;
  }

/**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translation->translate($string, $args, $options);
  }

  /**
   * Generates the corresponding menu name from a book ID.
   *
   * @param $id
   *   The book ID for which to make a menu name.
   *
   * @return
   *   The menu name.
   */
  public function createMenuName($id) {
    return 'book-toc-' . $id;
  }

  /**
   * Updates the book ID of a page and its children when it moves to a new book.
   *
   * @param array $book_link
   *   A fully loaded menu link that is part of the book hierarchy.
   */
  public function updateId($book_link) {
    $query = $this->connection->select('menu_links');
    $query->addField('menu_links', 'mlid');
    for ($i = 1; $i <= MENU_MAX_DEPTH && $book_link["p$i"]; $i++) {
      $query->condition("p$i", $book_link["p$i"]);
    }
    $mlids = $query->execute()->fetchCol();

    if ($mlids) {
      $this->connection->update('book')
        ->fields(array('bid' => $book_link['bid']))
        ->condition('mlid', $mlids, 'IN')
        ->execute();
    }
  }

  /**
   * Builds the parent selection form element for the node form or outline tab.
   *
   * This function is also called when generating a new set of options during the
   * Ajax callback, so an array is returned that can be used to replace an
   * existing form element.
   *
   * @param array $book_link
   *   A fully loaded menu link that is part of the book hierarchy.
   *
   * @return array
   *   A parent selection form element.
   */
  protected function addParentSelectFormElements(array $book_link) {
    if ($this->configFactory->get('menu.settings')->get('override_parent_selector')) {
      return array();
    }
    // Offer a message or a drop-down to choose a different parent page.
    $form = array(
      '#type' => 'hidden',
      '#value' => -1,
      '#prefix' => '<div id="edit-book-plid-wrapper">',
      '#suffix' => '</div>',
    );

    if ($book_link['nid'] === $book_link['bid']) {
      // This is a book - at the top level.
      if ($book_link['original_bid'] === $book_link['bid']) {
        $form['#prefix'] .= '<em>' . $this->t('This is the top-level page in this book.') . '</em>';
      }
      else {
        $form['#prefix'] .= '<em>' . $this->t('This will be the top-level page in this book.') . '</em>';
      }
    }
    elseif (!$book_link['bid']) {
      $form['#prefix'] .= '<em>' . $this->t('No book selected.') . '</em>';
    }
    else {
      $form = array(
        '#type' => 'select',
        '#title' => $this->t('Parent item'),
        '#default_value' => $book_link['plid'],
        '#description' => $this->t('The parent page in the book. The maximum depth for a book and all child pages is !maxdepth. Some pages in the selected book may not be available as parents if selecting them would exceed this limit.', array('!maxdepth' => MENU_MAX_DEPTH)),
        '#options' => $this->getTableOfContents($book_link['bid'], $book_link['parent_depth_limit'], array($book_link['mlid'])),
        '#attributes' => array('class' => array('book-title-select')),
        '#prefix' => '<div id="edit-book-plid-wrapper">',
        '#suffix' => '</div>',
      );
    }

    return $form;
  }

  /**
   * Recursively processes and formats menu items for getTableOfContents().
   *
   * This helper function recursively modifies the table of contents array for
   * each item in the menu tree, ignoring items in the exclude array or at a depth
   * greater than the limit. Truncates titles over thirty characters and appends
   * an indentation string incremented by depth.
   *
   * @param array $tree
   *   The data structure of the book's menu tree. Includes hidden links.
   * @param string $indent
   *   A string appended to each menu item title. Increments by '--' per depth
   *   level.
   * @param array $toc
   *   Reference to the table of contents array. This is modified in place, so the
   *   function does not have a return value.
   * @param array $exclude
   *   Optional array of menu link ID values. Any link whose menu link ID is in
   *   this array will be excluded (along with its children).
   * @param int $depth_limit
   *   Any link deeper than this value will be excluded (along with its children).
   */
  protected function recurseTableOfContents(array $tree, $indent, array &$toc, array $exclude, $depth_limit) {
    foreach ($tree as $data) {
      if ($data['link']['depth'] > $depth_limit) {
        // Don't iterate through any links on this level.
        break;
      }

      if (!in_array($data['link']['mlid'], $exclude)) {
        $toc[$data['link']['mlid']] = $indent . ' ' . truncate_utf8($data['link']['title'], 30, TRUE, TRUE);
        if ($data['below']) {
          $this->recurseTableOfContents($data['below'], $indent . '--', $toc, $exclude, $depth_limit);
        }
      }
    }
  }

  /**
   * Returns an array of book pages in table of contents order.
   *
   * @param int $bid
   *   The ID of the book whose pages are to be listed.
   * @param int $depth_limit
   *   Any link deeper than this value will be excluded (along with its children).
   * @param array $exclude
   *   (optional) An array of menu link ID values. Any link whose menu link ID is
   *   in this array will be excluded (along with its children). Defaults to an
   *   empty array.
   *
   * @return array
   *   An array of (menu link ID, title) pairs for use as options for selecting a
   *   book page.
   */
  public function getTableOfContents($bid, $depth_limit, array $exclude = array()) {
    $tree = menu_tree_all_data($this->createMenuName($bid));
    $toc = array();
    $this->recurseTableOfContents($tree, '', $toc, $exclude, $depth_limit);

    return $toc;
  }

}
