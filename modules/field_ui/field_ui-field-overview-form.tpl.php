<?php
// $Id: field_ui-field-overview-form.tpl.php,v 1.2 2009/10/24 17:26:16 webchick Exp $

/**
 * @file
 * Default theme implementation to configure field settings.
 *
 * Available variables:
 *
 * - $form: The complete overview form for the field settings.
 * - $contexts: An associative array of the available contexts for these fields.
 *   On the node field display settings this defaults to including "teaser" and
 *   "full" as the available contexts.
 * - $rows: The field overview form broken down into rendered rows for printing
 *   as a table.
 * - $submit: The rendered submit button for this form.
 *
 * @see field_ui_field_overview_form()
 * @see template_preprocess_field_ui_field_overview_form()
 */
?>
<table id="field-overview" class="sticky-enabled">
  <thead>
    <tr>
      <th><?php print t('Label'); ?></th>
      <th><?php print t('Weight'); ?></th>
      <th><?php print t('Name'); ?></th>
      <th><?php print t('Field'); ?></th>
      <th><?php print t('Widget'); ?></th>
      <th colspan="2"><?php print t('Operations'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php
    $count = 0;
    foreach ($rows as $row): ?>
      <tr class="<?php print $count % 2 == 0 ? 'odd' : 'even'; ?> <?php print $row->class ?>">
      <?php
      switch ($row->row_type):
        case 'field': ?>
          <td>
            <span class="<?php print $row->label_class; ?>"><?php print $row->label; ?></span>
          </td>
          <td><?php print $row->weight . $row->hidden_name; ?></td>
          <td><?php print $row->field_name; ?></td>
          <td><?php print $row->type; ?></td>
          <td><?php print $row->widget_type; ?></td>
          <td><?php print $row->edit; ?></td>
          <td><?php print $row->delete; ?></td>
          <?php break;

        case 'extra': ?>
          <td>
            <span class="<?php print $row->label_class; ?>"><?php print $row->label; ?></span>
          </td>
          <td><?php print $row->weight . $row->hidden_name; ?></td>
          <td><?php print $row->name; ?></td>
          <td colspan="2"><?php print $row->description; ?></td>
          <td><?php print $row->edit; ?></td>
          <td><?php print $row->delete; ?></td>
          <?php break;

        case 'add_new_field': ?>
          <td>
            <div class="<?php print $row->label_class; ?>">
              <div class="new"><?php print t('Add new field'); ?></div>
              <?php print $row->label; ?>
            </div>
          </td>
          <td><div class="new">&nbsp;</div><?php print $row->weight . $row->hidden_name; ?></td>
          <td><div class="new">&nbsp;</div><?php print $row->field_name; ?></td>
          <td><div class="new">&nbsp;</div><?php print $row->type; ?></td>
          <td colspan="3"><div class="new">&nbsp;</div><?php print $row->widget_type; ?></td>
          <?php break;

        case 'add_existing_field': ?>
          <td>
            <div class="<?php print $row->label_class; ?>">
              <div class="new"><?php print t('Add existing field'); ?></div>
              <?php print $row->label; ?>
            </div>
          </td>
          <td><div class="new">&nbsp;</div><?php print $row->weight . $row->hidden_name; ?></td>
          <td colspan="2"><div class="new">&nbsp;</div><?php print $row->field_name; ?></td>
          <td colspan="3"><div class="new">&nbsp;</div><?php print $row->widget_type; ?></td>
          <?php break;
      endswitch; ?>
      </tr>
      <?php $count++;
    endforeach; ?>
  </tbody>
</table>

<?php print $submit; ?>
