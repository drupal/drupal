<?php

/**
 * @file
 * Provide views data for node.module.
 */

use Drupal\views\Analyzer;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_data().
 */
function node_views_data() {

  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['node']['table']['group'] = t('Content');

  // Advertise this table as a possible base table.
  $data['node']['table']['base'] = array(
    'field' => 'nid',
    'title' => t('Content'),
    'weight' => -10,
    'access query tag' => 'node_access',
    'defaults' => array(
      'field' => 'title',
    ),
  );
  $data['node']['table']['entity type'] = 'node';
  $data['node']['table']['wizard_id'] = 'node';

  $data['node_field_data']['table']['group'] = t('Content');
  $data['node_field_data']['table']['entity type'] = 'node';
  $data['node_field_data']['table']['join']['node'] = array(
    'type' => 'INNER',
    'left_field' => 'nid',
    'field' => 'nid',
  );

  $data['node']['nid'] = array(
    'title' => t('Nid'),
    'help' => t('The node ID.'),
    'field' => array(
      'id' => 'node',
    ),
    'argument' => array(
      'id' => 'node_nid',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'nid',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // This definition has more items in it than it needs to as an example.
  $data['node_field_data']['title'] = array(
    'title' => t('Title'),
    'help' => t('The content title.'),
    'field' => array(
      // This is the real field which could be left out since it is the same.
      'field' => 'title',
      // This is the UI group which could be left out since it is the same.
      'group' => t('Content'),
      'id' => 'node',
      'link_to_node default' => TRUE,
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['node_field_data']['created'] = array(
    'title' => t('Post date'),
    'help' => t('The date the content was posted.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['node_field_data']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('The date the content was last updated.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['node_field_data']['type'] = array(
    'title' => t('Type'),
    'help' => t('The content type (for example, "blog entry", "forum post", "story", etc).'),
    'field' => array(
      'id' => 'node_type',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'bundle',
    ),
    'argument' => array(
      'id' => 'node_type',
    ),
  );

  $data['node_field_data']['status'] = array(
    'title' => t('Published status'),
    'help' => t('Whether or not the content is published.'),
    'field' => array(
      'id' => 'boolean',
      'output formats' => array(
        'published-notpublished' => array(t('Published'), t('Not published')),
      ),
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Published status'),
      'type' => 'yes-no',
      // Use status = 1 instead of status <> 0 in WHERE statement.
      'use_equal' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['node_field_data']['status_extra'] = array(
    'title' => t('Published status or admin user'),
    'help' => t('Filters out unpublished content if the current user cannot view it.'),
    'filter' => array(
      'field' => 'status',
      'id' => 'node_status',
      'label' => t('Published status or admin user'),
    ),
  );

  $data['node_field_data']['promote'] = array(
    'title' => t('Promoted to front page status'),
    'help' => t('Whether or not the content is promoted to the front page.'),
    'field' => array(
      'id' => 'boolean',
      'output formats' => array(
        'promoted-notpromoted' => array(t('Promoted'), t('Not promoted')),
      ),
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Promoted to front page status'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['node_field_data']['sticky'] = array(
    'title' => t('Sticky status'),
    'help' => t('Whether or not the content is sticky.'),
    'field' => array(
      'id' => 'boolean',
      'output formats' => array(
        'sticky' => array(t('Sticky'), t('Not sticky')),
      ),
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Sticky status'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'id' => 'standard',
      'help' => t('Whether or not the content is sticky. To list sticky content first, set this to descending.'),
    ),
  );

  if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
    $data['node']['translation_link'] = array(
      'title' => t('Translation link'),
      'help' => t('Provide a link to the translations overview for nodes.'),
      'field' => array(
        'id' => 'content_translation_link',
      ),
    );
  }

  $data['node']['view_node'] = array(
    'field' => array(
      'title' => t('Link to content'),
      'help' => t('Provide a simple link to the content.'),
      'id' => 'node_link',
    ),
  );

  $data['node']['edit_node'] = array(
    'field' => array(
      'title' => t('Link to edit content'),
      'help' => t('Provide a simple link to edit the content.'),
      'id' => 'node_link_edit',
    ),
  );

  $data['node']['delete_node'] = array(
    'field' => array(
      'title' => t('Link to delete content'),
      'help' => t('Provide a simple link to delete the content.'),
      'id' => 'node_link_delete',
    ),
  );

  $data['node']['path'] = array(
    'field' => array(
      'title' => t('Path'),
      'help' => t('The aliased path to this content.'),
      'id' => 'node_path',
    ),
  );

  // Bogus fields for aliasing purposes.

  $data['node_field_data']['created_fulldate'] = array(
    'title' => t('Created date'),
    'help' => t('Date in the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'created',
      'id' => 'date_fulldate',
    ),
  );

  $data['node_field_data']['created_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'created',
      'id' => 'date_year_month',
    ),
  );

  $data['node_field_data']['created_year'] = array(
    'title' => t('Created year'),
    'help' => t('Date in the form of YYYY.'),
    'argument' => array(
      'field' => 'created',
      'id' => 'date_year',
    ),
  );

  $data['node_field_data']['created_month'] = array(
    'title' => t('Created month'),
    'help' => t('Date in the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'created',
      'id' => 'date_month',
    ),
  );

  $data['node_field_data']['created_day'] = array(
    'title' => t('Created day'),
    'help' => t('Date in the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'created',
      'id' => 'date_day',
    ),
  );

  $data['node_field_data']['created_week'] = array(
    'title' => t('Created week'),
    'help' => t('Date in the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'created',
      'id' => 'date_week',
    ),
  );

  $data['node_field_data']['changed_fulldate'] = array(
    'title' => t('Updated date'),
    'help' => t('Date in the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_fulldate',
    ),
  );

  $data['node_field_data']['changed_year_month'] = array(
    'title' => t('Updated year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_year_month',
    ),
  );

  $data['node_field_data']['changed_year'] = array(
    'title' => t('Updated year'),
    'help' => t('Date in the form of YYYY.'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_year',
    ),
  );

  $data['node_field_data']['changed_month'] = array(
    'title' => t('Updated month'),
    'help' => t('Date in the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_month',
    ),
  );

  $data['node_field_data']['changed_day'] = array(
    'title' => t('Updated day'),
    'help' => t('Date in the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_day',
    ),
  );

  $data['node_field_data']['changed_week'] = array(
    'title' => t('Updated week'),
    'help' => t('Date in the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_week',
    ),
  );

  $data['node_field_data']['uid'] = array(
    'title' => t('Author uid'),
    'help' => t('The user authoring the content. If you need more fields than the uid add the content: author relationship'),
    'relationship' => array(
      'title' => t('Content author'),
      'help' => t('Relate content to the user who created it.'),
      'id' => 'standard',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('author'),
    ),
    'filter' => array(
      'id' => 'user_name',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'user',
    ),
  );

  $data['node']['node_listing_empty'] = array(
    'title' => t('Empty Node Frontpage behavior'),
    'help' => t('Provides a link to the node add overview page.'),
    'area' => array(
      'id' => 'node_listing_empty',
    ),
  );

  $data['node_field_data']['uid_revision'] = array(
    'title' => t('User has a revision'),
    'help' => t('All nodes where a certain user has a revision'),
    'real field' => 'nid',
    'filter' => array(
      'id' => 'node_uid_revision',
    ),
    'argument' => array(
      'id' => 'node_uid_revision',
    ),
  );

  $data['node_revision']['table']['entity type'] = 'node';
  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['node_revision']['table']['group']  = t('Content revision');
  $data['node_revision']['table']['wizard_id'] = 'node_revision';

  // Advertise this table as a possible base table.
  $data['node_revision']['table']['base'] = array(
    'field' => 'vid',
    'title' => t('Content revision'),
    'help' => t('Content revision is a history of changes to content.'),
    'defaults' => array(
      'field' => 'title',
    ),
  );

  // For other base tables, explain how we join.
  $data['node_revision']['table']['join'] = array(
    'node' => array(
      'left_field' => 'vid',
      'field' => 'vid',
    ),
  );

  $data['node_revision']['nid'] = array(
    'title' => t('Nid'),
    'help' => t('The revision NID of the content revision.'),
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'node_nid',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'node',
      'base field' => 'nid',
      'title' => t('Content'),
      'label' => t('Get the actual content from a content revision.'),
    ),
  );

  $data['node_revision']['vid'] = array(
    'title' => t('Vid'),
    'help' => t('The revision ID of the content revision.'),
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'node_vid',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'node',
      'base field' => 'vid',
      'title' => t('Content'),
      'label' => t('Get the actual content from a content revision.'),
    ),
  );

  if (\Drupal::moduleHandler()->moduleExists('language')) {
    $data['node_revision']['langcode'] = array(
      'title' => t('Language'),
      'help' => t('The language the content is in.'),
      'field' => array(
        'id' => 'node_language',
      ),
      'filter' => array(
        'id' => 'language',
      ),
      'argument' => array(
        'id' => 'language',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
  }

  $data['node_revision']['log'] = array(
    'title' => t('Log message'),
    'help' => t('The log message entered when the revision was created.'),
    'field' => array(
      'id' => 'xss',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['node_revision']['revision_uid'] = array(
    'title' => t('User'),
    'help' => t('Relate a content revision to the user who created the revision.'),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'users',
      'base field' => 'uid',
      'label' => t('revision user'),
    ),
  );

  $data['node_field_revision']['table']['entity type'] = 'node';
  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['node_field_revision']['table']['group']  = t('Content revision');
  $data['node_field_revision']['table']['wizard_id'] = 'node_field_revision';

  // For other base tables, explain how we join.
  $data['node_field_revision']['table']['join'] = array(
    'node' => array(
      'left_field' => 'vid',
      'field' => 'vid',
    ),
  );

  $data['node_field_revision']['status'] = array(
    'title' => t('Published'),
    'help' => t('Whether or not the content is published.'),
    'field' => array(
      'id' => 'boolean',
      'output formats' => array(
        'published-notpublished' => array(t('Published'), t('Not published')),
      ),
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Published'),
      'type' => 'yes-no',
      // Use status = 1 instead of status <> 0 in WHERE statement.
      'use_equal' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['node_field_revision']['title'] = array(
    'title' => t('Title'),
    'help' => t('The content title.'),
    'field' => array(
      'field' => 'title',
      'id' => 'node_revision',
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['node_field_revision']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('The date the node was last updated.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['node_revision']['link_to_revision'] = array(
    'field' => array(
      'title' => t('Link to revision'),
      'help' => t('Provide a simple link to the revision.'),
      'id' => 'node_revision_link',
      'click sortable' => FALSE,
    ),
  );

  $data['node_revision']['revert_revision'] = array(
    'field' => array(
      'title' => t('Link to revert revision'),
      'help' => t('Provide a simple link to revert to the revision.'),
      'id' => 'node_revision_link_revert',
      'click sortable' => FALSE,
    ),
  );

  $data['node_revision']['delete_revision'] = array(
    'field' => array(
      'title' => t('Link to delete revision'),
      'help' => t('Provide a simple link to delete the content revision.'),
      'id' => 'node_revision_link_delete',
      'click sortable' => FALSE,
    ),
  );

  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['node_access']['table']['group']  = t('Content access');

  // For other base tables, explain how we join.
  $data['node_access']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );
  $data['node_access']['nid'] = array(
    'title' => t('Access'),
    'help' => t('Filter by access.'),
    'filter' => array(
      'id' => 'node_access',
      'help' => t('Filter for content by view access. <strong>Not necessary if you are using node as your base table.</strong>'),
    ),
  );

  // Add search table, fields, filters, etc., but only if a page using the
  // node_search plugin is enabled.
  if (\Drupal::moduleHandler()->moduleExists('search')) {
    $enabled = FALSE;
    $search_page_repository = \Drupal::service('search.search_page_repository');
    foreach ($search_page_repository->getActiveSearchpages() as $page) {
      if ($page->getPlugin()->getPluginId() == 'node_search') {
        $enabled = TRUE;
        break;
      }
    }

    if ($enabled) {
      $data['node_search_index']['table']['group'] = t('Search');

      // Automatically join to the node table. Use a Views table alias to
      // allow other modules to use this table too, if they use the search
      // index.
      $data['node_search_index']['table']['join'] = array(
        'node' => array(
          'left_field' => 'nid',
          'field' => 'sid',
          'table' => 'search_index',
          'extra' => "node_search_index.type = 'node_search'",
        )
      );

      $data['node_search_total']['table']['join'] = array(
        'node_search_index' => array(
          'left_field' => 'word',
          'field' => 'word',
        ),
      );

      $data['node_search_dataset']['table']['join'] = array(
        'node_search_index' => array(
          'left_field' => 'sid',
          'field' => 'sid',
          'extra' => 'node_search_index.type = node_search_dataset.type',
          'type' => 'INNER',
        ),
      );

      $data['node_search_index']['score'] = array(
        'title' => t('Score'),
        'help' => t('The score of the search item. This will not be used if the search filter is not also present.'),
        'field' => array(
          'id' => 'search_score',
          'float' => TRUE,
          'no group by' => TRUE,
        ),
        'sort' => array(
          'id' => 'search_score',
          'no group by' => TRUE,
        ),
      );

      $data['node_search_index']['keys'] = array(
        'title' => t('Search Keywords'),
        'help' => t('The keywords to search for.'),
        'filter' => array(
          'id' => 'search_keywords',
          'no group by' => TRUE,
          'search_type' => 'node_search',
        ),
        'argument' => array(
          'id' => 'search',
          'no group by' => TRUE,
          'search_type' => 'node_search',
        ),
      );

    }
  }

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function node_views_data_alter(&$data) {
  $data['node']['node_bulk_form'] = array(
    'title' => t('Node operations bulk form'),
    'help' => t('Add a form element that lets you run operations on multiple nodes.'),
    'field' => array(
      'id' => 'node_bulk_form',
    ),
  );
}

/**
 * Implements hook_preprocess_node().
 */
function node_row_node_view_preprocess_node(&$variables) {
  $options = $variables['view']->rowPlugin->options;

  if (!$options['links']) {
    unset($variables['content']['links']);
  }
}

/**
 * Implements hook_views_wizard().
 */
function node_views_wizard() {
  // @todo: figure this piece out.
  if (\Drupal::moduleHandler()->moduleExists('statistics')) {
    $plugins['node']['available_sorts']['node_counter-totalcount:DESC'] = t('Number of hits');
  }

}
