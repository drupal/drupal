<?php

/**
 * @file
 * Contains \Drupal\book\Plugin\Block\BookNavigationBlock.
 */

namespace Drupal\book\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Book navigation' block.
 *
 * @Block(
 *   id = "book_navigation",
 *   admin_label = @Translation("Book navigation"),
 *   category = @Translation("Menus")
 * )
 */
class BookNavigationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'cache' => DRUPAL_CACHE_PER_PAGE | DRUPAL_CACHE_PER_ROLE,
      'block_mode' => "all pages",
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm()
   */
  function blockForm($form, &$form_state) {
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
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['block_mode'] = $form_state['values']['book_block_mode'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_bid = 0;
    if ($node = menu_get_object()) {
      $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];
    }
    if ($this->configuration['block_mode'] == 'all pages') {
      $book_menus = array();
      $pseudo_tree = array(0 => array('below' => FALSE));
      foreach (book_get_books() as $book_id => $book) {
        if ($book['bid'] == $current_bid) {
          // If the current page is a node associated with a book, the menu
          // needs to be retrieved.
          $book_menus[$book_id] = menu_tree_output(menu_tree_all_data($node->book['menu_name'], $node->book));
        }
        else {
          // Since we know we will only display a link to the top node, there
          // is no reason to run an additional menu tree query for each book.
          $book['in_active_trail'] = FALSE;
          // Check whether user can access the book link.
          $book_node = node_load($book['nid']);
          $book['access'] = node_access('view', $book_node);
          $pseudo_tree[0]['link'] = $book;
          $book_menus[$book_id] = menu_tree_output($pseudo_tree);
        }
      }
      if ($book_menus) {
        return array(
          '#theme' => 'book_all_books_block',
          $book_menus
        );
      }
    }
    elseif ($current_bid) {
      // Only display this block when the user is browsing a book.
      $select = db_select('node', 'n')
        ->fields('n', array('nid'))
        ->condition('n.nid', $node->book['bid'])
        ->addTag('node_access');
      $nid = $select->execute()->fetchField();
      // Only show the block if the user has view access for the top-level node.
      if ($nid) {
        $tree = menu_tree_all_data($node->book['menu_name'], $node->book);
        // There should only be one element at the top level.
        $data = array_shift($tree);
        $below = menu_tree_output($data['below']);
        if (!empty($below)) {
          $book_title_link = array('#theme' => 'book_title_link', '#link' => $data['link']);
          return array(
            '#title' => drupal_render($book_title_link),
            $below,
          );
        }
      }
    }
    return array();
  }

}
