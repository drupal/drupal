<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\field\TaxonomyIndexTid.
 */

namespace Drupal\taxonomy\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\PrerenderList;
use Drupal\Component\Utility\String;

/**
 * Field handler to display all taxonomy terms of a node.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("taxonomy_index_tid")
 */
class TaxonomyIndexTid extends PrerenderList {

  /**
   * Overrides \Drupal\views\Plugin\views\field\PrerenderList::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // @todo: Wouldn't it be possible to use $this->base_table and no if here?
    if ($view->storage->get('base_table') == 'node_field_revision') {
      $this->additional_fields['nid'] = array('table' => 'node_field_revision', 'field' => 'nid');
    }
    else {
      $this->additional_fields['nid'] = array('table' => 'node', 'field' => 'nid');
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_taxonomy'] = array('default' => TRUE, 'bool' => TRUE);
    $options['limit'] = array('default' => FALSE, 'bool' => TRUE);
    $options['vids'] = array('default' => array());

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
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    foreach ($vocabularies as $voc) {
      $options[$voc->id()] = $voc->label();
    }

    $form['vids'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $this->options['vids'],
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
    $this->addAdditionalFields();
  }

  public function preRender(&$values) {
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
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
      $query->fields('td');
      $query->addField('tn', 'nid', 'node_nid');
      $query->orderby('td.weight');
      $query->orderby('td.name');
      $query->condition('tn.nid', $nids);
      $query->addTag('term_access');
      $vocabs = array_filter($this->options['vids']);
      if (!empty($this->options['limit']) && !empty($vocabs)) {
        $query->condition('td.vid', $vocabs);
      }
      $result = $query->execute();

      foreach ($result as $term_record) {
        $this->items[$term_record->node_nid][$term_record->tid]['name'] = String::checkPlain($term_record->name);
        $this->items[$term_record->node_nid][$term_record->tid]['tid'] = $term_record->tid;
        $this->items[$term_record->node_nid][$term_record->tid]['vocabulary_vid'] = $term_record->vid;
        $this->items[$term_record->node_nid][$term_record->tid]['vocabulary'] = String::checkPlain($vocabularies[$term_record->vid]->label());

        if (!empty($this->options['link_to_taxonomy'])) {
          $this->items[$term_record->node_nid][$term_record->tid]['make_link'] = TRUE;
          $this->items[$term_record->node_nid][$term_record->tid]['path'] = 'taxonomy/term/' . $term_record->tid;
        }
      }
    }
  }

  function render_item($count, $item) {
    return $item['name'];
  }

  protected function documentSelfTokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-tid' . ']'] = t('The taxonomy term ID for the term.');
    $tokens['[' . $this->options['id'] . '-name' . ']'] = t('The taxonomy term name for the term.');
    $tokens['[' . $this->options['id'] . '-vocabulary-vid' . ']'] = t('The machine name for the vocabulary the term belongs to.');
    $tokens['[' . $this->options['id'] . '-vocabulary' . ']'] = t('The name for the vocabulary the term belongs to.');
  }

  protected function addSelfTokens(&$tokens, $item) {
    foreach (array('tid', 'name', 'vocabulary_vid', 'vocabulary') as $token) {
      // Replace _ with - for the vocabulary vid.
      $tokens['[' . $this->options['id'] . '-' . str_replace('_', '-', $token) . ']'] = isset($item[$token]) ? $item[$token] : '';
    }
  }

}
