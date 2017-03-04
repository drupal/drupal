<?php

/**
 * @file
 * Provide views data for forum.module.
 */

/**
 * Implements hook_views_data().
 */
function forum_views_data() {

  $data['forum_index']['table']['group'] = t('Forum');
  $data['forum_index']['table']['base'] = [
    'field' => 'nid',
    'title' => t('Forum content'),
    'access query tag' => 'node_access',
  ];

  $data['forum_index']['nid'] = [
    'title' => t('Nid'),
    'help' => t('The content ID of the forum index entry.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'relationship' => [
      'base' => 'node',
      'base field' => 'nid',
      'label' => t('Node'),
    ],
  ];

  $data['forum_index']['title'] = [
    'title' => t('Title'),
    'help' => t('The content title.'),
    'field' => [
      'id' => 'standard',
      'link_to_node default' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['forum_index']['tid'] = [
    'title' => t('Has taxonomy term ID'),
    'help' => t('Display content if it has the selected taxonomy terms.'),
    'argument' => [
      'id' => 'taxonomy_index_tid',
      'name table' => 'taxonomy_term_data',
      'name field' => 'name',
      'empty field name' => t('Uncategorized'),
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_data',
    ],
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'title' => t('Has taxonomy term'),
      'id' => 'taxonomy_index_tid',
      'hierarchy table' => 'taxonomy_term_hierarchy',
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_data',
      'allow empty' => TRUE,
    ],
    'relationship' => [
      'base' => 'taxonomy_term',
      'base field' => 'tid',
      'label' => t('Term'),
    ],
  ];


  $data['forum_index']['created'] = [
    'title' => t('Post date'),
    'help' => t('The date the content was posted.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date'
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['forum_index']['sticky'] = [
    'title' => t('Sticky'),
    'help' => t('Whether or not the content is sticky.'),
    'field' => [
      'id' => 'boolean',
      'click sortable' => TRUE,
      'output formats' => [
        'sticky' => [t('Sticky'), t('Not sticky')],
      ],
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Sticky'),
      'type' => 'yes-no',
    ],
    'sort' => [
      'id' => 'standard',
      'help' => t('Whether or not the content is sticky. To list sticky content first, set this to descending.'),
    ],
  ];

  $data['forum_index']['last_comment_timestamp'] = [
    'title' => t('Last comment time'),
    'help' => t('Date and time of when the last comment was posted.'),
    'field' => [
      'id' => 'comment_last_timestamp',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['forum_index']['comment_count'] = [
    'title' => t('Comment count'),
    'help' => t('The number of comments a node has.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}
