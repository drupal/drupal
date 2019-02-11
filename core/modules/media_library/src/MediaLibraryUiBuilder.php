<?php

namespace Drupal\media_library;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service which builds the media library.
 *
 * @internal
 *   This class is an internal part of the media library and should not be
 *   instantiated or used by external code.
 */
class MediaLibraryUiBuilder {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The views executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewsExecutableFactory;

  /**
   * Constructs a MediaLibraryUiBuilder instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\views\ViewExecutableFactory $views_executable_factory
   *   The views executable factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, ViewExecutableFactory $views_executable_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request_stack->getCurrentRequest();
    $this->viewsExecutableFactory = $views_executable_factory;
  }

  /**
   * Get media library dialog options.
   *
   * @return array
   *   The media library dialog options.
   */
  public static function dialogOptions() {
    return [
      'dialogClass' => 'media-library-widget-modal',
      'title' => t('Media library'),
      'height' => '75%',
      'width' => '75%',
    ];
  }

  /**
   * Build the media library UI.
   *
   * @return array
   *   The render array for the media library.
   */
  public function buildUi() {
    $state = MediaLibraryState::fromRequest($this->request);
    // When navigating to a media type through the vertical tabs, we only want
    // to load the changed library content. This is not only more efficient, but
    // also provides a more accessible user experience for screen readers.
    if ($state->get('media_library_content') === '1') {
      return $this->buildLibraryContent($state);
    }
    else {
      return [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'id' => 'media-library-wrapper',
          'class' => ['media-library-wrapper'],
        ],
        'menu' => $this->buildMediaTypeMenu($state),
        'content' => $this->buildLibraryContent($state),
        '#attached' => [
          'library' => ['media_library/ui'],
        ],
      ];
    }
  }

  /**
   * Build the media library content area.
   *
   * @param \Drupal\media_library\MediaLibraryState $state
   *   The current state of the media library, derived from the current request.
   *
   * @return array
   *   The render array for the media library.
   */
  protected function buildLibraryContent(MediaLibraryState $state) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'media-library-content',
        'class' => ['media-library-content'],
        'tabindex' => -1,
      ],
      'view' => $this->buildMediaLibraryView($state),
    ];
  }

  /**
   * Check access to the media library.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(AccountInterface $account = NULL) {
    // Deny access if the view or display are removed.
    $view = $this->entityTypeManager->getStorage('view')->load('media_library');
    if (!$view) {
      return AccessResult::forbidden('The media library view does not exist.')
        ->setCacheMaxAge(0);
    }
    if (!$view->getDisplay('widget')) {
      return AccessResult::forbidden('The media library widget display does not exist.')
        ->addCacheableDependency($view);
    }
    return AccessResult::allowedIfHasPermission($account, 'view media')
      ->addCacheableDependency($view);
  }

  /**
   * Get the media type menu for the media library.
   *
   * @param \Drupal\media_library\MediaLibraryState $state
   *   The current state of the media library, derived from the current request.
   *
   * @return array
   *   The render array for the media type menu.
   */
  protected function buildMediaTypeMenu(MediaLibraryState $state) {
    // Add the menu for each type if we have more than 1 media type enabled for
    // the field.
    $allowed_type_ids = $state->getAllowedTypeIds();
    if (count($allowed_type_ids) === 1) {
      return [];
    }

    // @todo: Add a class to the li element.
    //   https://www.drupal.org/project/drupal/issues/3029227
    $menu = [
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => [
        'class' => ['media-library-menu', 'js-media-library-menu'],
      ],
    ];

    // Get the state parameters but remove the wrapper format. Also add the
    // 'media_library_content' argument to fetch only the updated content for
    // the tab.
    // @see self::buildUi()
    $state->remove(MainContentViewSubscriber::WRAPPER_FORMAT);
    $state->add(['media_library_content' => 1]);
    $query = $state->all();

    $allowed_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple($allowed_type_ids);

    $selected_type_id = $state->getSelectedTypeId();
    foreach ($allowed_types as $allowed_type_id => $allowed_type) {
      $query['media_library_selected_type'] = $allowed_type_id;

      $title = $allowed_type->label();
      if ($allowed_type_id === $selected_type_id) {
        $title = [
          '#markup' => $this->t('@title<span class="active-tab visually-hidden"> (active tab)</span>', ['@title' => $title]),
        ];
      }

      $menu['#links']['media-library-menu-' . $allowed_type_id] = [
        'title' => $title,
        'url' => Url::fromRoute('media_library.ui', [], [
          'query' => $query,
        ]),
        'attributes' => [
          'class' => ['media-library-menu__link'],
        ],
      ];
    }

    // Set the active menu item.
    $menu['#links']['media-library-menu-' . $selected_type_id]['attributes']['class'][] = 'active';

    return $menu;
  }

  /**
   * Get the media library view.
   *
   * @param \Drupal\media_library\MediaLibraryState $state
   *   The current state of the media library, derived from the current request.
   *
   * @return array
   *   The render array for the media library view.
   */
  protected function buildMediaLibraryView(MediaLibraryState $state) {
    // @todo Make the view configurable in
    //   https://www.drupal.org/project/drupal/issues/2971209
    $view = $this->entityTypeManager->getStorage('view')->load('media_library');
    $view_executable = $this->viewsExecutableFactory->get($view);
    $display_id = 'widget';

    $args = [$state->getSelectedTypeId()];

    $view_executable->setDisplay($display_id);
    $view_executable->preExecute($args);
    $view_executable->execute($display_id);

    return $view_executable->buildRenderable($display_id, $args, FALSE);
  }

}
