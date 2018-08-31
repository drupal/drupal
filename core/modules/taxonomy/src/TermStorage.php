<?php

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermStorage extends SqlContentEntityStorage implements TermStorageInterface {

  /**
   * Array of term parents keyed by vocabulary ID and child term ID.
   *
   * @var array
   */
  protected $treeParents = [];

  /**
   * Array of term ancestors keyed by vocabulary ID and parent term ID.
   *
   * @var array
   */
  protected $treeChildren = [];

  /**
   * Array of terms in a tree keyed by vocabulary ID and term ID.
   *
   * @var array
   */
  protected $treeTerms = [];

  /**
   * Array of loaded trees keyed by a cache id matching tree arguments.
   *
   * @var array
   */
  protected $trees = [];

  /**
   * Array of all loaded term ancestry keyed by ancestor term ID, keyed by term
   * ID.
   *
   * @var \Drupal\taxonomy\TermInterface[][]
   */
  protected $ancestors;

  /**
   * {@inheritdoc}
   *
   * @param array $values
   *   An array of values to set, keyed by property name. A value for the
   *   vocabulary ID ('vid') is required.
   */
  public function create(array $values = []) {
    // Save new terms with no parents by default.
    if (empty($values['parent'])) {
      $values['parent'] = [0];
    }
    $entity = parent::create($values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_term_count_nodes');
    $this->ancestors = [];
    $this->treeChildren = [];
    $this->treeParents = [];
    $this->treeTerms = [];
    $this->trees = [];
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTermHierarchy($tids) {}

  /**
   * {@inheritdoc}
   */
  public function updateTermHierarchy(EntityInterface $term) {}

  /**
   * {@inheritdoc}
   */
  public function loadParents($tid) {
    $terms = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    if ($tid && $term = $this->load($tid)) {
      foreach ($this->getParents($term) as $id => $parent) {
        // This method currently doesn't return the <root> parent.
        // @see https://www.drupal.org/node/2019905
        if (!empty($id)) {
          $terms[$id] = $parent;
        }
      }
    }

    return $terms;
  }

  /**
   * Returns a list of parents of this term.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The parent taxonomy term entities keyed by term ID. If this term has a
   *   <root> parent, that item is keyed with 0 and will have NULL as value.
   *
   * @internal
   * @todo Refactor away when TreeInterface is introduced.
   */
  protected function getParents(TermInterface $term) {
    $parents = $ids = [];
    // Cannot use $this->get('parent')->referencedEntities() here because that
    // strips out the '0' reference.
    foreach ($term->get('parent') as $item) {
      if ($item->target_id == 0) {
        // The <root> parent.
        $parents[0] = NULL;
        continue;
      }
      $ids[] = $item->target_id;
    }

    // @todo Better way to do this? AND handle the NULL/0 parent?
    // Querying the terms again so that the same access checks are run when
    // getParents() is called as in Drupal version prior to 8.3.
    $loaded_parents = [];

    if ($ids) {
      $query = \Drupal::entityQuery('taxonomy_term')
        ->condition('tid', $ids, 'IN');

      $loaded_parents = static::loadMultiple($query->execute());
    }

    return $parents + $loaded_parents;
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllParents($tid) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    return (!empty($tid) && $term = $this->load($tid)) ? $this->getAncestors($term) : [];
  }

  /**
   * Returns all ancestors of this term.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   A list of ancestor taxonomy term entities keyed by term ID.
   *
   * @internal
   * @todo Refactor away when TreeInterface is introduced.
   */
  protected function getAncestors(TermInterface $term) {
    if (!isset($this->ancestors[$term->id()])) {
      $this->ancestors[$term->id()] = [$term->id() => $term];
      $search[] = $term->id();

      while ($tid = array_shift($search)) {
        foreach ($this->getParents(static::load($tid)) as $id => $parent) {
          if ($parent && !isset($this->ancestors[$term->id()][$id])) {
            $this->ancestors[$term->id()][$id] = $parent;
            $search[] = $id;
          }
        }
      }
    }
    return $this->ancestors[$term->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren($tid, $vid = NULL) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    return (!empty($tid) && $term = $this->load($tid)) ? $this->getChildren($term) : [];
  }

  /**
   * Returns all children terms of this term.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   A list of children taxonomy term entities keyed by term ID.
   *
   * @internal
   * @todo Refactor away when TreeInterface is introduced.
   */
  public function getChildren(TermInterface $term) {
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('parent', $term->id());
    return static::loadMultiple($query->execute());
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
        $this->treeChildren[$vid] = [];
        $this->treeParents[$vid] = [];
        $this->treeTerms[$vid] = [];
        $query = $this->database->select($this->getDataTable(), 't');
        $query->join('taxonomy_term__parent', 'p', 't.tid = p.entity_id');
        $query->addExpression('parent_target_id', 'parent');
        $result = $query
          ->addTag('taxonomy_term_access')
          ->fields('t')
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
      $term_entities = [];
      if ($load_entities) {
        $term_entities = $this->loadMultiple(array_keys($this->treeTerms[$vid]));
      }

      $max_depth = (!isset($max_depth)) ? count($this->treeChildren[$vid]) : $max_depth;
      $tree = [];

      // Keeps track of the parents we have to process, the last entry is used
      // for the next processing step.
      $process_parents = [];
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
            if (!$load_entities) {
              unset($term->parent);
            }
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
    $query->leftJoin($this->getBaseTable(), 'td', 'ti.tid = td.tid');
    $query->condition('td.vid', $vid);
    $query->addTag('vocabulary_node_count');
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function resetWeights($vid) {
    $this->database->update($this->getDataTable())
      ->fields(['weight' => 0])
      ->condition('vid', $vid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeTerms(array $nids, array $vocabs = [], $langcode = NULL) {
    $query = db_select($this->getDataTable(), 'td');
    $query->innerJoin('taxonomy_index', 'tn', 'td.tid = tn.tid');
    $query->fields('td', ['tid']);
    $query->addField('tn', 'nid', 'node_nid');
    $query->orderby('td.weight');
    $query->orderby('td.name');
    $query->condition('tn.nid', $nids, 'IN');
    $query->addTag('taxonomy_term_access');
    if (!empty($vocabs)) {
      $query->condition('td.vid', $vocabs, 'IN');
    }
    if (!empty($langcode)) {
      $query->condition('td.langcode', $langcode);
    }

    $results = [];
    $all_tids = [];
    foreach ($query->execute() as $term_record) {
      $results[$term_record->node_nid][] = $term_record->tid;
      $all_tids[] = $term_record->tid;
    }

    $all_terms = $this->loadMultiple($all_tids);
    $terms = [];
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
    unset($vars['ancestors'], $vars['treeChildren'], $vars['treeParents'], $vars['treeTerms'], $vars['trees']);
    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    parent::__wakeup();
    // Initialize static caches.
    $this->ancestors = [];
    $this->treeChildren = [];
    $this->treeParents = [];
    $this->treeTerms = [];
    $this->trees = [];
  }

}
