<?php

/**
 * @file
 * Contains \Drupal\shortcut\Controller\ShortCutController.
 */

namespace Drupal\shortcut\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\PathBasedGeneratorInterface;
use Drupal\shortcut\ShortcutInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Builds the page for administering shortcut sets.
 */
class ShortcutController implements ControllerInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Stores the entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\PathBasedGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a new \Drupal\shortcut\Controller\ShortCutController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\PathBasedGeneratorInterface $url_generator
   *   The URL generator.
   */
   public function __construct(EntityManager $entity_manager, ModuleHandlerInterface $module_handler, PathBasedGeneratorInterface $url_generator) {
     $this->entityManager = $entity_manager;
     $this->moduleHandler = $module_handler;
     $this->urlGenerator = $url_generator;
   }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('module_handler'),
      $container->get('url_generator')
    );
  }

  /**
   * Creates a new link in the provided shortcut set.
   *
   * @param \Drupal\shortcut\ShortcutInterface $shortcut
   *   The shortcut set to add a link to.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the front page, or the previous location.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function addShortcutLinkInline(ShortcutInterface $shortcut, Request $request) {
    $token = $request->query->get('token');
    $link = $request->query->get('link');
    if (isset($token) && drupal_valid_token($token, 'shortcut-add-link') && shortcut_valid_link($link)) {
      $item = menu_get_item($link);
      $title = ($item && $item['title']) ? $item['title'] : $link;
      $link = array(
        'link_title' => $title,
        'link_path' => $link,
      );
      $this->moduleHandler->loadInclude('shortcut', 'admin.inc');
      shortcut_admin_add_link($link, $shortcut);
      if ($shortcut->save() == SAVED_UPDATED) {
        drupal_set_message(t('Added a shortcut for %title.', array('%title' => $link['link_title'])));
      }
      else {
        drupal_set_message(t('Unable to add a shortcut for %title.', array('%title' => $link['link_title'])));
      }
      return new RedirectResponse($this->urlGenerator->generateFromPath('<front>', array('absolute' => TRUE)));
    }

    throw new AccessDeniedHttpException();
  }

}
