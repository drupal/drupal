<?php
// $Id: book-all-books-block.tpl.php,v 1.3 2009/04/08 03:23:46 dries Exp $

/**
 * @file
 * Default theme implementation for rendering book outlines within a block.
 * This template is used only when the block is configured to "show block on
 * all pages" which presents Multiple independent books on all pages.
 *
 * Available variables:
 * - $book_menus: Array of book outlines rendered as an unordered list. It is
 *   keyed to the parent book ID which is also the ID of the parent node
 *   containing an entire outline.
 */
?>
<?php foreach ($book_menus as $book_id => $menu) : ?>
<div id="book-block-menu-<?php print $book_id; ?>" class="book-block-menu">
  <?php print $menu; ?>
</div>
<?php endforeach; ?>
