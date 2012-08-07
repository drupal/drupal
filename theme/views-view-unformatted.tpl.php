<?php

/**
 * @file
 * Default simple view template to display a list of rows.
 *
 * @ingroup views_templates
 */
?>
<?php if (!empty($title)): ?>
  <h3><?php print $title; ?></h3>
<?php endif; ?>
<?php foreach ($rows as $id => $row): ?>
  <div <?php if ($row_classes[$id]) { print 'class="' . $row_classes[$id] .'"';  } ?>>
    <?php print $row; ?>
  </div>
<?php endforeach; ?>
