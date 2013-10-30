<?php

/**
 * @file
 * Contains \Drupal\views_ui\Controller\ViewsUIController.
 */

namespace Drupal\views_ui\Controller;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewStorageInterface;
use Drupal\views_ui\ViewUI;
use Drupal\views\ViewsData;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Returns responses for Views UI routes.
 */
class ViewsUIController implements ContainerInjectionInterface {

  /**
   * Stores the Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Stores the Views data cache object.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * The URL generator to use.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The link generator to use.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Constructs a new \Drupal\views_ui\Controller\ViewsUIController object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The Entity manager.
   * @param \Drupal\views\ViewsData views_data
   *   The Views data cache object.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator.
   */
  public function __construct(EntityManagerInterface $entity_manager, ViewsData $views_data, UrlGeneratorInterface $url_generator, LinkGeneratorInterface $link_generator) {
    $this->entityManager = $entity_manager;
    $this->viewsData = $views_data;
    $this->urlGenerator = $url_generator;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('views.views_data'),
      $container->get('url_generator'),
      $container->get('link_generator')
    );
  }

  /**
   * Lists all instances of fields on any views.
   *
   * @return array
   *   The Views fields report page.
   */
  public function reportFields() {
    $views = $this->entityManager->getStorageController('view')->loadMultiple();

    // Fetch all fieldapi fields which are used in views
    // Therefore search in all views, displays and handler-types.
    $fields = array();
    $handler_types = ViewExecutable::viewsHandlerTypes();
    foreach ($views as $view) {
      $executable = $view->getExecutable();
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display_id => $display) {
        if ($executable->setDisplay($display_id)) {
          foreach ($handler_types as $type => $info) {
            foreach ($executable->getItems($type, $display_id) as $item) {
              $table_data = $this->viewsData->get($item['table']);
              if (isset($table_data[$item['field']]) && isset($table_data[$item['field']][$type])
                && $field_data = $table_data[$item['field']][$type]) {
                // The final check that we have a fieldapi field now.
                if (isset($field_data['field_name'])) {
                  $fields[$field_data['field_name']][$view->id()] = $view->id();
                }
              }
            }
          }
        }
      }
    }

    $header = array(t('Field name'), t('Used in'));
    $rows = array();
    foreach ($fields as $field_name => $views) {
      $rows[$field_name]['data'][0] = check_plain($field_name);
      foreach ($views as $view) {
        $rows[$field_name]['data'][1][] = $this->linkGenerator->generate($view, 'views_ui.edit', array('view' => $view));
      }
      $rows[$field_name]['data'][1] = implode(', ', $rows[$field_name]['data'][1]);
    }

    // Sort rows by field name.
    ksort($rows);
    $output = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No fields have been used in views yet.'),
    );

    return $output;
  }

  /**
   * Lists all plugins and what enabled Views use them.
   *
   * @return array
   *   The Views plugins report page.
   */
  public function reportPlugins() {
    $rows = views_plugin_list();
    foreach ($rows as &$row) {
      // Link each view name to the view itself.
      foreach ($row['views'] as $row_name => $view) {
        $row['views'][$row_name] = $this->linkGenerator->generate($view, 'views_ui.edit', array('view' => $view));
      }
      $row['views'] = implode(', ', $row['views']);
    }

    // Sort rows by field name.
    ksort($rows);
    return array(
      '#theme' => 'table',
      '#header' => array(t('Type'), t('Name'), t('Provided by'), t('Used in')),
      '#rows' => $rows,
      '#empty' => t('There are no enabled views.'),
    );
  }

  /**
   * Calls a method on a view and reloads the listing page.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'enable' or 'disable'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns a rebuilt listing page as an AJAX response, or redirects
   *   back to the listing page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function ajaxOperation(ViewStorageInterface $view, $op, Request $request) {
    if (!drupal_valid_token($request->query->get('token'), $op)) {
      // Throw an access denied exception if the token is invalid or missing.
      throw new AccessDeniedHttpException();
    }

    // Perform the operation.
    $view->$op()->save();

    // If the request is via AJAX, return the rendered list as JSON.
    if ($request->request->get('js')) {
      $list = $this->entityManager->getListController('view')->render();
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#views-entity-list', drupal_render($list)));
      return $response;
    }

    // Otherwise, redirect back to the page.
    return new RedirectResponse($this->urlGenerator->generate('views_ui.list', array(), TRUE));
  }

  /**
   * Menu callback for Views tag autocompletion.
   *
   * Like other autocomplete functions, this function inspects the 'q' query
   * parameter for the string to use to search for suggestions.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for Views tags.
   */
  public function autocompleteTag(Request $request) {
    $matches = array();
    $string = $request->query->get('q');
    // Get matches from default views.
    $views = $this->entityManager->getStorageController('view')->loadMultiple();
    foreach ($views as $view) {
      $tag = $view->get('tag');
      if ($tag && strpos($tag, $string) === 0) {
        $matches[$tag] = $tag;
        if (count($matches) >= 10) {
          break;
        }
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Returns the form to edit a view.
   *
   * @param \Drupal\views_ui\ViewUI $view
   *   The view being deleted.
   * @param string|null $display_id
   *   (optional) The display ID being edited. Defaults to NULL, which will load
   *   the first available display.
   *
   * @return array
   *   An array containing the Views edit and preview forms.
   */
  public function edit(ViewUI $view, $display_id = NULL) {
    $name = $view->label();
    $data = $this->viewsData->get($view->get('base_table'));

    if (isset($data['table']['base']['title'])) {
      $name .= ' (' . $data['table']['base']['title'] . ')';
    }
    drupal_set_title($name);

    $build['edit'] = $this->entityManager->getForm($view, 'edit', array('display_id' => $display_id));
    $build['preview'] = $this->entityManager->getForm($view, 'preview', array('display_id' => $display_id));
    return $build;
  }

}
