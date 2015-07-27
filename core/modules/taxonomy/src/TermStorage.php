<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermStorage.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermStorage extends SqlContentEntityStorage implements TermStorageInterface {

  /**
   * Array of loaded parents keyed by child term ID.
   *
   * @var array
   */
  protected $parents = array();

  /**
   * Array of all loaded term ancestry keyed by ancestor term ID.
   *
   * @var array
   */
  protected $parentsAll = array();

  /**
   * Array of child terms keyed by parent term ID.
   *
   * @var array
   */
  protected $children = array();

  /**
   * Array of term parents keyed by vocabulary ID and child term ID.
   *
   * @var array
   */
  protected $treeParents = array();

  /**
   * Array of term ancestors keyed by vocabulary ID and parent term ID.
   *
   * @var array
   */
  protected $treeChildren = array();

  /**
   * Array of terms in a tree keyed by vocabulary ID and term ID.
   *
   * @var array
   */
  protected $treeTerms = array();

  /**
   * Array of loaded trees keyed by a cache id matching tree arguments.
   *
   * @var array
   */
  protected $trees = array();

  /**
   * {@inheritdoc}
   *
   * @param array $values
   *   An array of values to set, keyed by property name. A value for the
   *   vocabulary ID ('vid') is required.
   */
  public function create(array $values = array()) {
    // Save new terms with no parents by default.
    if (empty($values['parent'])) {
      $values['parent'] = array(0);
    }
    $entity = parent::create($values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_term_count_nodes');
    $this->parents = array();
    $this->parentsAll = array();
    $this->children = array();
    $this->treeChildren = array();
    $this->treeParents = array();
    $this->treeTerms = array();
    $this->trees = array();
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTermHierarchy($tids) {
    $this->database->delete('taxonomy_term_hierarchy')
      ->condition('tid', $tids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateTermHierarchy(EntityInterface $term) {
    $query = $this->database->insert('taxonomy_term_hierarchy')
      ->fields(array('tid', 'parent'));

    foreach ($term->parent as $parent) {
      $query->values(array(
        'tid' => $term->id(),
        'parent' => (int) $parent->target_id,
      ));
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadParents($tid) {
    if (!isset($this->parents[$tid])) {
      $parents = array();
      $query = $this->database->select('taxonomy_term_field_data', 't');
      $query->join('taxonomy_term_hierarchy', 'h', 'h.parent = t.tid');
      $query->addField('t', 'tid');
      $query->condition('h.tid', $tid);
      $query->condition('t.default_langcode', 1);
      $query->addTag('term_access');
      $query->orderBy('t.weight');
      $query->orderBy('t.name');
      if ($ids = $query->execute()->fetchCol()) {
        $parents = $this->loadMultiple($ids);
      }
      $this->parents[$tid] = $parents;
    }
    return $this->parents[$tid];
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllParents($tid) {
    if (!isset($this->parentsAll[$tid])) {
      $parents = array();
      if ($term = $this->load($tid)) {
        $parents[$term->id()] = $term;
        $terms_to_search[] = $term->id();

        while ($tid = array_shift($terms_to_search)) {
          if ($new_parents = $this->loadParents($tid)) {
            foreach ($new_parents as $new_parent) {
              if (!isset($parents[$new_parent->id()])) {
                $parents[$new_parent->id()] = $new_parent;
                $terms_to_search[] = $new_parent->id();
              }
            }
          }
        }
      }

      $this->parentsAll[$tid] = $parents;
    }
    return $this->parentsAll[$tid];
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren($tid, $vid = NULL) {
    if (!isset($this->children[$tid])) {
      $children = array();
      $query = $this->database->select('taxonomy_term_field_data', 't');
      $query->join('taxonomy_term_hierarchy', 'h', 'h.tid = t.tid');
      $query->addField('t', 'tid');
      $query->condition('h.parent', $tid);
      if ($vid) {
        $query->condition('t.vid', $vid);
      }
      $query->condition('t.default_langcode', 1);
      $query->addTag('term_access');
      $query->orderBy('t.weight');
      $query->orderBy('t.name');
      if ($ids = $query->execute()->fetchCol()) {
        $children = $this->loadMultiple($ids);
      }
      $this->children[$tid] = $children;
    }
    return $this->children[$tid];
  }

  /**
   * {@inheritdoc}
   */
  public function loadTree($vid, $parent = 0, $max_depth = NULL, $load_entities = FALSE) {
    $cache_key = implode(':', func_get_args());
    if (!isset($this->trees[$cache_key])) {
      // We cache trees, so it's not CPU-intensive to call on a term and its
      // children, too.
      if (!isset($this->treeChildren[$vid])) {
        $this->treeChildren[$vid] = array();
        $this->treeParents[$vid] = array();
        $this->treeTerms[$vid] = array();
        $query = $this->database->select('taxonomy_term_field_data', 't');
        $query->join('taxonomy_term_hierarchy', 'h', 'h.tid = t.tid');
        $result = $query
          ->addTag('term_access')
          ->fields('t')
          ->fields('h', array('parent'))
          ->condition('t.vid', $vid)
          ->condition('t.default_langcode', 1)
          ->orderBy('t.weight')
          ->orderBy('t.name')
          ->execute();
        foreach ($result as $term) {
          $this->treeChildren[$vid][$term->parent][] = $term->tid;
          $this->treeParents[$vid][$term->tid][] = $term->parent;
          $this->treeTerms[$vid][$term->tid] = $term;
        }
      }

      // Load full entities, if necessary. The entity controller statically
      // caches the results.
      $term_entities = array();
      if ($load_entities) {
        $term_entities = $this->loadMultiple(array_keys($this->treeTerms[$vid]));
      }

      $max_depth = (!isset($max_depth)) ? count($this->treeChildren[$vid]) : $max_depth;
      $tree = array();

      // Keeps track of the parents we have to process, the last entry is used
      // for the next processing step.
      $process_parents = array();
      $process_parents[] = $parent;

      // Loops over the parent terms and adds its children to the tree array.
      // Uses a loop instead of a recursion, because it's more efficient.
      while (count($process_parents)) {
        $parent = array_pop($process_parents);
        // The number of parents determines the current depth.
        $depth = count($process_parents);
        if ($max_depth > $depth && !empty($this->treeChildren[$vid][$parent])) {
          $has_children = FALSE;
          $child = current($this->treeChildren[$vid][$parent]);
          do {
            if (empty($child)) {
              break;
            }
            $term = $load_entities ? $term_entities[$child] : $this->treeTerms[$vid][$child];
            if (isset($this->treeParents[$vid][$load_entities ? $term->id() : $term->tid])) {
              // Clone the term so that the depth attribute remains correct
              // in the event of multiple parents.
              $term = clone $term;
            }
            $term->depth = $depth;
            unset($term->parent);
            $tid = $load_entities ? $term->id() : $term->tid;
            $term->parents = $this->treeParents[$vid][$tid];
            $tree[] = $term;
            if (!empty($this->treeChildren[$vid][$tid])) {
              $has_children = TRUE;

              // We have to continue with this parent later.
              $process_parents[] = $parent;
              // Use the current term as parent for the next iteration.
              $process_parents[] = $tid;

              // Reset pointers for child lists because we step in there more
              // often with multi parents.
              reset($this->treeChildren[$vid][$tid]);
              // Move pointer so that we get the correct term the next time.
              next($this->treeChildren[$vid][$parent]);
              break;
            }
          } while ($child = next($this->treeChildren[$vid][$parent]));

          if (!$has_children) {
            // We processed all terms in this hierarchy-level, reset pointer
            // so that this function works the next time it gets called.
            reset($this->treeChildren[$vid][$parent]);
          }
        }
      }
      $this->trees[$cache_key] = $tree;
    }
    return $this->trees[$cache_key];
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCount($vid) {
    $query = $this->database->select('taxonomy_index', 'ti');
    $query->addExpression('COUNT(DISTINCT ti.nid)');
    $query->leftJoin('taxonomy_term_data', 'td', 'ti.tid = td.tid');
    $query->condition('td.vid', $vid);
    $query->addTag('vocabulary_node_count');
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function resetWeights($vid) {
    $this->database->update('taxonomy_term_field_data')
      ->fields(array('weight' => 0))
      ->condition('vid', $vid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeTerms(array $nids, array $vocabs = array(), $langcode = NULL) {
    $query = db_select('taxonomy_term_field_data', 'td');
    $query->innerJoin('taxonomy_index', 'tn', 'td.tid = tn.tid');
    $query->fields('td', array('tid'));
    $query->addField('tn', 'nid', 'node_nid');
    $query->orderby('td.weight');
    $query->orderby('td.name');
    $query->condition('tn.nid', $nids, 'IN');
    $query->addTag('term_access');
    if (!empty($vocabs)) {
      $query->condition('td.vid', $vocabs, 'IN');
    }
    if (!empty($langcode)) {
      $query->condition('td.langcode', $langcode);
    }

    $results = array();
    $all_tids = array();
    foreach ($query->execute() as $term_record) {
      $results[$term_record->node_nid][] = $term_record->tid;
      $all_tids[] = $term_record->tid;
    }

    $all_terms = $this->loadMultiple($all_tids);
    $terms = array();
    foreach ($results as $nid => $tids) {
      foreach ($tids as $tid) {
        $terms[$nid][$tid] = $all_terms[$tid];
      }
    }
    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = parent::__sleep();
    // Do not serialize static cache.
    unset($vars['parents'], $vars['parentsAll'], $vars['children'], $vars['treeChildren'], $vars['treeParents'], $vars['treeTerms'], $vars['trees']);
    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    parent::__wakeup();
    // Initialize static caches.
    $this->parents = array();
    $this->parentsAll = array();
    $this->children = array();
    $this->treeChildren = array();
    $this->treeParents = array();
    $this->treeTerms = array();
    $this->trees = array();
  }

}
