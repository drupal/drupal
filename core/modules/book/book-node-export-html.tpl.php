<?php

/**
 * @file
 * Default theme implementation for rendering a single node in a printer
 * friendly outline.
 *
 * @see book-node-export-html.tpl.php
 * Where it is collected and printed out.
 *
 * Available variables:
 * - $depth: Depth of the current node inside the outline.
 * - $title: Node title.
 * - $content: Node content.
 * - $children: All the child nodes recursively rendered through this file.
 *
 * @see template_preprocess_book_node_export_html()
 */
?>
<div id="node-<?php print $node->nid; ?>" class="section-<?php print $depth; ?>">
  <h1 class="book-heading"><?php print $title; ?></h1>
  <?php print $content; ?>
  <?php print $children; ?>
</div>
