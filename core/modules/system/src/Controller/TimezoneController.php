<?php

namespace Drupal\system\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a callback for finding out a timezone name.
 */
class TimezoneController {

  /**
   * Retrieve a JSON object containing a time zone name given a timezone
   * abbreviation.
   *
   * @param string $abbreviation
   *   Time zone abbreviation.
   * @param int $offset
   *   Offset from GMT in seconds. Defaults to -1 which means that first found
   *   time zone corresponding to abbr is returned. Otherwise exact offset is
   *   searched and only if not found then the first time zone with any offset
   *   is returned.
   * @param null|bool $is_daylight_saving_time
   *   Daylight saving time indicator. If abbr does not exist then the time
   *   zone is searched solely by offset and isdst.
   *
   * @return JsonResponse
   *   The timezone name in JsonResponse object.
   */
  public function getTimezone($abbreviation = '', $offset = -1, $is_daylight_saving_time = NULL) {
    // An abbreviation of "0" passed in the callback arguments should be
    // interpreted as the empty string.
    $abbreviation = $abbreviation ? $abbreviation : '';
    $timezone = timezone_name_from_abbr($abbreviation, intval($offset), $is_daylight_saving_time);
    return new JsonResponse($timezone);
  }

}
