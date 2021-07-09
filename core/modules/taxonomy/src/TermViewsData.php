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

    $data['taxonomy_term_field_data']['table']['join'] = [
      // This is provided for the many_to_one argument.
      'taxonomy_index' => [
        'field' => 'tid',
        'left_field' => 'tid',
      ],
    ];

    $data['taxonomy_term_field_data']['tid']['help'] = $this->t('The tid of a taxonomy term.');

    $data['taxonomy_term_field_data']['tid']['argument']['id'] = 'taxonomy';
    $data['taxonomy_term_field_data']['tid']['argument']['name field'] = 'name';
    $data['taxonomy_term_field_data']['tid']['argument']['zero is null'] = TRUE;

    $data['taxonomy_term_field_data']['tid']['filter']['id'] = 'taxonomy_index_tid';
    $data['taxonomy_term_field_data']['tid']['filter']['title'] = $this->t('Term');
    $data['taxonomy_term_field_data']['tid']['filter']['help'] = $this->t('Taxonomy term chosen from autocomplete or select widget.');
    $data['taxonomy_term_field_data']['tid']['filter']['hierarchy table'] = 'taxonomy_term__parent';
    $data['taxonomy_term_field_data']['tid']['filter']['numeric'] = TRUE;

    $data['taxonomy_term_field_data']['tid_raw'] = [
      'title' => $this->t('Term ID'),
      'help' => $this->t('The tid of a taxonomy term.'),
      'real field' => 'tid',
      'filter' => [
        'id' => 'numeric',
        'allow empty' => TRUE,
      ],
    ];

    $data['taxonomy_term_field_data']['tid_representative'] = [
      'relationship' => [
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
        'relationship' => 'node_field_data:term_node_tid',
      ],
    ];

    $data['taxonomy_term_field_data']['vid']['help'] = $this->t('Filter the results of "Taxonomy: Term" to a particular vocabulary.');
    $data['taxonomy_term_field_data']['vid']['field']['help'] = t('The vocabulary name.');
    $data['taxonomy_term_field_data']['vid']['argument']['id'] = 'vocabulary_vid';
    unset($data['taxonomy_term_field_data']['vid']['sort']);

    $data['taxonomy_term_field_data']['name']['field']['id'] = 'term_name';
    $data['taxonomy_term_field_data']['name']['argument']['many to one'] = TRUE;
    $data['taxonomy_term_field_data']['name']['argument']['empty field name'] = $this->t('Uncategorized');

    $data['taxonomy_term_field_data']['description__value']['field']['click sortable'] = FALSE;

    $data['taxonomy_term_field_data']['changed']['title'] = $this->t('Updated date');
    $data['taxonomy_term_field_data']['changed']['help'] = $this->t('The date the term was last updated.');

    $data['taxonomy_term_field_data']['changed_fulldate'] = [
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['taxonomy_term_field_data']['changed_year_month'] = [
      'title' => $this->t('Updated year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    $data['taxonomy_term_field_data']['changed_year'] = [
      'title' => $this->t('Updated year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    $data['taxonomy_term_field_data']['changed_month'] = [
      'title' => $this->t('Updated month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    $data['taxonomy_term_field_data']['changed_day'] = [
      'title' => $this->t('Updated day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    $data['taxonomy_term_field_data']['changed_week'] = [
      'title' => $this->t('Updated week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    // Link to self through left.parent = right.tid (going down in depth).
    $data['taxonomy_term__parent']['table']['join']['taxonomy_term__parent'] = [
      'left_field' => 'entity_id',
      'field' => 'parent_target_id',
    ];

    $data['taxonomy_term__parent']['parent_target_id']['help'] = $this->t('The parent term of the term. This can produce duplicate entries if you are using a vocabulary that allows multiple parents.');
    $data['taxonomy_term__parent']['parent_target_id']['relationship']['label'] = $this->t('Parent');
    $data['taxonomy_term__parent']['parent_target_id']['argument']['id'] = 'taxonomy';

    return $data;
  }

}
