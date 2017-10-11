<?php

namespace Drupal\views_ui;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Defines a class to build a listing of view entities.
 *
 * @see \Drupal\views\Entity\View
 */
class ViewListBuilder extends ConfigEntityListBuilder {

  /**
   * The views display plugin manager to use.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $displayManager;

  /**
   * {@inheritdoc}
   */
  protected $limit;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.views.display')
    );
  }

  /**
   * Constructs a new ViewListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $display_manager
   *   The views display plugin manager to use.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, PluginManagerInterface $display_manager) {
    parent::__construct($entity_type, $storage);

    $this->displayManager = $display_manager;
    // This list builder uses client-side filters which requires all entities to
    // be listed, disable the pager.
    // @todo https://www.drupal.org/node/2536826 change the filtering to support
    //   a pager.
    $this->limit = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = [
      'enabled' => [],
      'disabled' => [],
    ];
    foreach (parent::load() as $entity) {
      if ($entity->status()) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $view) {
    $row = parent::buildRow($view);
    return [
      'data' => [
        'view_name' => [
          'data' => [
            '#plain_text' => $view->label(),
          ],
        ],
        'machine_name' => [
          'data' => [
            '#plain_text' => $view->id(),
          ],
        ],
        'description' => [
          'data' => [
            '#plain_text' => $view->get('description'),
          ],
        ],
        'displays' => [
          'data' => [
            '#theme' => 'views_ui_view_displays_list',
            '#displays' => $this->getDisplaysList($view),
          ],
        ],
        'operations' => $row['operations'],
      ],
      '#attributes' => [
        'class' => [$view->status() ? 'views-ui-list-enabled' : 'views-ui-list-disabled'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'view_name' => [
        'data' => $this->t('View name'),
        '#attributes' => [
          'class' => ['views-ui-name'],
        ],
      ],
      'machine_name' => [
        'data' => $this->t('Machine name'),
        '#attributes' => [
          'class' => ['views-ui-machine-name'],
        ],
      ],
      'description' => [
        'data' => $this->t('Description'),
        '#attributes' => [
          'class' => ['views-ui-description'],
        ],
      ],
      'displays' => [
        'data' => $this->t('Displays'),
        '#attributes' => [
          'class' => ['views-ui-displays'],
        ],
      ],
      'operations' => [
        'data' => $this->t('Operations'),
        '#attributes' => [
          'class' => ['views-ui-operations'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->hasLinkTemplate('duplicate-form')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 15,
        'url' => $entity->urlInfo('duplicate-form'),
      ];
    }

    // Add AJAX functionality to enable/disable operations.
    foreach (['enable', 'disable'] as $op) {
      if (isset($operations[$op])) {
        $operations[$op]['url'] = $entity->urlInfo($op);
        // Enable and disable operations should use AJAX.
        $operations[$op]['attributes']['class'][] = 'use-ajax';
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    $list['#type'] = 'container';
    $list['#attributes']['id'] = 'views-entity-list';

    $list['#attached']['library'][] = 'core/drupal.ajax';
    $list['#attached']['library'][] = 'views_ui/views_ui.listing';

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $list['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 60,
      '#placeholder' => $this->t('Filter by view name, machine name, description, or display path'),
      '#attributes' => [
        'class' => ['views-filter-text'],
        'data-table' => '.views-listing-table',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the view name, machine name, description, or display path to filter by.'),
      ],
    ];

    $list['enabled']['heading']['#markup'] = '<h2>' . $this->t('Enabled', [], ['context' => 'Plural']) . '</h2>';
    $list['disabled']['heading']['#markup'] = '<h2>' . $this->t('Disabled', [], ['context' => 'Plural']) . '</h2>';
    foreach (['enabled', 'disabled'] as $status) {
      $list[$status]['#type'] = 'container';
      $list[$status]['#attributes'] = ['class' => ['views-list-section', $status]];
      $list[$status]['table'] = [
        '#theme' => 'views_ui_views_listing_table',
        '#headers' => $this->buildHeader(),
        '#attributes' => ['class' => ['views-listing-table', $status]],
      ];
      foreach ($entities[$status] as $entity) {
        $list[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    // @todo Use a placeholder for the entity label if this is abstracted to
    // other entity types.
    $list['enabled']['table']['#empty'] = $this->t('There are no enabled views.');
    $list['disabled']['table']['#empty'] = $this->t('There are no disabled views.');

    return $list;
  }

  /**
   * Gets a list of displays included in the view.
   *
   * @param \Drupal\Core\Entity\EntityInterface $view
   *   The view entity instance to get a list of displays for.
   *
   * @return array
   *   An array of display types that this view includes.
   */
  protected function getDisplaysList(EntityInterface $view) {
    $displays = [];

    $executable = $view->getExecutable();
    $executable->initDisplay();
    foreach ($executable->displayHandlers as $display) {
      $rendered_path = FALSE;
      $definition = $display->getPluginDefinition();
      if (!empty($definition['admin'])) {
        if ($display->hasPath()) {
          $path = $display->getPath();
          if ($view->status() && strpos($path, '%') === FALSE) {
            // Wrap this in a try/catch as trying to generate links to some
            // routes may throw a NotAcceptableHttpException if they do not
            // respond to HTML, such as RESTExports.
            try {
              // @todo Views should expect and store a leading /. See:
              //   https://www.drupal.org/node/2423913
              $rendered_path = \Drupal::l('/' . $path, Url::fromUserInput('/' . $path));
            }
            catch (NotAcceptableHttpException $e) {
              $rendered_path = '/' . $path;
            }
          }
          else {
            $rendered_path = '/' . $path;
          }
        }
        $displays[] = [
          'display' => $definition['admin'],
          'path' => $rendered_path,
        ];
      }
    }

    sort($displays);
    return $displays;
  }

}
