<?php

/**
 * @file
 * Contains \Drupal\book\Form\BookAdminEditForm.
 */

namespace Drupal\book\Form;

use Drupal\book\BookManagerInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for administering a single book's hierarchy.
 */
class BookAdminEditForm extends FormBase {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Constructs a new BookAdminEditForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The custom block storage.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   */
  public function __construct(EntityStorageInterface $node_storage, BookManagerInterface $book_manager) {
    $this->nodeStorage = $node_storage;
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('node'),
      $container->get('book.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'book_admin_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form['#title'] = $node->label();
    $form['#node'] = $node;
    $this->bookAdminTable($node, $form);
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save book pages'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('tree_hash') != $form_state->getValue('tree_current_hash')) {
      $form_state->setErrorByName('', $this->t('This book has been modified by another user, the changes could not be saved.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save elements in the same order as defined in post rather than the form.
    // This ensures parents are updated before their children, preventing orphans.
    $user_input = $form_state->getUserInput();
    if (isset($user_input['table'])) {
      $order = array_flip(array_keys($user_input['table']));
      $form['table'] = array_merge($order, $form['table']);

      foreach (Element::children($form['table']) as $key) {
        if ($form['table'][$key]['#item']) {
          $row = $form['table'][$key];
          $values = $form_state->getValue(array('table', $key));

          // Update menu item if moved.
          if ($row['pid']['#default_value'] != $values['pid'] || $row['weight']['#default_value'] != $values['weight']) {
            $link = $this->bookManager->loadBookLink($values['nid'], FALSE);
            $link['weight'] = $values['weight'];
            $link['pid'] = $values['pid'];
            $this->bookManager->saveBookLink($link, FALSE);
          }

          // Update the title if changed.
          if ($row['title']['#default_value'] != $values['title']) {
            $node = $this->nodeStorage->load($values['nid']);
            $node->revision_log = $this->t('Title changed from %original to %current.', array('%original' => $node->label(), '%current' => $values['title']));
            $node->title = $values['title'];
            $node->book['link_title'] = $values['title'];
            $node->setNewRevision();
            $node->save();
            $this->logger('content')->notice('book: updated %title.', array('%title' => $node->label(), 'link' => $node->link($this->t('View'))));
          }
        }
      }
    }

    drupal_set_message($this->t('Updated book %title.', array('%title' => $form['#node']->label())));
  }

  /**
   * Builds the table portion of the form for the book administration page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node of the top-level page in the book.
   * @param array $form
   *   The form that is being modified, passed by reference.
   *
   * @see self::buildForm()
   */
  protected function bookAdminTable(NodeInterface $node, array &$form) {
    $form['table'] = array(
      '#theme' => 'book_admin_table',
      '#tree' => TRUE,
    );

    $tree = $this->bookManager->bookSubtreeData($node->book);
    // Do not include the book item itself.
    $tree = array_shift($tree);
    if ($tree['below']) {
      $hash = Crypt::hashBase64(serialize($tree['below']));
      // Store the hash value as a hidden form element so that we can detect
      // if another user changed the book hierarchy.
      $form['tree_hash'] = array(
        '#type' => 'hidden',
        '#default_value' => $hash,
      );
      $form['tree_current_hash'] = array(
        '#type' => 'value',
        '#value' => $hash,
      );
      $this->bookAdminTableTree($tree['below'], $form['table']);
    }
  }

  /**
   * Helps build the main table in the book administration page form.
   *
   * @param array $tree
   *   A subtree of the book menu hierarchy.
   * @param array $form
   *   The form that is being modified, passed by reference.
   *
   * @see self::buildForm()
   */
  protected function bookAdminTableTree(array $tree, array &$form) {
    // The delta must be big enough to give each node a distinct value.
    $count = count($tree);
    $delta = ($count < 30) ? 15 : intval($count / 2) + 1;

    foreach ($tree as $data) {
      $form['book-admin-' . $data['link']['nid']] = array(
        '#item' => $data['link'],
        'depth' => array('#type' => 'value', '#value' => $data['link']['depth']),
        'title' => array(
          '#type' => 'textfield',
          '#default_value' => $data['link']['title'],
          '#maxlength' => 255,
          '#size' => 40,
        ),
        'weight' => array(
          '#type' => 'weight',
          '#default_value' => $data['link']['weight'],
          '#delta' => max($delta, abs($data['link']['weight'])),
          '#title' => $this->t('Weight for @title', array('@title' => $data['link']['title'])),
          '#title_display' => 'invisible',
        ),
        'pid' => array(
          '#type' => 'hidden',
          '#default_value' => $data['link']['pid'],
        ),
        'nid' => array(
          '#type' => 'hidden',
          '#default_value' => $data['link']['nid'],
        ),
      );
      if ($data['below']) {
        $this->bookAdminTableTree($data['below'], $form);
      }
    }
  }

}
