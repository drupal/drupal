<?php

/**
 * @file
 * Hooks provided by the Dashboard module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Adds regions to the dashboard.
 *
 * @return
 *   An array whose keys are the names of the dashboard regions and whose
 *   values are the titles that will be displayed in the blocks administration
 *   interface. The keys are also used as theme wrapper functions.
 */
function hook_dashboard_regions() {
  // Define a new dashboard region. Your module can also then define
  // theme_mymodule_dashboard_region() as a theme wrapper function to control
  // the region's appearance.
  return array('mymodule_dashboard_region' => "My module's dashboard region");
}

/**
 * Alter dashboard regions provided by modules.
 *
 * @param $regions
 *   An array containing all dashboard regions, in the format provided by
 *   hook_dashboard_regions().
 */
function hook_dashboard_regions_alter($regions) {
  // Remove the sidebar region defined by the core dashboard module.
  unset($regions['dashboard_sidebar']);
}

/**
 * @} End of "addtogroup hooks".
 */
