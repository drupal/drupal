<?php

/**
 * @file
 * Contains \Drupal\language_test\Controller\LanguageTestController.
 */

namespace Drupal\language_test\Controller;

use Drupal\Core\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for language_test routes.
 */
class LanguageTestController implements ControllerInterface {

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Returns links to the current page with different langcodes.
   *
   * Using #theme causes these links to be rendered with theme_link().
   */
  public function themeLinkActiveClass() {
    // We assume that 'en' and 'fr' have been configured.
    $languages = language_list();
    return array(
      'no_language' => array(
        '#theme' => 'link',
        '#text' => t('Link to the current path with no langcode provided.'),
        '#path' => current_path(),
        '#options' => array(
          'attributes' => array(
            'id' => 'no_lang_link',
          ),
        ),
      ),
      'fr' => array(
        '#theme' => 'link',
        '#text' => t('Link to a French version of the current path.'),
        '#path' => current_path(),
        '#options' => array(
          'language' => $languages['fr'],
          'attributes' => array(
            'id' => 'fr_link',
          ),
        ),
      ),
      'en' => array(
        '#theme' => 'link',
        '#text' => t('Link to an English version of the current path.'),
        '#path' => current_path(),
        '#options' => array(
          'language' => $languages['en'],
          'attributes' => array(
            'id' => 'en_link',
          ),
        ),
      ),
    );
  }

  /**
   * Returns links to the current page with different langcodes.
   *
   * Using #type causes these links to be rendered with l().
   */
  public function lActiveClass() {
    // We assume that 'en' and 'fr' have been configured.
    $languages = language_list();
    return array(
      'no_language' => array(
        '#type' => 'link',
        '#title' => t('Link to the current path with no langcode provided.'),
        '#href' => current_path(),
        '#options' => array(
          'attributes' => array(
            'id' => 'no_lang_link',
          ),
        ),
      ),
      'fr' => array(
        '#type' => 'link',
        '#title' => t('Link to a French version of the current path.'),
        '#href' => current_path(),
        '#options' => array(
          'language' => $languages['fr'],
          'attributes' => array(
            'id' => 'fr_link',
          ),
        ),
      ),
      'en' => array(
        '#type' => 'link',
        '#title' => t('Link to an English version of the current path.'),
        '#href' => current_path(),
        '#options' => array(
          'language' => $languages['en'],
          'attributes' => array(
            'id' => 'en_link',
          ),
        ),
      ),
    );
  }

}
