<?php

/**
 * @file
 * Contains \Drupal\forum\Form\ForumFormController.
 */

namespace Drupal\forum\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\taxonomy\TermFormController;
use Drupal\taxonomy\TermStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base form controller for forum term edit forms.
 */
class ForumFormController extends TermFormController implements EntityControllerInterface {

  /**
   * Reusable type field to use in status messages.
   *
   * @var string
   */
  protected $forumFormType;

  /**
   * Reusable url stub to use in watchdog messages.
   *
   * @var string
   */
  protected $urlStub = 'forum';

  /**
   * The forum config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Term Storage Controller.
   *
   * @var \Drupal\taxonomy\TermStorageControllerInterface
   */
  protected $storageController;

  /**
   * Constructs a new MenuLinkFormController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\taxonomy\TermStorageControllerInterface $storage_controller
   *   The storage controller.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactory $config_factory, Request $request, TermStorageControllerInterface $storage_controller) {
    parent::__construct($module_handler);
    $this->config = $config_factory->get('forum.settings');
    $this->request = $request;
    $this->storageController = $storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('request'),
      $container->get('plugin.manager.entity')->getStorageController('taxonomy_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $taxonomy_term = $this->entity;
    // Build the bulk of the form from the parent taxonomy term form.
    $form = parent::form($form, $form_state, $taxonomy_term);

    // Set the title and description of the name field.
    $form['name']['#title'] = t('Forum name');
    $form['name']['#description'] = t('Short but meaningful name for this collection of threaded discussions.');

    // Change the description.
    $form['description']['#description'] = t('Description and guidelines for discussions within this forum.');

    // Re-use the weight field.
    $form['weight'] = $form['relations']['weight'];

    // Remove the remaining relations fields.
    unset($form['relations']);

    // Our parent field is different to the taxonomy term.
    $form['parent']['#tree'] = TRUE;
    $form['parent'][0] = $this->forumParentSelect($taxonomy_term->id(), t('Parent'));

    $form['#theme'] = 'forum_form';
    $this->forumFormType = t('forum');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, array &$form_state) {
    $term = parent::buildEntity($form, $form_state);

    // Assign parents from forum parent select field.
    $term->parent = array($form_state['values']['parent'][0]);

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $term = $this->entity;

    $status = $this->storageController->save($term);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message(t('Created new @type %term.', array('%term' => $term->label(), '@type' => $this->forumFormType)));
        watchdog('forum', 'Created new @type %term.', array('%term' => $term->label(), '@type' => $this->forumFormType), WATCHDOG_NOTICE, l(t('edit'), 'admin/structure/forum/edit/' . $this->urlStub . '/' . $term->id()));
        $form_state['values']['tid'] = $term->id();
        break;

      case SAVED_UPDATED:
        drupal_set_message(t('The @type %term has been updated.', array('%term' => $term->label(), '@type' => $this->forumFormType)));
        watchdog('taxonomy', 'Updated @type %term.', array('%term' => $term->label(), '@type' => $this->forumFormType), WATCHDOG_NOTICE, l(t('edit'), 'admin/structure/forum/edit/' . $this->urlStub . '/' . $term->id()));
        // Clear the page and block caches to avoid stale data.
        Cache::invalidateTags(array('content' => TRUE));
        break;
    }

    $form_state['redirect'] = 'admin/structure/forum';
    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    if ($this->request->query->get('destination')) {
      $destination = drupal_get_destination();
      $this->request->query->remove('destination');
    }
    $term = $this->getEntity($form_state);
    $form_state['redirect'] = array(
      'admin/structure/forum/delete/forum/' . $term->id(),
      array('query' => $destination),
    );
  }

  /**
   * Returns a select box for available parent terms.
   *
   * @param int $tid
   *   ID of the term that is being added or edited.
   * @param string $title
   *   Title for the select box.
   *
   * @return array
   *   A select form element.
   */
  protected function forumParentSelect($tid, $title) {
    // @todo Inject a taxonomy service when one exists.
    $parents = taxonomy_term_load_parents($tid);
    if ($parents) {
      $parent = array_shift($parents);
      $parent = $parent->id();
    }
    else {
      $parent = 0;
    }

    $vid = $this->config->get('vocabulary');
    // @todo Inject a taxonomy service when one exists.
    $children = taxonomy_get_tree($vid, $tid, NULL, TRUE);

    // A term can't be the child of itself, nor of its children.
    foreach ($children as $child) {
      $exclude[] = $child->tid;
    }
    $exclude[] = $tid;

    // @todo Inject a taxonomy service when one exists.
    $tree = taxonomy_get_tree($vid, 0, NULL, TRUE);
    $options[0] = '<' . t('root') . '>';
    if ($tree) {
      foreach ($tree as $term) {
        if (!in_array($term->id(), $exclude)) {
          $options[$term->id()] = str_repeat(' -- ', $term->depth) . $term->label();
        }
      }
    }

    $description = t('Forums may be placed at the top (root) level, or inside another container or forum.');

    return array(
      '#type' => 'select',
      '#title' => $title,
      '#default_value' => $parent,
      '#options' => $options,
      '#description' => $description,
      '#required' => TRUE,
    );
  }

}
