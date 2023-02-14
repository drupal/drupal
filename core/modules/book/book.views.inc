<?php

/**
 * @file
 * Provide views data for book.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function book_views_data() {
  $data = [];
  $data['book'] = [];
  $data['book']['table'] = [];
  $data['book']['table']['group'] = t('Book');

  $data['book']['table']['join'] = [
    'node_field_data' => [
      'left_field' => 'nid',
      'field' => 'nid',
    ],
  ];

  $data['book']['nid'] = [
    'title' => t('Page'),
    'help' => t('The book page node.'),
    'relationship' => [
      'base' => 'node_field_data',
      'id' => 'standard',
      'label' => t('Book Page'),
    ],
  ];

  $data['book']['bid'] = [
    'title' => t('Top level book'),
    'help' => t('The book the node is in.'),
    'relationship' => [
      'base' => 'node_field_data',
      'id' => 'standard',
      'label' => t('Book'),
    ],
  ];

  $data['book']['pid'] = [
    'title' => t('Parent'),
    'help' => t('The parent book node.'),
    'relationship' => [
      'base' => 'node_field_data',
      'id' => 'standard',
      'label' => t('Book Parent'),
    ],
  ];

  $data['book']['has_children'] = [
    'title' => t('Page has Children'),
    'help' => t('Flag indicating whether this book page has children'),
    'field' => [
      'id' => 'boolean',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Has Children'),
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  $data['book']['weight'] = [
    'title' => t('Weight'),
    'help' => t('The weight of the book page.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['book']['depth'] = [
    'title' => t('Depth'),
    'help' => t('The depth of the book page in the hierarchy; top level book pages have a depth of 1.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'standard',
    ],
  ];
  $parents = [
    1 => t('1st parent'),
    2 => t('2nd parent'),
    3 => t('3rd parent'),
    4 => t('4th parent'),
    5 => t('5th parent'),
    6 => t('6th parent'),
    7 => t('7th parent'),
    8 => t('8th parent'),
    9 => t('9th parent'),
  ];
  foreach ($parents as $i => $parent_label) {
    $data['book']["p$i"] = [
      'title' => $parent_label,
      'help' => t('The @parent of book node.', ['@parent' => $parent_label]),
      'relationship' => [
        'base' => 'node_field_data',
        'id' => 'standard',
        'label' => t('Book @parent', ['@parent' => $parent_label]),
      ],
    ];
  }

  return $data;
}
