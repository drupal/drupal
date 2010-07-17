<?php
// $Id: field_ui-display-overview-table.tpl.php,v 1.3 2010/07/17 19:19:39 dries Exp $

/**
 * @file
 * Default theme implementation to configure field display settings.
 *
 * Available variables:
 * - $rows: The field display settings form broken down into rendered rows for
 *   printing as a table. The array is separated in two entries, 'visible' and
 *   'hidden'.
 * - $id: The HTML id for the table.
 *
 * @see field_ui_display_overview_form()
 * @see template_preprocess_field_ui_display_overview_table()
 */
?>
<?php if ($rows): ?>
<div id="field-display-overview-wrapper">
  <table id="field-display-overview" class="field-display-overview sticky-enabled">
    <thead>
      <tr>
        <th><?php print t('Field'); ?></th>
        <th><?php print t('Weight'); ?></th>
        <th><?php print t('Label'); ?></th>
        <th colspan="3"><?php print t('Format'); ?></th>
      </tr>
    </thead>
    <tbody>
      <tr class="region-message region-visible-message <?php print empty($rows['visible']) ? 'region-empty' : 'region-populated'; ?>">
        <td colspan="5"><em><?php print t('No field is displayed'); ?></em></td>
      </tr>
      <?php
      $count = 0;
      foreach ($rows['visible'] as $row): ?>
        <tr id="<?php print $row->id; ?>" class="<?php print $count % 2 == 0 ? 'odd' : 'even'; ?> <?php print $row->class ?>">
          <td><span class="<?php print $row->label_class; ?>"><?php print $row->human_name; ?></span></td>
          <td><?php print $row->weight . $row->hidden_name; ?></td>
          <td><?php if (isset($row->label)) print $row->label; ?></td>
          <?php if (isset($row->settings_edit_form)) : ?>
            <td colspan="3">
              <?php print $row->type; ?>
              <?php print $row->settings_edit_form; ?>
            </td>
          <?php else :?>
            <td>
              <?php print $row->type; ?>
            </td>
            <td class="field-formatter-summary-cell">
              <?php print $row->settings_summary; ?>
            </td>
            <td>
              <?php print $row->settings_edit; ?>
            </td>
          <?php endif; ?>
        </tr>
        <?php $count++;
      endforeach; ?>
      <tr class="region-title region-title-hidden">
        <td colspan="5"><?php print t('Hidden'); ?></td>
      </tr>
      <tr class="region-message region-hidden-message <?php print empty($rows['hidden']) ? 'region-empty' : 'region-populated'; ?>">
        <td colspan="5"><em><?php print t('No field is hidden'); ?></em></td>
      </tr>
      <?php foreach ($rows['hidden'] as $row): ?>
        <tr class="<?php print $count % 2 == 0 ? 'odd' : 'even'; ?> <?php print $row->class ?>">
          <td><span class="<?php print $row->label_class; ?>"><?php print $row->human_name; ?></span></td>
          <td><?php print $row->weight . $row->hidden_name; ?></td>
          <td><?php if (isset($row->label)) print $row->label; ?></td>
          <td><?php print $row->type; ?></td>
          <td class="field-formatter-summary-cell">
            <?php print $row->settings_summary; ?>
          </td>
          <td>
            <?php print $row->settings_edit; ?>
          </td>
        </tr>
        <?php $count++;
      endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
