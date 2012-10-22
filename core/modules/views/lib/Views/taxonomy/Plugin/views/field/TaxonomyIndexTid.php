<?php

/**
 * @file
 * Definition of Views\taxonomy\Plugin\views\field\TaxonomyIndexTid.
 */

namespace Views\taxonomy\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to display all taxonomy terms of a node.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "taxonomy_index_tid",
 *   module = "taxonomy"
 * )
 */
class TaxonomyIndexTid extends PrerenderList {

  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);
    // @todo: Wouldn't it be possible to use $this->base_table and no if here?
    if ($view->storage->base_table == 'node_revision') {
      $this->additional_fields['nid'] = array('table' => 'node_revision', 'field' => 'nid');
    }
    else {
      $this->additional_fields['nid'] = array('table' => 'node', 'field' => 'nid');
    }

    // Convert legacy vids option to machine name vocabularies.
    if (!empty($this->options['vids'])) {
      $vocabularies = taxonomy_vocabulary_get_names();
      foreach ($this->options['vids'] as $vid) {
        if (isset($vocabularies[$vid], $vocabularies[$vid]->machine_name)) {
          $this->options['vocabularies'][$vocabularies[$vid]->machine_name] = $vocabularies[$vid]->machine_name;
        }
      }
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_taxonomy'] = array('default' => TRUE, 'bool' => TRUE);
    $options['limit'] = array('default' => FALSE, 'bool' => TRUE);
    $options['vocabularies'] = array('default' => array());

    return $options;
  }

  /**
   * Provide "link to term" option.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_taxonomy'] = array(
      '#title' => t('Link this field to its term page'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_taxonomy']),
    );

    $form['limit'] = array(
      '#type' => 'checkbox',
      '#title' => t('Limit terms by vocabulary'),
      '#default_value' => $this->options['limit'],
    );

    $options = array();
    $vocabularies = taxonomy_vocabulary_get_names();
    foreach ($vocabularies as $voc) {
      $options[$voc->machine_name] = check_plain($voc->name);
    }

    $form['vocabularies'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $this->options['vocabularies'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[limit]"]' => array('checked' => TRUE),
        ),
      ),

    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Add this term to the query
   */
  public function query() {
    $this->add_additional_fields();
  }

  function pre_render(&$values) {
    $this->field_alias = $this->aliases['nid'];
    $nids = array();
    foreach ($values as $result) {
      if (!empty($result->{$this->aliases['nid']})) {
        $nids[] = $result->{$this->aliases['nid']};
      }
    }

    if ($nids) {
      $query = db_select('taxonomy_term_data', 'td');
      $query->innerJoin('taxonomy_index', 'tn', 'td.tid = tn.tid');
      $query->innerJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');
      $query->fields('td');
      $query->addField('tn', 'nid', 'node_nid');
      $query->addField('tv', 'name', 'vocabulary');
      $query->addField('tv', 'machine_name', 'vocabulary_machine_name');
      $query->orderby('td.weight');
      $query->orderby('td.name');
      $query->condition('tn.nid', $nids);
      $query->addTag('term_access');
      $vocabs = array_filter($this->options['vocabularies']);
      if (!empty($this->options['limit']) && !empty($vocabs)) {
        $query->condition('tv.machine_name', $vocabs);
      }
      $result = $query->execute();

      foreach ($result as $term) {
        $this->items[$term->node_nid][$term->tid]['name'] = check_plain($term->name);
        $this->items[$term->node_nid][$term->tid]['tid'] = $term->tid;
        $this->items[$term->node_nid][$term->tid]['vocabulary_machine_name'] = check_plain($term->vocabulary_machine_name);
        $this->items[$term->node_nid][$term->tid]['vocabulary'] = check_plain($term->vocabulary);

        if (!empty($this->options['link_to_taxonomy'])) {
          $this->items[$term->node_nid][$term->tid]['make_link'] = TRUE;
          $this->items[$term->node_nid][$term->tid]['path'] = 'taxonomy/term/' . $term->tid;
        }
      }
    }
  }

  function render_item($count, $item) {
    return $item['name'];
  }

  function document_self_tokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-tid' . ']'] = t('The taxonomy term ID for the term.');
    $tokens['[' . $this->options['id'] . '-name' . ']'] = t('The taxonomy term name for the term.');
    $tokens['[' . $this->options['id'] . '-vocabulary-machine-name' . ']'] = t('The machine name for the vocabulary the term belongs to.');
    $tokens['[' . $this->options['id'] . '-vocabulary' . ']'] = t('The name for the vocabulary the term belongs to.');
  }

  function add_self_tokens(&$tokens, $item) {
    foreach (array('tid', 'name', 'vocabulary_machine_name', 'vocabulary') as $token) {
      // Replace _ with - for the vocabulary machine name.
      $tokens['[' . $this->options['id'] . '-' . str_replace('_', '-', $token) . ']'] = isset($item[$token]) ? $item[$token] : '';
    }
  }

}
