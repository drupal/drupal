<?php

/**
 * @file
 * Contains \Drupal\system\Controller\LanguageDateFormatController.
 */

namespace Drupal\system\Controller;

/**
 * Controller for Language Date Format handling.
 */
class DateFormatLanguageController {

  /**
   * Displays edit date format links for each language.
   *
   * @return array
   *   Render array of overview page.
   */
  public function overviewPage() {

    $header = array(t('Language'), t('Operations'));

    $languages = language_list();
    foreach ($languages as $langcode => $language) {
      $row = array();
      $row[] = $language->name;
      $links = array();
      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => 'admin/config/regional/date-time/locale/' . $langcode . '/edit',
      );
      $links['reset'] = array(
        'title' => t('Reset'),
        'href' => 'admin/config/regional/date-time/locale/' . $langcode . '/reset',
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }

    return array('#theme' => 'table', '#header' => $header, '#rows' => $rows);

  }

}
