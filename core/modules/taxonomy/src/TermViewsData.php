<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermViewsData.
 */

namespace Drupal\taxonomy;

use Drupal\views\EntityViewsDataInterface;

/**
 * Provides the views data for the taxonomy entity type.
 */
class TermViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = array();

    $data['taxonomy_term_data']['table']['group']  = t('Taxonomy term');
    $data['taxonomy_term_data']['table']['base'] = array(
      'field' => 'tid',
      'title' => t('Term'),
      'help' => t('Taxonomy terms are attached to nodes.'),
      'access query tag' => 'term_access',
    );
    $data['taxonomy_term_data']['table']['entity type'] = 'taxonomy_term';
    $data['taxonomy_term_data']['table']['wizard_id'] = 'taxonomy_term';

    $data['taxonomy_term_data']['table']['join'] = array(
      // This is provided for the many_to_one argument.
      'taxonomy_index' => array(
        'field' => 'tid',
        'left_field' => 'tid',
      ),
    );

    $data['taxonomy_term_field_data']['table']['group'] = t('Taxonomy term');
    $data['taxonomy_term_field_data']['table']['entity type'] = 'taxonomy_term';
    $data['taxonomy_term_field_data']['table']['join']['taxonomy_term_data'] = array(
      'type' => 'INNER',
      'left_field' => 'tid',
      'field' => 'tid',
    );

    $data['taxonomy_term_data']['tid'] = array(
      'title' => t('Term ID'),
      'help' => t('The tid of a taxonomy term.'),
      'field' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'argument' => array(
        'id' => 'taxonomy',
        'name field' => 'name',
        'zero is null' => TRUE,
      ),
      'filter' => array(
        'title' => t('Term'),
        'help' => t('Taxonomy term chosen from autocomplete or select widget.'),
        'id' => 'taxonomy_index_tid',
        'hierarchy table' => 'taxonomy_term_hierarchy',
        'numeric' => TRUE,
      ),
    );

    $data['taxonomy_term_data']['tid_raw'] = array(
      'title' => t('Term ID'),
      'help' => t('The tid of a taxonomy term.'),
      'real field' => 'tid',
      'filter' => array(
        'id' => 'numeric',
        'allow empty' => TRUE,
      ),
    );

    $data['taxonomy_term_data']['tid_representative'] = array(
      'relationship' => array(
        'title' => t('Representative node'),
        'label'  => t('Representative node'),
        'help' => t('Obtains a single representative node for each term, according to a chosen sort criterion.'),
        'id' => 'groupwise_max',
        'relationship field' => 'tid',
        'outer field' => 'taxonomy_term_data.tid',
        'argument table' => 'taxonomy_term_field_data',
        'argument field' =>  'tid',
        'base'   => 'node',
        'field'  => 'nid',
        'relationship' => 'node:term_node_tid'
      ),
    );

    $data['taxonomy_term_field_data']['name'] = array(
      'title' => t('Name'),
      'help' => t('The taxonomy term name.'),
      'field' => array(
        'id' => 'taxonomy',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'filter' => array(
        'id' => 'string',
        'help' => t('Taxonomy term name.'),
      ),
      'argument' => array(
        'id' => 'string',
        'help' => t('Taxonomy term name.'),
        'many to one' => TRUE,
        'empty field name' => t('Uncategorized'),
      ),
    );

    $data['taxonomy_term_field_data']['weight'] = array(
      'title' => t('Weight'),
      'help' => t('The term weight field'),
      'field' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
    );

    $data['taxonomy_term_field_data']['description__value'] = array(
      'title' => t('Term description'),
      'help' => t('The description associated with a taxonomy term.'),
      'field' => array(
        'id' => 'markup',
        'format' => array('field' => 'description__format'),
        'click sortable' => FALSE,
      ),
      'filter' => array(
        'id' => 'string',
      ),
    );

    $data['taxonomy_term_data']['vid'] = array(
      'title' => t('Vocabulary'),
      'help' => t('Filter the results of "Taxonomy: Term" to a particular vocabulary.'),
      'filter' => array(
        'id' => 'bundle',
      ),
    );

    $data['taxonomy_term_field_data']['langcode'] = array(
      'title' => t('Language'), // The item it appears as on the UI,
      'help' => t('Language of the term'),
      'field' => array(
        'id' => 'taxonomy_term_language',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'filter' => array(
        'id' => 'language',
      ),
      'argument' => array(
        'id' => 'language',
      ),
    );

    $data['taxonomy_term_data']['edit_term'] = array(
      'field' => array(
        'title' => t('Term edit link'),
        'help' => t('Provide a simple link to edit the term.'),
        'id' => 'term_link_edit',
        'click sortable' => FALSE,
      ),
    );

    $data['taxonomy_term_field_data']['changed'] = array(
      'title' => t('Updated date'),
      'help' => t('The date the term was last updated.'),
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

    $data['taxonomy_term_field_data']['changed_fulldate'] = array(
      'title' => t('Updated date'),
      'help' => t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_fulldate',
      ),
    );

    $data['taxonomy_term_field_data']['changed_year_month'] = array(
      'title' => t('Updated year + month'),
      'help' => t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year_month',
      ),
    );

    $data['taxonomy_term_field_data']['changed_year'] = array(
      'title' => t('Updated year'),
      'help' => t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year',
      ),
    );

    $data['taxonomy_term_field_data']['changed_month'] = array(
      'title' => t('Updated month'),
      'help' => t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_month',
      ),
    );

    $data['taxonomy_term_field_data']['changed_day'] = array(
      'title' => t('Updated day'),
      'help' => t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_day',
      ),
    );

    $data['taxonomy_term_field_data']['changed_week'] = array(
      'title' => t('Updated week'),
      'help' => t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_week',
      ),
    );

    if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
      $data['taxonomy_term_data']['translation_link'] = array(
        'title' => t('Translation link'),
        'help' => t('Provide a link to the translations overview for taxonomy terms.'),
        'field' => array(
          'id' => 'content_translation_link',
        ),
      );
    }

    $data['taxonomy_index']['table']['group']  = t('Taxonomy term');

    $data['taxonomy_index']['table']['join'] = array(
      'taxonomy_term_data' => array(
        // links directly to taxonomy_term_data via tid
        'left_field' => 'tid',
        'field' => 'tid',
      ),
      'node' => array(
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
      'title' => t('Content with term'),
      'help' => t('Relate all content tagged with a term.'),
      'relationship' => array(
        'id' => 'standard',
        'base' => 'node',
        'base field' => 'nid',
        'label' => t('node'),
        'skip base' => 'node',
      ),
    );

    // @todo This stuff needs to move to a node field since really it's all about
    //   nodes.
    $data['taxonomy_index']['tid'] = array(
      'group' => t('Content'),
      'title' => t('Has taxonomy term ID'),
      'help' => t('Display content if it has the selected taxonomy terms.'),
      'argument' => array(
        'id' => 'taxonomy_index_tid',
        'name table' => 'taxonomy_term_field_data',
        'name field' => 'name',
        'empty field name' => t('Uncategorized'),
        'numeric' => TRUE,
        'skip base' => 'taxonomy_term_data',
      ),
      'filter' => array(
        'title' => t('Has taxonomy term'),
        'id' => 'taxonomy_index_tid',
        'hierarchy table' => 'taxonomy_term_hierarchy',
        'numeric' => TRUE,
        'skip base' => 'taxonomy_term_data',
        'allow empty' => TRUE,
      ),
    );


  $data['taxonomy_index']['sticky'] = [
    'title' => t('Sticky status'),
    'help' => t('Whether or not the content related to a term is sticky.'),
    'filter' => [
      'id' => 'boolean',
      'label' => t('Sticky status'),
      'type' => 'yes-no',
    ],
    'sort' => [
      'id' => 'standard',
      'help' => t('Whether or not the content related to a term is sticky. To list sticky content first, set this to descending.'),
    ],
  ];

  $data['taxonomy_index']['created'] = [
    'title' => t('Post date'),
    'help' => t('The date the content related to a term was posted.'),
    'sort' => [
      'id' => 'date'
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

    $data['taxonomy_term_hierarchy']['table']['group']  = t('Taxonomy term');

    $data['taxonomy_term_hierarchy']['table']['join'] = array(
      'taxonomy_term_hierarchy' => array(
        // Link to self through left.parent = right.tid (going down in depth).
        'left_field' => 'tid',
        'field' => 'parent',
      ),
      'taxonomy_term_data' => array(
        // Link directly to taxonomy_term_data via tid.
        'left_field' => 'tid',
        'field' => 'tid',
      ),
    );

    $data['taxonomy_term_hierarchy']['parent'] = array(
      'title' => t('Parent term'),
      'help' => t('The parent term of the term. This can produce duplicate entries if you are using a vocabulary that allows multiple parents.'),
      'relationship' => array(
        'base' => 'taxonomy_term_data',
        'field' => 'parent',
        'label' => t('Parent'),
        'id' => 'standard',
      ),
      'filter' => array(
        'help' => t('Filter the results of "Taxonomy: Term" by the parent pid.'),
        'id' => 'numeric',
      ),
      'argument' => array(
        'help' => t('The parent term of the term.'),
        'id' => 'taxonomy',
      ),
    );

    return $data;
  }

}

