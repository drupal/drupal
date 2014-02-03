<?php

/**
 * @file
 * Contains \Drupal\tour_test\Controller\TourTestController.
 */

namespace Drupal\tour_test\Controller;

/**
 * Controller routines for tour_test routes.
 */
class TourTestController {

  /**
   * Outputs some content for testing tours.
   *
   * @param string $locale
   *  (optional) Dummy locale variable for testing routing parameters. Defaults
   *   to 'foo'.
   *
   * @return array
   *   Array of markup.
   */
  public function tourTest1($locale = 'foo') {
    return array(
      'tip-1' => array(
        '#type' => 'container',
        '#attributes' => array(
          'id' => 'tour-test-1',
        ),
        '#children' => t('Where does the rain in Spain fail?'),
      ),
      'tip-3' => array(
        '#type' => 'container',
        '#attributes' => array(
          'id' => 'tour-test-3',
        ),
        '#children' => t('Tip created now?'),
      ),
      'tip-4' => array(
        '#type' => 'container',
        '#attributes' => array(
          'id' => 'tour-test-4',
        ),
        '#children' => t('Tip created later?'),
      ),
      'tip-5' => array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => 'tour-test-5',
        ),
        '#children' => t('Tip created later?'),
      ),
      'code-tip-1' => array(
        '#type' => 'container',
        '#attributes' => array(
          'id' => 'tour-code-test-1',
        ),
        '#children' => t('Tip created now?'),
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
