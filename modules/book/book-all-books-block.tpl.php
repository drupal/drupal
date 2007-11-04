<?php
// $Id$

/**
 * @file book-all-books-block.tpl.php
 * Default theme implementation for rendering book outlines within a block.
 * This template is used only when the block is configured to "show block on
 * all pages" which presents Multiple independent books on all pages.
 *
 * Available variables:
 * - $book_menus: Array of book outlines rendered as an unordered list. It is
 *   keyed to the parent book ID which is also the ID of the parent node
 *   containing an entire outline.
 *
 * @see template_preprocess_book_all_books_block()
 */
?>
<?php foreach ($book_menus as $book_id => $menu) : ?>
<div id="book-block-menu-<?php print $book_id; ?>" class="book-block-menu">
  <?php print $menu; ?>
</div>
<?php endforeach; ?>
