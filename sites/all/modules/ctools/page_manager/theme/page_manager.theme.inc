<?php
// $Id: page_manager.theme.inc,v 1.9 2010/11/03 00:01:14 merlinofchaos Exp $

/**
 * @file
 * Preprocess functions for page manager editing templates.
 */

/**
 * Preprocess the page manager edit page.
 */
function template_preprocess_page_manager_edit_page(&$vars) {
  ctools_include('ajax');
  ctools_include('modal');
  ctools_modal_add_js();
  ctools_add_js('dependent');

  ctools_add_css('page-manager', 'page_manager');
  ctools_add_css('wizard');

  $task = $vars['page']->task;

  $page = &$vars['page'];

  $vars['locked'] = '';
  $vars['changed'] = '';
  if (!empty($page->locked)) {
    $vars['locked'] = theme('page_manager_lock', array('page' => $page));
    $vars['changed'] = theme('page_manager_changed', array('text' => t('Locked'), 'description' => t('This page is being edited by another user and you cannot make changes to it.')));
  }
  else if (!empty($page->new)) {
    $vars['changed'] = theme('page_manager_changed', array('text' => t('New'), 'description' => t('This page is newly created and has not yet been saved to the database. It will not be available until you save it.')));
  }
  else if (!empty($page->changed)) {
    $vars['changed'] = theme('page_manager_changed', array('text' => t('Changed'), 'description' => t('This page has been modified, but these modifications are not yet live. While modifying this page, it is locked from modification by other users.')));
  }

  $form_state = array(
    'page' => &$vars['page'],
  );

  $active = $vars['content']['active'];
  if ($active[0] == 'handlers' && isset($vars['operations'][$active[1]])) {
    $vars['operations']['secondary'] = $vars['operations'][$active[1]];
  }
}

/**
 * Turn the rearrange form into a table with tablesorting on.
 */
function theme_page_manager_handler_rearrange($vars) {
  $form = &$vars['form'];
  // Assemble the data for a table from everything in $form['handlers']
  foreach (element_children($form['handlers']) as $id) {
    // provide a reference shortcut.
    $element = &$form['handlers'][$id];
    if (isset($element['title'])) {
      $row = array();

      $row[] = array(
        'data' => render($element['title']),
        'class' => array('page-manager-handler'),
      );

      $element['weight']['#attributes']['class'][] = 'weight';
      $row[] = render($element['weight']);

      $rows[] = array('data' => $row, 'class' => array('draggable'), 'id' => 'page-manager-row-' . $id);
    }
  }

  if (empty($rows)) {
    $rows[] = array(array('data' => t('No task handlers are defined for this task.'), 'colspan' => '5'));
  }

  $header = array(
    array('data' => t('Variant'), 'class' => array('page-manager-handler')),
    t('Weight'),
  );

  drupal_add_tabledrag('page-manager-arrange-handlers', 'order', 'sibling', 'weight');

  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'page-manager-arrange-handlers')));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Draw the "this task is locked from editing" box.
 */
function theme_page_manager_lock($vars) {
  $page = $vars['page'];

  $account  = user_load($page->locked->uid);
  $name     = theme('username', array('account' => $account));
  $lock_age = format_interval(REQUEST_TIME - $page->locked->updated);
  $break    = url(page_manager_edit_url($page->task_name, array('actions', 'break-lock')));

  ctools_add_css('ctools');
  $output = '<div class="ctools-locked">';
  $output .= t('This page is being edited by user !user, and is therefore locked from editing by others. This lock is !age old. Click here to <a href="!break">break this lock</a>.', array('!user' => $name, '!age' => $lock_age, '!break' => $break));
  $output .= '</div>';
  return $output;
}

/**
 * Draw the "you have unsaved changes and this task is locked." message.
 */
function theme_page_manager_changed($vars) {
  $text = $vars['text'];
  $description = $vars['description'];

  ctools_add_css('ctools');
  $output = '<div class="page-manager-changed" title="' . $description . '">';
  $output .= $text;
  $output .= '</div>';

  return $output;
}
