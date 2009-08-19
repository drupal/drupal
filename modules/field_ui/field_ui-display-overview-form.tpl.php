<?php
// $Id: field_ui-display-overview-form.tpl.php,v 1.1 2009/08/19 13:31:13 webchick Exp $

/**
 * @file
 * Default theme implementation to configure field display settings.
 *
 * Available variables:
 *
 * - $form: The complete form for the field display settings.
 * - $contexts: An associative array of the available contexts for these fields.
 *   On the node field display settings this defaults to including "teaser" and
 *   "full" as the available contexts.
 * - $rows: The field display settings form broken down into rendered rows for
 *   printing as a table.
 * - $submit: The rendered submit button for this form.
 *
 * @see field_ui_display_overview_form()
 * @see template_preprocess_field_ui_display_overview_form()
 */
?>
<?php if ($rows): ?>
  <table id="field-display-overview" class="sticky-enabled">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <?php foreach ($contexts as $key => $value): ?>
          <th colspan="2"><?php print $value; ?>
        <?php endforeach; ?>
      </tr>
      <tr>
        <th><?php print t('Field'); ?></th>
        <?php foreach ($contexts as $key => $value): ?>
          <th><?php print t('Label'); ?></th>
          <th><?php print t('Format'); ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      $count = 0;
      foreach ($rows as $row): ?>
        <tr class="<?php print $count % 2 == 0 ? 'odd' : 'even'; ?>">
          <td><span class="<?php print $row->label_class; ?>"><?php print $row->human_name; ?></span></td>
          <?php foreach ($contexts as $context => $title): ?>
            <td><?php print $row->{$context}->label; ?></td>
            <td><?php print $row->{$context}->type; ?></td>
          <?php endforeach; ?>
        </tr>
        <?php $count++;
      endforeach; ?>
    </tbody>
  </table>
  <?php print $submit; ?>
<?php endif; ?>
