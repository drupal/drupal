<?php
// $Id: views-ui-list-views.tpl.php,v 1.6.6.1 2010/01/20 23:24:24 dereine Exp $
/**
 * @file
 *
 * Displays the list of views on the administration screen.
 */
?>
<p><?php print $help; ?></p>
<?php print $widgets; ?>
<?php foreach ($views as $view): ?>
  <table class="views-entry <?php print $view->classes; ?>">
    <tbody>
      <tr>
        <td class="view-name">
          <?php print $help_type_icon; ?>
          <?php print t('<em>@type</em> @base view: <strong>@view</strong>', array('@type' => $view->type, '@view' => $view->name, '@base' => $view->base)); ?>
          <?php if (!empty($view->tag)): ?>
            &nbsp;(<?php print $view->tag; ?>)
          <?php endif; ?>
        </td>
        <td class="view-ops"><?php print $view->ops ?></td>
      </tr>
      <tr>
        <td>
          <?php if ($view->title): ?>
            <?php print t('Title: @title', array('@title' => $view->title)); ?> <br />
          <?php endif; ?>
          <?php if (isset($view->path)): ?>
            <?php print t('Path: !path', array('!path' => $view->path)); ?> <br />
          <?php endif; ?>
          <?php if ($view->displays): ?>
            <em><?php print $view->displays; ?> </em><br />
          <?php endif; ?>
        </td>
        <td colspan="2" class="description">
          <?php print $view->description; ?>
        </td>
      </tr>
    </tbody>
  </table>
<?php endforeach; ?>
