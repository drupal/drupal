<?php

namespace Drupal\node;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the node entity type.
 */
class NodeViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['node_field_data']['table']['base']['weight'] = -10;
    $data['node_field_data']['table']['base']['access query tag'] = 'node_access';
    $data['node_field_data']['table']['wizard_id'] = 'node';

    $data['node_field_data']['nid']['argument'] = [
      'id' => 'node_nid',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'nid',
    ];

    $data['node_field_data']['title']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];

    $data['node_field_data']['title']['field']['link_to_node default'] = TRUE;

    $data['node_field_data']['type']['argument']['id'] = 'node_type';

    $data['node_field_data']['langcode']['help'] = $this->t('The language of the content or translation.');

    $data['node_field_data']['status']['filter']['label'] = $this->t('Published status');
    $data['node_field_data']['status']['filter']['type'] = 'yes-no';
    // Use status = 1 instead of status <> 0 in WHERE statement.
    $data['node_field_data']['status']['filter']['use_equal'] = TRUE;

    $data['node_field_data']['status_extra'] = array(
      'title' => $this->t('Published status or admin user'),
      'help' => $this->t('Filters out unpublished content if the current user cannot view it.'),
      'filter' => array(
        'field' => 'status',
        'id' => 'node_status',
        'label' => $this->t('Published status or admin user'),
      ),
    );

    $data['node_field_data']['promote']['help'] = $this->t('A boolean indicating whether the node is visible on the front page.');
    $data['node_field_data']['promote']['filter']['label'] = $this->t('Promoted to front page status');
    $data['node_field_data']['promote']['filter']['type'] = 'yes-no';

    $data['node_field_data']['sticky']['help'] = $this->t('A boolean indicating whether the node should sort to the top of content lists.');
    $data['node_field_data']['sticky']['filter']['label'] = $this->t('Sticky status');
    $data['node_field_data']['sticky']['filter']['type'] = 'yes-no';
    $data['node_field_data']['sticky']['sort']['help'] = $this->t('Whether or not the content is sticky. To list sticky content first, set this to descending.');

    $data['node']['path'] = array(
      'field' => array(
        'title' => $this->t('Path'),
        'help' => $this->t('The aliased path to this content.'),
        'id' => 'node_path',
      ),
    );

    $data['node']['node_bulk_form'] = array(
      'title' => $this->t('Node operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple nodes.'),
      'field' => array(
        'id' => 'node_bulk_form',
      ),
    );

    // Bogus fields for aliasing purposes.

    // @todo Add similar support to any date field
    // @see https://www.drupal.org/node/2337507
    $data['node_field_data']['created_fulldate'] = array(
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_fulldate',
      ),
    );

    $data['node_field_data']['created_year_month'] = array(
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_year_month',
      ),
    );

    $data['node_field_data']['created_year'] = array(
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_year',
      ),
    );

    $data['node_field_data']['created_month'] = array(
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_month',
      ),
    );

    $data['node_field_data']['created_day'] = array(
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_day',
      ),
    );

    $data['node_field_data']['created_week'] = array(
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_week',
      ),
    );

    $data['node_field_data']['changed_fulldate'] = array(
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_fulldate',
      ),
    );

    $data['node_field_data']['changed_year_month'] = array(
      'title' => $this->t('Updated year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year_month',
      ),
    );

    $data['node_field_data']['changed_year'] = array(
      'title' => $this->t('Updated year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year',
      ),
    );

    $data['node_field_data']['changed_month'] = array(
      'title' => $this->t('Updated month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_month',
      ),
    );

    $data['node_field_data']['changed_day'] = array(
      'title' => $this->t('Updated day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_day',
      ),
    );

    $data['node_field_data']['changed_week'] = array(
      'title' => $this->t('Updated week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_week',
      ),
    );

    $data['node_field_data']['uid']['help'] = $this->t('The user authoring the content. If you need more fields than the uid add the content: author relationship');
    $data['node_field_data']['uid']['filter']['id'] = 'user_name';
    $data['node_field_data']['uid']['relationship']['title'] = $this->t('Content author');
    $data['node_field_data']['uid']['relationship']['help'] = $this->t('Relate content to the user who created it.');
    $data['node_field_data']['uid']['relationship']['label'] = $this->t('author');

    $data['node']['node_listing_empty'] = array(
      'title' => $this->t('Empty Node Frontpage behavior'),
      'help' => $this->t('Provides a link to the node add overview page.'),
      'area' => array(
        'id' => 'node_listing_empty',
      ),
    );

    $data['node_field_data']['uid_revision']['title'] = $this->t('User has a revision');
    $data['node_field_data']['uid_revision']['help'] = $this->t('All nodes where a certain user has a revision');
    $data['node_field_data']['uid_revision']['real field'] = 'nid';
    $data['node_field_data']['uid_revision']['filter']['id'] = 'node_uid_revision';
    $data['node_field_data']['uid_revision']['argument']['id'] = 'node_uid_revision';

    $data['node_field_revision']['table']['wizard_id'] = 'node_revision';

    // Advertise this table as a possible base table.
    $data['node_field_revision']['table']['base']['help'] = $this->t('Content revision is a history of changes to content.');
    $data['node_field_revision']['table']['base']['defaults']['title'] = 'title';

    $data['node_field_revision']['nid']['argument'] = [
      'id' => 'node_nid',
      'numeric' => TRUE,
    ];
    // @todo the NID field needs different behaviour on revision/non-revision
    //   tables. It would be neat if this could be encoded in the base field
    //   definition.
    $data['node_field_revision']['nid']['relationship']['id'] = 'standard';
    $data['node_field_revision']['nid']['relationship']['base'] = 'node_field_data';
    $data['node_field_revision']['nid']['relationship']['base field'] = 'nid';
    $data['node_field_revision']['nid']['relationship']['title'] = $this->t('Content');
    $data['node_field_revision']['nid']['relationship']['label'] = $this->t('Get the actual content from a content revision.');

    $data['node_field_revision']['vid'] = array(
      'argument' => array(
        'id' => 'node_vid',
        'numeric' => TRUE,
      ),
      'relationship' => array(
        'id' => 'standard',
        'base' => 'node_field_data',
        'base field' => 'vid',
        'title' => $this->t('Content'),
        'label' => $this->t('Get the actual content from a content revision.'),
      ),
    ) + $data['node_field_revision']['vid'];

    $data['node_field_revision']['langcode']['help'] = $this->t('The language the original content is in.');

    $data['node_revision']['revision_uid']['help'] = $this->t('Relate a content revision to the user who created the revision.');
    $data['node_revision']['revision_uid']['relationship']['label'] = $this->t('revision user');

    $data['node_field_revision']['table']['wizard_id'] = 'node_field_revision';

    $data['node_field_revision']['table']['join']['node_field_data']['left_field'] = 'vid';
    $data['node_field_revision']['table']['join']['node_field_data']['field'] = 'vid';

    $data['node_field_revision']['status']['filter']['label'] = $this->t('Published');
    $data['node_field_revision']['status']['filter']['type'] = 'yes-no';
    $data['node_field_revision']['status']['filter']['use_equal'] = TRUE;

    $data['node_field_revision']['promote']['help'] = $this->t('A boolean indicating whether the node is visible on the front page.');

    $data['node_field_revision']['sticky']['help'] = $this->t('A boolean indicating whether the node should sort to the top of content lists.');

    $data['node_field_revision']['langcode']['help'] = $this->t('The language of the content or translation.');

    $data['node_field_revision']['link_to_revision'] = array(
      'field' => array(
        'title' => $this->t('Link to revision'),
        'help' => $this->t('Provide a simple link to the revision.'),
        'id' => 'node_revision_link',
        'click sortable' => FALSE,
      ),
    );

    $data['node_field_revision']['revert_revision'] = array(
      'field' => array(
        'title' => $this->t('Link to revert revision'),
        'help' => $this->t('Provide a simple link to revert to the revision.'),
        'id' => 'node_revision_link_revert',
        'click sortable' => FALSE,
      ),
    );

    $data['node_field_revision']['delete_revision'] = array(
      'field' => array(
        'title' => $this->t('Link to delete revision'),
        'help' => $this->t('Provide a simple link to delete the content revision.'),
        'id' => 'node_revision_link_delete',
        'click sortable' => FALSE,
      ),
    );

    // Define the base group of this table. Fields that don't have a group defined
    // will go into this field by default.
    $data['node_access']['table']['group']  = $this->t('Content access');

    // For other base tables, explain how we join.
    $data['node_access']['table']['join'] = array(
      'node_field_data' => array(
        'left_field' => 'nid',
        'field' => 'nid',
      ),
    );
    $data['node_access']['nid'] = array(
      'title' => $this->t('Access'),
      'help' => $this->t('Filter by access.'),
      'filter' => array(
        'id' => 'node_access',
        'help' => $this->t('Filter for content by view access. <strong>Not necessary if you are using node as your base table.</strong>'),
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
        $data['node_search_index']['table']['group'] = $this->t('Search');

        // Automatically join to the node table (or actually, node_field_data).
        // Use a Views table alias to allow other modules to use this table too,
        // if they use the search index.
        $data['node_search_index']['table']['join'] = array(
          'node_field_data' => array(
            'left_field' => 'nid',
            'field' => 'sid',
            'table' => 'search_index',
            'extra' => "node_search_index.type = 'node_search' AND node_search_index.langcode = node_field_data.langcode",
          )
        );

        $data['node_search_total']['table']['join'] = array(
          'node_search_index' => array(
            'left_field' => 'word',
            'field' => 'word',
          ),
        );

        $data['node_search_dataset']['table']['join'] = array(
          'node_field_data' => array(
            'left_field' => 'sid',
            'left_table' => 'node_search_index',
            'field' => 'sid',
            'table' => 'search_dataset',
            'extra' => 'node_search_index.type = node_search_dataset.type AND node_search_index.langcode = node_search_dataset.langcode',
            'type' => 'INNER',
          ),
        );

        $data['node_search_index']['score'] = array(
          'title' => $this->t('Score'),
          'help' => $this->t('The score of the search item. This will not be used if the search filter is not also present.'),
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
          'title' => $this->t('Search Keywords'),
          'help' => $this->t('The keywords to search for.'),
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

}
