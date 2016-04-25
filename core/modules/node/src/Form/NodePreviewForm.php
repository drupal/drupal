<?php

namespace Drupal\node\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains a form for switching the view mode of a node during preview.
 */
class NodePreviewForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'), $container->get('config.factory'));
  }

  /**
   * Constructs a new NodePreviewForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_preview_form_select';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node being previews
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $node = NULL) {
    $view_mode = $node->preview_view_mode;

    $query_options = $node->isNew() ? array('query' => array('uuid' => $node->uuid())) : array();
    $form['backlink'] = array(
      '#type' => 'link',
      '#title' => $this->t('Back to content editing'),
      '#url' => $node->isNew() ? Url::fromRoute('node.add', ['node_type' => $node->bundle()]) : $node->urlInfo('edit-form'),
      '#options' => array('attributes' => array('class' => array('node-preview-backlink'))) + $query_options,
    );

    $view_mode_options = $this->entityManager->getViewModeOptionsByBundle('node', $node->bundle());

    // Unset view modes that are not used in the front end.
    unset($view_mode_options['rss']);
    unset($view_mode_options['search_index']);

    $form['uuid'] = array(
      '#type' => 'value',
      '#value' => $node->uuid(),
    );

    $form['view_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $view_mode_options,
      '#default_value' => $view_mode,
      '#attributes' => array(
        'data-drupal-autosubmit' => TRUE,
      )
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Switch'),
      '#attributes' => array(
        'class' => array('js-hide'),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.node.preview', array(
      'node_preview' => $form_state->getValue('uuid'),
      'view_mode_id' => $form_state->getValue('view_mode'),
    ));
  }

}
