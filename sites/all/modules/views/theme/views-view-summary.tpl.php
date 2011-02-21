<?php
// $Id: views-view-summary.tpl.php,v 1.6.6.1 2010/03/16 23:12:25 merlinofchaos Exp $
/**
 * @file views-view-summary.tpl.php
 * Default simple view template to display a list of summary lines
 *
 * @ingroup views_templates
 */
?>
<div class="item-list">
  <ul class="views-summary">
  <?php foreach ($rows as $id => $row): ?>
    <li><a href="<?php print $row->url; ?>"<?php print !empty($classes[$id]) ? ' class="'. $classes[$id] .'"' : ''; ?>><?php print $row->link; ?></a>
      <?php if (!empty($options['count'])): ?>
        (<?php print $row->count?>)
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
</div>
