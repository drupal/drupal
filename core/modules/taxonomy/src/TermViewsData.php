<?php

namespace Drupal\taxonomy;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the taxonomy entity type.
 */
class TermViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['taxonomy_term_field_data']['table']['base']['help'] = $this->t('Taxonomy terms are attached to nodes.');
    $data['taxonomy_term_field_data']['table']['base']['access query tag'] = 'taxonomy_term_access';
    $data['taxonomy_term_field_data']['table']['wizard_id'] = 'taxonomy_term';

    $data['taxonomy_term_field_data']['table']['join'] = array(
      // This is provided for the many_to_one argument.
      'taxonomy_index' => array(
        'field' => 'tid',
        'left_field' => 'tid',
      ),
    );

    $data['taxonomy_term_field_data']['tid']['help'] = $this->t('The tid of a taxonomy term.');

    $data['taxonomy_term_field_data']['tid']['argument']['id'] = 'taxonomy';
    $data['taxonomy_term_field_data']['tid']['argument']['name field'] = 'name';
    $data['taxonomy_term_field_data']['tid']['argument']['zero is null'] = TRUE;

    $data['taxonomy_term_field_data']['tid']['filter']['id'] = 'taxonomy_index_tid';
    $data['taxonomy_term_field_data']['tid']['filter']['title'] = $this->t('Term');
    $data['taxonomy_term_field_data']['tid']['filter']['help'] = $this->t('Taxonomy term chosen from autocomplete or select widget.');
    $data['taxonomy_term_field_data']['tid']['filter']['hierarchy table'] = 'taxonomy_term_hierarchy';
    $data['taxonomy_term_field_data']['tid']['filter']['numeric'] = TRUE;

    $data['taxonomy_term_field_data']['tid_raw'] = array(
      'title' => $this->t('Term ID'),
      'help' => $this->t('The tid of a taxonomy term.'),
      'real field' => 'tid',
      'filter' => array(
        'id' => 'numeric',
        'allow empty' => TRUE,
      ),
    );

    $data['taxonomy_term_field_data']['tid_representative'] = array(
      'relationship' => array(
        'title' => $this->t('Representative node'),
        'label'  => $this->t('Representative node'),
        'help' => $this->t('Obtains a single representative node for each term, according to a chosen sort criterion.'),
        'id' => 'groupwise_max',
        'relationship field' => 'tid',
        'outer field' => 'taxonomy_term_field_data.tid',
        'argument table' => 'taxonomy_term_field_data',
        'argument field' => 'tid',
        'base'   => 'node_field_data',
        'field'  => 'nid',
        'relationship' => 'node_field_data:term_node_tid'
      ),
    );

    $data['taxonomy_term_field_data']['vid']['help'] = $this->t('Filter the results of "Taxonomy: Term" to a particular vocabulary.');
    unset($data['taxonomy_term_field_data']['vid']['field']);
    unset($data['taxonomy_term_field_data']['vid']['argument']);
    unset($data['taxonomy_term_field_data']['vid']['sort']);

    $data['taxonomy_term_field_data']['name']['field']['id'] = 'term_name';
    $data['taxonomy_term_field_data']['name']['argument']['many to one'] = TRUE;
    $data['taxonomy_term_field_data']['name']['argument']['empty field name'] = $this->t('Uncategorized');

    $data['taxonomy_term_field_data']['description__value']['field']['click sortable'] = FALSE;

    $data['taxonomy_term_field_data']['changed']['title'] = $this->t('Updated date');
    $data['taxonomy_term_field_data']['changed']['help'] = $this->t('The date the term was last updated.');

    $data['taxonomy_term_field_data']['changed_fulldate'] = array(
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_fulldate',
      ),
    );

    $data['taxonomy_term_field_data']['changed_year_month'] = array(
      'title' => $this->t('Updated year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year_month',
      ),
    );

    $data['taxonomy_term_field_data']['changed_year'] = array(
      'title' => $this->t('Updated year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year',
      ),
    );

    $data['taxonomy_term_field_data']['changed_month'] = array(
      'title' => $this->t('Updated month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_month',
      ),
    );

    $data['taxonomy_term_field_data']['changed_day'] = array(
      'title' => $this->t('Updated day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_day',
      ),
    );

    $data['taxonomy_term_field_data']['changed_week'] = array(
      'title' => $this->t('Updated week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_week',
      ),
    );

    $data['taxonomy_index']['table']['group']  = $this->t('Taxonomy term');

    $data['taxonomy_index']['table']['join'] = array(
      'taxonomy_term_field_data' => array(
        // links directly to taxonomy_term_field_data via tid
        'left_field' => 'tid',
        'field' => 'tid',
      ),
      'node_field_data' => array(
        // links directly to node via nid
        'left_field' => 'nid',
        'field' => 'nid',
      ),
      'taxonomy_term_hierarchy' => array(
        'left_field' => 'tid',
        'field' => 'tid',
      ),
    );

    $data['taxonomy_index']['nid'] = array(
      'title' => $this->t('Content with term'),
      'help' => $this->t('Relate all content tagged with a term.'),
      'relationship' => array(
        'id' => 'standard',
        'base' => 'node',
        'base field' => 'nid',
        'label' => $this->t('node'),
        'skip base' => 'node',
      ),
    );

    // @todo This stuff needs to move to a node field since really it's all
    //   about nodes.
    $data['taxonomy_index']['tid'] = array(
      'group' => $this->t('Content'),
      'title' => $this->t('Has taxonomy term ID'),
      'help' => $this->t('Display content if it has the selected taxonomy terms.'),
      'argument' => array(
        'id' => 'taxonomy_index_tid',
        'name table' => 'taxonomy_term_field_data',
        'name field' => 'name',
        'empty field name' => $this->t('Uncategorized'),
        'numeric' => TRUE,
        'skip base' => 'taxonomy_term_field_data',
      ),
      'filter' => array(
        'title' => $this->t('Has taxonomy term'),
        'id' => 'taxonomy_index_tid',
        'hierarchy table' => 'taxonomy_term_hierarchy',
        'numeric' => TRUE,
        'skip base' => 'taxonomy_term_field_data',
        'allow empty' => TRUE,
      ),
    );

    $data['taxonomy_index']['status'] = [
      'title' => $this->t('Publish status'),
      'help' => $this->t('Whether or not the content related to a term is published.'),
      'filter' => [
        'id' => 'boolean',
        'label' => $this->t('Published status'),
        'type' => 'yes-no',
      ],
    ];

    $data['taxonomy_index']['sticky'] = [
      'title' => $this->t('Sticky status'),
      'help' => $this->t('Whether or not the content related to a term is sticky.'),
      'filter' => [
        'id' => 'boolean',
        'label' => $this->t('Sticky status'),
        'type' => 'yes-no',
      ],
      'sort' => [
        'id' => 'standard',
        'help' => $this->t('Whether or not the content related to a term is sticky. To list sticky content first, set this to descending.'),
      ],
    ];

    $data['taxonomy_index']['created'] = [
      'title' => $this->t('Post date'),
      'help' => $this->t('The date the content related to a term was posted.'),
      'sort' => [
        'id' => 'date'
      ],
      'filter' => [
        'id' => 'date',
      ],
    ];

    $data['taxonomy_term_hierarchy']['table']['group']  = $this->t('Taxonomy term');
    $data['taxonomy_term_hierarchy']['table']['provider']  = 'taxonomy';

    $data['taxonomy_term_hierarchy']['table']['join'] = array(
      'taxonomy_term_hierarchy' => array(
        // Link to self through left.parent = right.tid (going down in depth).
        'left_field' => 'tid',
        'field' => 'parent',
      ),
      'taxonomy_term_field_data' => array(
        // Link directly to taxonomy_term_field_data via tid.
        'left_field' => 'tid',
        'field' => 'tid',
      ),
    );

    $data['taxonomy_term_hierarchy']['parent'] = array(
      'title' => $this->t('Parent term'),
      'help' => $this->t('The parent term of the term. This can produce duplicate entries if you are using a vocabulary that allows multiple parents.'),
      'relationship' => array(
        'base' => 'taxonomy_term_field_data',
        'field' => 'parent',
        'label' => $this->t('Parent'),
        'id' => 'standard',
      ),
      'filter' => array(
        'help' => $this->t('Filter the results of "Taxonomy: Term" by the parent pid.'),
        'id' => 'numeric',
      ),
      'argument' => array(
        'help' => $this->t('The parent term of the term.'),
        'id' => 'taxonomy',
      ),
    );

    return $data;
  }

}
