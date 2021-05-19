<?php

namespace Drupal\book\Form;

use Drupal\book\BookManager;
use Drupal\book\BookManagerInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for administering a single book's hierarchy.
 *
 * @internal
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
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new BookAdminEditForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The custom block storage.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface|null $entity_repository
   *   The entity repository service.
   */
  public function __construct(EntityStorageInterface $node_storage, BookManagerInterface $book_manager, EntityRepositoryInterface $entity_repository = NULL) {
    $this->nodeStorage = $node_storage;
    $this->bookManager = $book_manager;
    if (!$entity_repository) {
      @trigger_error('The entity.repository service must be passed to ' . __NAMESPACE__ . '\BookAdminEditForm::__construct(). It was added in drupal:9.2.0 and will be required before drupal:10.0.0.', E_USER_DEPRECATED);
      $entity_repository = \Drupal::service('entity.repository');
    }
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('node'),
      $container->get('book.manager'),
      $container->get('entity.repository')
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
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save book pages'),
    ];

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
          $values = $form_state->getValue(['table', $key]);

          // Update menu item if moved.
          if ($row['parent']['pid']['#default_value'] != $values['pid'] || $row['weight']['#default_value'] != $values['weight']) {
            $link = $this->bookManager->loadBookLink($values['nid'], FALSE);
            $link['weight'] = $values['weight'];
            $link['pid'] = $values['pid'];
            $this->bookManager->saveBookLink($link, FALSE);
          }

          // Update the title if changed.
          if ($row['title']['#default_value'] != $values['title']) {
            $node = $this->nodeStorage->load($values['nid']);
            $node = $this->entityRepository->getTranslationFromContext($node);
            $node->revision_log = $this->t('Title changed from %original to %current.', ['%original' => $node->label(), '%current' => $values['title']]);
            $node->title = $values['title'];
            $node->book['link_title'] = $values['title'];
            $node->setNewRevision();
            $node->save();
            $this->logger('content')->notice('book: updated %title.', ['%title' => $node->label(), 'link' => $node->toLink($this->t('View'))->toString()]);
          }
        }
      }
    }

    $this->messenger()->addStatus($this->t('Updated book %title.', ['%title' => $form['#node']->label()]));
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
    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Weight'),
        $this->t('Parent'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No book content available.'),
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'book-pid',
          'subgroup' => 'book-pid',
          'source' => 'book-nid',
          'hidden' => TRUE,
          'limit' => BookManager::BOOK_MAX_DEPTH - 2,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'book-weight',
        ],
      ],
    ];

    $tree = $this->bookManager->bookSubtreeData($node->book);
    // Do not include the book item itself.
    $tree = array_shift($tree);
    if ($tree['below']) {
      $hash = Crypt::hashBase64(serialize($tree['below']));
      // Store the hash value as a hidden form element so that we can detect
      // if another user changed the book hierarchy.
      $form['tree_hash'] = [
        '#type' => 'hidden',
        '#default_value' => $hash,
      ];
      $form['tree_current_hash'] = [
        '#type' => 'value',
        '#value' => $hash,
      ];
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

    $access = \Drupal::currentUser()->hasPermission('administer nodes');
    $destination = $this->getDestinationArray();

    foreach ($tree as $data) {
      $nid = $data['link']['nid'];
      $id = 'book-admin-' . $nid;

      $form[$id]['#item'] = $data['link'];
      $form[$id]['#nid'] = $nid;
      $form[$id]['#attributes']['class'][] = 'draggable';
      $form[$id]['#weight'] = $data['link']['weight'];

      if (isset($data['link']['depth']) && $data['link']['depth'] > 2) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $data['link']['depth'] - 2,
        ];
      }

      $form[$id]['title'] = [
        '#prefix' => !empty($indentation) ? \Drupal::service('renderer')->render($indentation) : '',
        '#type' => 'textfield',
        '#default_value' => $data['link']['title'],
        '#maxlength' => 255,
        '#size' => 40,
      ];

      $form[$id]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $data['link']['weight'],
        '#delta' => max($delta, abs($data['link']['weight'])),
        '#title' => $this->t('Weight for @title', ['@title' => $data['link']['title']]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['book-weight'],
        ],
      ];

      $form[$id]['parent']['nid'] = [
        '#parents' => ['table', $id, 'nid'],
        '#type' => 'hidden',
        '#value' => $nid,
        '#attributes' => [
          'class' => ['book-nid'],
        ],
      ];

      $form[$id]['parent']['pid'] = [
        '#parents' => ['table', $id, 'pid'],
        '#type' => 'hidden',
        '#default_value' => $data['link']['pid'],
        '#attributes' => [
          'class' => ['book-pid'],
        ],
      ];

      $form[$id]['parent']['bid'] = [
        '#parents' => ['table', $id, 'bid'],
        '#type' => 'hidden',
        '#default_value' => $data['link']['bid'],
        '#attributes' => [
          'class' => ['book-bid'],
        ],
      ];

      $form[$id]['operations'] = [
        '#type' => 'operations',
      ];
      $form[$id]['operations']['#links']['view'] = [
        'title' => $this->t('View'),
        'url' => new Url('entity.node.canonical', ['node' => $nid]),
      ];

      if ($access) {
        $form[$id]['operations']['#links']['edit'] = [
          'title' => $this->t('Edit'),
          'url' => new Url('entity.node.edit_form', ['node' => $nid]),
          'query' => $destination,
        ];
        $form[$id]['operations']['#links']['delete'] = [
          'title' => $this->t('Delete'),
          'url' => new Url('entity.node.delete_form', ['node' => $nid]),
          'query' => $destination,
        ];
      }

      if ($data['below']) {
        $this->bookAdminTableTree($data['below'], $form);
      }
    }
  }

}
