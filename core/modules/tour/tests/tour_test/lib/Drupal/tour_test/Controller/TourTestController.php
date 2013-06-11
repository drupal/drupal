<?php
/**
 * @file
 * Contains \Drupal\tour_test\Controller\TourTestController.
 */

namespace Drupal\tour_test\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for tour_test routes.
 */
class TourTestController implements ControllerInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Outputs some content for testing tours.
   */
  public function tourTest1() {
    return array(
      'tip-1' => array(
        '#type' => 'container',
        '#attributes' => array(
          'id' => 'tour-test-1',
        ),
        '#children' => t('Where does the rain in Spain fail?'),
      ),
      'tip-4' => array(
        '#type' => 'container',
        '#attributes' => array(
          'id' => 'tour-test-4',
        ),
        '#children' => t('Tip created later?'),
      ),
    );
  }

  /**
   * Outputs some content for testing tours.
   */
  public function tourTest2() {
    return array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'tour-test-2',
      ),
      '#children' => t('Pangram example'),
    );

  }

}
