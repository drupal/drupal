<?php

/**
 * @file
 * Default theme implementation for rendering book outlines within a block.
 *
 * This template is used only when the block is configured to "show block on all
 * pages", which presents multiple independent books on all pages.
 *
 * Available variables:
 * - $book_menus: Array of book outlines keyed to the parent book ID. Call
 *   render() on each to print it as an unordered list.
 *
 * @see template_preprocess_book_all_books_block()
 *
 * @ingroup themeable
 */
?>
<?php foreach ($book_menus as $book_id => $menu): ?>
  <div id="book-block-menu-<?php print $book_id; ?>" class="book-block-menu">
    <?php print render($menu); ?>
  </div>
<?php endforeach; ?>
