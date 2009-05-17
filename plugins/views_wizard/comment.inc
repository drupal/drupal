<?php

/**
 * @file
 * Views wizard for comment views.
 */

// Parent plugin.
if (module_exists('comment')) {
  $plugin = array(
    'name' => 'comment',
    'base_table' => 'comment',
    'created_column' => 'created',
    'form_wizard_class' => array(
      'file' => 'views_ui_comment_views_wizard.class.php',
      'class' => 'ViewsUiCommentViewsWizard',
    ),
    'title' => t('Comments'),
    'filters' => array(
      'status' => array(
        'value' => COMMENT_PUBLISHED,
        'table' => 'comment',
        'field' => 'status',
      ),
      'status_node' => array(
        'value' => NODE_PUBLISHED,
        'table' => 'node',
        'field' => 'status',
        'relationship' => 'nid',
      ),
    ),
    'path_field' => array(
      'id' => 'cid',
      'table' => 'comment',
      'field' => 'cid',
      'exclude' => TRUE,
      'link_to_comment' => FALSE,
      'alter' => array(
        'alter_text' => 1,
        'text' => 'comment/[cid]#comment-[cid]',
      ),
    ),
  );
}
