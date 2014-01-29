<?php
/**
 * @file
 * Contains \Drupal\book\BookManager.
 */

namespace Drupal\book;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
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
      'link_path' => 'node/%',
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
    return MENU_MAX_DEPTH - 1 - (($book_link['mlid'] && $book_link['has_children']) ? $this->findChildrenRelativeDepth($book_link) : 0);
  }

  /**
   * {@inheritdoc}
   */
  protected function findChildrenRelativeDepth(array $entity) {
    $query = db_select('menu_links');
    $query->addField('menu_links', 'depth');
    $query->condition('menu_name', $entity['menu_name']);
    $query->orderBy('depth', 'DESC');
    $query->range(0, 1);

    $i = 1;
    $p = 'p1';
    while ($i <= MENU_MAX_DEPTH && $entity[$p]) {
      $query->condition($p, $entity[$p]);
      $p = 'p' . ++$i;
    }

    $max_depth = $query->execute()->fetchField();

    return ($max_depth > $entity['depth']) ? $max_depth - $entity['depth'] : 0;
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
    foreach (array('menu_name', 'mlid', 'nid', 'link_path', 'has_children', 'options', 'module', 'original_bid', 'parent_depth_limit') as $key) {
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
    $tree = $this->bookTreeAllData($this->createMenuName($bid));
    $toc = array();
    $this->recurseTableOfContents($tree, '', $toc, $exclude, $depth_limit);

    return $toc;
  }

  /**
   * Deletes node's entry form book table.
   *
   * @param int $nid
   *   The nid to delete.
   */
  public function deleteBook($nid) {
    $this->connection->delete('book')
      ->condition('nid', $nid)
      ->execute();
  }

  /**
   * Gets the data structure representing a named menu tree.
   *
   * Since this can be the full tree including hidden items, the data returned
   * may be used for generating an an admin interface or a select.
   *
   * @param string $menu_name
   *   The named menu links to return
   * @param $link
   *   A fully loaded menu link, or NULL. If a link is supplied, only the
   *   path to root will be included in the returned tree - as if this link
   *   represented the current page in a visible menu.
   * @param int $max_depth
   *   Optional maximum depth of links to retrieve. Typically useful if only one
   *   or two levels of a sub tree are needed in conjunction with a non-NULL
   *   $link, in which case $max_depth should be greater than $link['depth'].
   *
   * @return array
   *   An tree of menu links in an array, in the order they should be rendered.
   *
   * Note: copied from menu_tree_all_data().
   */
  public function bookTreeAllData($menu_name, $link = NULL, $max_depth = NULL) {
    $tree = &drupal_static('menu_tree_all_data', array());
    $language_interface = language(Language::TYPE_INTERFACE);

    // Use $mlid as a flag for whether the data being loaded is for the whole tree.
    $mlid = isset($link['mlid']) ? $link['mlid'] : 0;
    // Generate a cache ID (cid) specific for this $menu_name, $link, $language, and depth.
    $cid = 'links:' . $menu_name . ':all:' . $mlid . ':' . $language_interface->id . ':' . (int) $max_depth;

    if (!isset($tree[$cid])) {
      // If the static variable doesn't have the data, check {cache_menu}.
      $cache = cache('menu')->get($cid);
      if ($cache && isset($cache->data)) {
        // If the cache entry exists, it contains the parameters for
        // menu_build_tree().
        $tree_parameters = $cache->data;
      }
      // If the tree data was not in the cache, build $tree_parameters.
      if (!isset($tree_parameters)) {
        $tree_parameters = array(
          'min_depth' => 1,
          'max_depth' => $max_depth,
        );
        if ($mlid) {
          // The tree is for a single item, so we need to match the values in its
          // p columns and 0 (the top level) with the plid values of other links.
          $parents = array(0);
          for ($i = 1; $i < MENU_MAX_DEPTH; $i++) {
            if (!empty($link["p$i"])) {
              $parents[] = $link["p$i"];
            }
          }
          $tree_parameters['expanded'] = $parents;
          $tree_parameters['active_trail'] = $parents;
          $tree_parameters['active_trail'][] = $mlid;
        }

        // Cache the tree building parameters using the page-specific cid.
        cache('menu')->set($cid, $tree_parameters, Cache::PERMANENT, array('menu' => $menu_name));
      }

      // Build the tree using the parameters; the resulting tree will be cached
      // by _menu_build_tree()).
      $tree[$cid] = $this->menu_build_tree($menu_name, $tree_parameters);
    }

    return $tree[$cid];
  }

  /**
   * Returns a rendered menu tree.
   *
   * The menu item's LI element is given one of the following classes:
   * - expanded: The menu item is showing its submenu.
   * - collapsed: The menu item has a submenu which is not shown.
   * - leaf: The menu item has no submenu.
   *
   * @param array $tree
   *   A data structure representing the tree as returned from menu_tree_data.
   *
   * @return array
   *   A structured array to be rendered by drupal_render().
   *
   * Note: copied from menu_tree_output() but some hacky code using
   * menu_get_item() was removed.
   */
  public function bookTreeOutput(array $tree) {
    $build = array();
    $items = array();

    // Pull out just the menu links we are going to render so that we
    // get an accurate count for the first/last classes.
    foreach ($tree as $data) {
      if ($data['link']['access'] && !$data['link']['hidden']) {
        $items[] = $data;
      }
    }

    $num_items = count($items);
    foreach ($items as $i => $data) {
      $class = array();
      if ($i == 0) {
        $class[] = 'first';
      }
      if ($i == $num_items - 1) {
        $class[] = 'last';
      }
      // Set a class for the <li>-tag. Since $data['below'] may contain local
      // tasks, only set 'expanded' class if the link also has children within
      // the current menu.
      if ($data['link']['has_children'] && $data['below']) {
        $class[] = 'expanded';
      }
      elseif ($data['link']['has_children']) {
        $class[] = 'collapsed';
      }
      else {
        $class[] = 'leaf';
      }
      // Set a class if the link is in the active trail.
      if ($data['link']['in_active_trail']) {
        $class[] = 'active-trail';
        $data['link']['localized_options']['attributes']['class'][] = 'active-trail';
      }

      // Allow menu-specific theme overrides.
      $element['#theme'] = 'menu_link__' . strtr($data['link']['menu_name'], '-', '_');
      $element['#attributes']['class'] = $class;
      $element['#title'] = $data['link']['title'];
      $element['#href'] = $data['link']['link_path'];
      $element['#localized_options'] = !empty($data['link']['localized_options']) ? $data['link']['localized_options'] : array();
      $element['#below'] = $data['below'] ? $this->bookTreeOutput($data['below']) : $data['below'];
      $element['#original_link'] = $data['link'];
      // Index using the link's unique mlid.
      $build[$data['link']['mlid']] = $element;
    }
    if ($build) {
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme_wrappers'][] = 'menu_tree__' . strtr($data['link']['menu_name'], '-', '_');
    }

    return $build;
  }

  /**
   * Builds a menu tree, translates links, and checks access.
   *
   * @param string $menu_name
   *   The name of the menu.
   * @param array $parameters
   *   (optional) An associative array of build parameters. Possible keys:
   *   - expanded: An array of parent link ids to return only menu links that are
   *     children of one of the plids in this list. If empty, the whole menu tree
   *     is built, unless 'only_active_trail' is TRUE.
   *   - active_trail: An array of mlids, representing the coordinates of the
   *     currently active menu link.
   *   - only_active_trail: Whether to only return links that are in the active
   *     trail. This option is ignored, if 'expanded' is non-empty.
   *   - min_depth: The minimum depth of menu links in the resulting tree.
   *     Defaults to 1, which is the default to build a whole tree for a menu
   *     (excluding menu container itself).
   *   - max_depth: The maximum depth of menu links in the resulting tree.
   *   - conditions: An associative array of custom database select query
   *     condition key/value pairs; see _menu_build_tree() for the actual query.
   *
   * @return array
   *   A fully built menu tree.
   */
  protected function menu_build_tree($menu_name, array $parameters = array()) {
    // Build the menu tree.
    $data = $this->_menu_build_tree($menu_name, $parameters);
    // Check access for the current user to each item in the tree.
    menu_tree_check_access($data['tree'], $data['node_links']);
    return $data['tree'];
  }

  /**
   * Builds a menu tree.
   *
   * This function may be used build the data for a menu tree only, for example
   * to further massage the data manually before further processing happens.
   * menu_tree_check_access() needs to be invoked afterwards.
   *
   * @see menu_build_tree()
   */
  protected function _menu_build_tree($menu_name, array $parameters = array()) {
    // Static cache of already built menu trees.
    $trees = &drupal_static('menu_build_tree', array());
    $language_interface = language(Language::TYPE_INTERFACE);

    // Build the cache id; sort parents to prevent duplicate storage and remove
    // default parameter values.
    if (isset($parameters['expanded'])) {
      sort($parameters['expanded']);
    }
    $tree_cid = 'links:' . $menu_name . ':tree-data:' . $language_interface->id . ':' . hash('sha256', serialize($parameters));

    // If we do not have this tree in the static cache, check {cache_menu}.
    if (!isset($trees[$tree_cid])) {
      $cache = cache('menu')->get($tree_cid);
      if ($cache && isset($cache->data)) {
        $trees[$tree_cid] = $cache->data;
      }
    }

    if (!isset($trees[$tree_cid])) {
      $query = \Drupal::entityQuery('menu_link');
      for ($i = 1; $i <= MENU_MAX_DEPTH; $i++) {
        $query->sort('p' . $i, 'ASC');
      }
      $query->condition('menu_name', $menu_name);
      if (!empty($parameters['expanded'])) {
        $query->condition('plid', $parameters['expanded'], 'IN');
      }
      elseif (!empty($parameters['only_active_trail'])) {
        $query->condition('mlid', $parameters['active_trail'], 'IN');
      }
      $min_depth = (isset($parameters['min_depth']) ? $parameters['min_depth'] : 1);
      if ($min_depth != 1) {
        $query->condition('depth', $min_depth, '>=');
      }
      if (isset($parameters['max_depth'])) {
        $query->condition('depth', $parameters['max_depth'], '<=');
      }
      // Add custom query conditions, if any were passed.
      if (isset($parameters['conditions'])) {
        foreach ($parameters['conditions'] as $column => $value) {
          $query->condition($column, $value);
        }
      }

      // Build an ordered array of links using the query result object.
      $links = array();
      if ($result = $query->execute()) {
        $links = menu_link_load_multiple($result);
      }
      $active_trail = (isset($parameters['active_trail']) ? $parameters['active_trail'] : array());
      $data['tree'] = $this->menu_tree_data($links, $active_trail, $min_depth);
      $data['node_links'] = array();
      $this->bookTreeCollectNodeLinks($data['tree'], $data['node_links']);

      // Cache the data, if it is not already in the cache.
      cache('menu')->set($tree_cid, $data, Cache::PERMANENT, array('menu' => $menu_name));
      $trees[$tree_cid] = $data;
    }

    return $trees[$tree_cid];
  }

  /**
   * Collects node links from a given menu tree recursively.
   *
   * @param array $tree
   *   The menu tree you wish to collect node links from.
   * @param array $node_links
   *   An array in which to store the collected node links.
   */
  public function bookTreeCollectNodeLinks(&$tree, &$node_links) {
    // All book links are nodes.
    // @todo clean this up.
    foreach ($tree as $key => $v) {
      if (!is_array($v['link']['route_parameters'])) {
        $v['link']['route_parameters'] = unserialize($v['link']['route_parameters']);
      }
      if ($v['link']['route_name'] == 'node.view' && isset($v['link']['route_parameters']['node'])) {
        $nid = $v['link']['route_parameters']['node'];
        $node_links[$nid][$tree[$key]['link']['mlid']] = &$tree[$key]['link'];
        $tree[$key]['link']['access'] = FALSE;
      }
      if ($tree[$key]['below']) {
        $this->bookTreeCollectNodeLinks($tree[$key]['below'], $node_links);
      }
    }
  }

  /**
   * Checks access and performs dynamic operations for each link in the tree.
   *
   * @param array $tree
   *   The menu tree you wish to operate on.
   * @param array $node_links
   *   A collection of node link references generated from $tree by
   *   menu_tree_collect_node_links().
   */
  public function bookTreeCheckAccess(&$tree, $node_links = array()) {
    if ($node_links) {
      $nids = array_keys($node_links);
      $select = db_select('node_field_data', 'n');
      $select->addField('n', 'nid');
      // @todo This should be actually filtering on the desired node status field
      //   language and just fall back to the default language.
      $select->condition('n.status', 1);

      $select->condition('n.nid', $nids, 'IN');
      $select->addTag('node_access');
      $nids = $select->execute()->fetchCol();
      foreach ($nids as $nid) {
        foreach ($node_links[$nid] as $mlid => $link) {
          $node_links[$nid][$mlid]['access'] = TRUE;
        }
      }
    }
    $this->_menu_tree_check_access($tree);
  }

  /**
   * Sorts the menu tree and recursively checks access for each item.
   */
  protected function _menu_tree_check_access(&$tree) {
    $new_tree = array();
    foreach ($tree as $key => $v) {
      $item = &$tree[$key]['link'];
      $this->_menu_link_translate($item);
      if ($item['access']) {
        if ($tree[$key]['below']) {
          $this->_menu_tree_check_access($tree[$key]['below']);
        }
        // The weights are made a uniform 5 digits by adding 50000 as an offset.
        // After _menu_link_translate(), $item['title'] has the localized link title.
        // Adding the mlid to the end of the index insures that it is unique.
        $new_tree[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $tree[$key];
      }
    }
    // Sort siblings in the tree based on the weights and localized titles.
    ksort($new_tree);
    $tree = $new_tree;
  }

  /**
   * Provides menu link access control, translation, and argument handling.
   *
   * This function is similar to _menu_translate(), but it also does
   * link-specific preparation (such as always calling to_arg() functions).
   *
   * @param $item
   *   A menu link.
   * @param bool $translate
   *   (optional) Whether to try to translate a link containing dynamic path
   *   argument placeholders (%) based on the menu router item of the current
   *   path. Defaults to FALSE. Internally used for breadcrumbs.
   *
   * Note: copied from _menu_link_translate() in menu.inc, but reduced to the
   * minimal code that's used.
   */
  protected function _menu_link_translate(&$item, $translate = FALSE) {
    if (!is_array($item['options'])) {
      $item['options'] = unserialize($item['options']);
    }
    if (!is_array($item['route_parameters'])) {
      $item['route_parameters'] = unserialize($item['route_parameters']);
    }
    $item['href'] = $item['link_path'];
    // menu_tree_check_access() may set this ahead of time for links to nodes.
    if (!isset($item['access'])) {
      $item['access'] = \Drupal::service('access_manager')->checkNamedRoute('node.view', array('node' => $item['route_parameters']['node']), \Drupal::currentUser());
    }
    // For performance, don't localize a link the user can't access.
    if ($item['access']) {
      // Inlined the code we use from _menu_item_localize().
      $item['localized_options'] = $item['options'];
      // All 'class' attributes are assumed to be an array during rendering, but
      // links stored in the database may use an old string value.
      // @todo In order to remove this code we need to implement a database update
      //   including unserializing all existing link options and running this code
      //   on them, as well as adding validation to menu_link_save().
      if (isset($item['options']['attributes']['class']) && is_string($item['options']['attributes']['class'])) {
        $item['localized_options']['attributes']['class'] = explode(' ', $item['options']['attributes']['class']);
      }
      $item['title'] = $item['link_title'];
    }
  }

  /**
   * Sorts and returns the built data representing a menu tree.
   *
   * @param array $links
   *   A flat array of menu links that are part of the menu. Each array element
   *   is an associative array of information about the menu link, containing the
   *   fields from the {menu_links} table, and optionally additional information
   *   from the {menu_router} table, if the menu item appears in both tables.
   *   This array must be ordered depth-first. See _menu_build_tree() for a sample
   *   query.
   * @param array $parents
   *   An array of the menu link ID values that are in the path from the current
   *   page to the root of the menu tree.
   * @param int $depth
   *   The minimum depth to include in the returned menu tree.
   *
   * @return array
   *   An array of menu links in the form of a tree. Each item in the tree is an
   *   associative array containing:
   *   - link: The menu link item from $links, with additional element
   *     'in_active_trail' (TRUE if the link ID was in $parents).
   *   - below: An array containing the sub-tree of this item, where each element
   *     is a tree item array with 'link' and 'below' elements. This array will be
   *     empty if the menu item has no items in its sub-tree having a depth
   *     greater than or equal to $depth.
   */
  protected function menu_tree_data(array $links, array $parents = array(), $depth = 1) {
    // Reverse the array so we can use the more efficient array_pop() function.
    $links = array_reverse($links);
    return $this->_menu_tree_data($links, $parents, $depth);
  }

  /**
   * Builds the data representing a menu tree.
   *
   * The function is a bit complex because the rendering of a link depends on
   * the next menu link.
   */
  protected function _menu_tree_data(&$links, $parents, $depth) {
    $tree = array();
    while ($item = array_pop($links)) {
      // We need to determine if we're on the path to root so we can later build
      // the correct active trail.
      $item['in_active_trail'] = in_array($item['mlid'], $parents);
      // Add the current link to the tree.
      $tree[$item['mlid']] = array(
        'link' => $item,
        'below' => array(),
      );
      // Look ahead to the next link, but leave it on the array so it's available
      // to other recursive function calls if we return or build a sub-tree.
      $next = end($links);
      // Check whether the next link is the first in a new sub-tree.
      if ($next && $next['depth'] > $depth) {
        // Recursively call _menu_tree_data to build the sub-tree.
        $tree[$item['mlid']]['below'] = $this->_menu_tree_data($links, $parents, $next['depth']);
        // Fetch next link after filling the sub-tree.
        $next = end($links);
      }
      // Determine if we should exit the loop and return.
      if (!$next || $next['depth'] < $depth) {
        break;
      }
    }
    return $tree;
  }

  /**
   * Gets the data representing a subtree of the book hierarchy.
   *
   * The root of the subtree will be the link passed as a parameter, so the
   * returned tree will contain this item and all its descendents in the menu
   * tree.
   *
   * @param $link
   *   A fully loaded menu link.
   *
   * @return
   *   A subtree of menu links in an array, in the order they should be rendered.
   */
  public function bookMenuSubtreeData($link) {
    $tree = &drupal_static(__FUNCTION__, array());

    // Generate a cache ID (cid) specific for this $menu_name and $link.
    $cid = 'links:' . $link['menu_name'] . ':subtree-cid:' . $link['mlid'];

    if (!isset($tree[$cid])) {
      $cache = cache('menu')->get($cid);

      if ($cache && isset($cache->data)) {
        // If the cache entry exists, it will just be the cid for the actual data.
        // This avoids duplication of large amounts of data.
        $cache = cache('menu')->get($cache->data);

        if ($cache && isset($cache->data)) {
          $data = $cache->data;
        }
      }

      // If the subtree data was not in the cache, $data will be NULL.
      if (!isset($data)) {
        $query = db_select('menu_links', 'ml', array('fetch' => \PDO::FETCH_ASSOC));
        $query->join('book', 'b', 'ml.mlid = b.mlid');
        $query->fields('b');
        $query->fields('ml');
        $query->condition('menu_name', $link['menu_name']);
        for ($i = 1; $i <= MENU_MAX_DEPTH && $link["p$i"]; ++$i) {
          $query->condition("p$i", $link["p$i"]);
        }
        for ($i = 1; $i <= MENU_MAX_DEPTH; ++$i) {
          $query->orderBy("p$i");
        }
        $links = array();
        foreach ($query->execute() as $item) {
          $links[] = $item;
        }
        $data['tree'] = $this->menu_tree_data($links, array(), $link['depth']);
        $data['node_links'] = array();
        $this->bookTreeCollectNodeLinks($data['tree'], $data['node_links']);
        // Compute the real cid for book subtree data.
        $tree_cid = 'links:' . $item['menu_name'] . ':subtree-data:' . hash('sha256', serialize($data));
        // Cache the data, if it is not already in the cache.

        if (!cache('menu')->get($tree_cid)) {
          cache('menu')->set($tree_cid, $data);
        }
        // Cache the cid of the (shared) data using the menu and item-specific cid.
        cache('menu')->set($cid, $tree_cid);
      }
      // Check access for the current user to each item in the tree.
      $this->bookTreeCheckAccess($data['tree'], $data['node_links']);
      $tree[$cid] = $data['tree'];
    }

    return $tree[$cid];
  }
}
