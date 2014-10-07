<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\field\TaxonomyIndexTid.
 */

namespace Drupal\taxonomy\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\PrerenderList;
use Drupal\Component\Utility\String;

/**
 * Field handler to display all taxonomy terms of a node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("taxonomy_index_tid")
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

    $options['link_to_taxonomy'] = array('default' => TRUE);
    $options['limit'] = array('default' => FALSE);
    $options['vids'] = array('default' => array());

    return $options;
  }

  /**
   * Provide "link to term" option.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_taxonomy'] = array(
      '#title' => $this->t('Link this field to its term page'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_taxonomy']),
    );

    $form['limit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Limit terms by vocabulary'),
      '#default_value' => $this->options['limit'],
    );

    $options = array();
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    foreach ($vocabularies as $voc) {
      $options[$voc->id()] = $voc->label();
    }

    $form['vids'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabularies'),
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
      $vocabs = array_filter($this->options['vids']);
      if (empty($this->options['limit'])) {
        $vocabs = array();
      }
      $result = \Drupal::entityManager()->getStorage('taxonomy_term')->getNodeTerms($nids, $vocabs);

      foreach ($result as $node_nid => $data) {
        foreach ($data as $tid => $term) {
          $this->items[$node_nid][$tid]['name'] = \Drupal::entityManager()->getTranslationFromContext($term)->label();
          $this->items[$node_nid][$tid]['tid'] = $tid;
          $this->items[$node_nid][$tid]['vocabulary_vid'] = $term->getVocabularyId();
          $this->items[$node_nid][$tid]['vocabulary'] = String::checkPlain($vocabularies[$term->getVocabularyId()]->label());

          if (!empty($this->options['link_to_taxonomy'])) {
            $this->items[$node_nid][$tid]['make_link'] = TRUE;
            $this->items[$node_nid][$tid]['path'] = 'taxonomy/term/' . $tid;
          }
        }
      }
    }
  }

  function render_item($count, $item) {
    return $item['name'];
  }

  protected function documentSelfTokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-tid' . ']'] = $this->t('The taxonomy term ID for the term.');
    $tokens['[' . $this->options['id'] . '-name' . ']'] = $this->t('The taxonomy term name for the term.');
    $tokens['[' . $this->options['id'] . '-vocabulary-vid' . ']'] = $this->t('The machine name for the vocabulary the term belongs to.');
    $tokens['[' . $this->options['id'] . '-vocabulary' . ']'] = $this->t('The name for the vocabulary the term belongs to.');
  }

  protected function addSelfTokens(&$tokens, $item) {
    foreach (array('tid', 'name', 'vocabulary_vid', 'vocabulary') as $token) {
      // Replace _ with - for the vocabulary vid.
      $tokens['[' . $this->options['id'] . '-' . str_replace('_', '-', $token) . ']'] = isset($item[$token]) ? $item[$token] : '';
    }
  }

}
