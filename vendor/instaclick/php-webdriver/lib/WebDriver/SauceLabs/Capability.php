<?php
/**
 * Copyright 2012-2017 Anthon Pang. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package WebDriver
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */

namespace WebDriver\SauceLabs;

use WebDriver\Capability as BaseCapability;

/**
 * WebDriver\SauceLabs\Capability class
 *
 * @package WebDriver
 */
class Capability extends BaseCapability
{
    /**
     * Desired capabilities - SauceLabs
     *
     * @see https://saucelabs.com/docs/additional-config
     */

    // Job Annotation
    const NAME                  = 'name';                  // Name the job
    const BUILD                 = 'build';                 // Record the build number
    const TAGS                  = 'tags';                  // Tag your jobs
    const PASSED                = 'passed';                // Record pass/fail status
    const CUSTOM_DATA           = 'custom-data';           // Record custom data

    // Performance improvements and data collection
    const RECORD_VIDEO          = 'record-video';          // Video recording
    const VIDEO_UPLOAD_ON_PASS  = 'video-upload-on-pass';  // Video upload on pass
    const RECORD_SCREENSHOTS    = 'record-screenshots';    // Record step-by-step screenshots
    const CAPTURE_HTML          = 'capture-html';          // HTML source capture
    const QUIET_EXCEPTIONS      = 'webdriver.remote.quietExceptions'; // Enable Selenium 2's automatic screenshots
    const SAUCE_ADVISOR         = 'sauce-advisor';         // Sauce Advisor

    // Selenium specific
    const SELENIUM_VERSION      = 'selenium-version';      // Use a specific Selenium version
    const SINGLE_WINDOW         = 'single-window';         // Selenium RC's single window mode
    const USER_EXTENSIONS_URL   = 'user-extensions-url';   // Selenium RC's user extensions
    const FIREFOX_PROFILE_URL   = 'firefox-profile-url';   // Selenium RC's custom Firefox profiles

    // Timeouts
    const MAX_DURATION          = 'max-duration';          // Set maximum test duration
    const COMMAND_TIMEOUT       = 'command-timeout';       // Set command timeout
    const IDLE_TIMEOUT          = 'idle-timeout';          // Set idle test timeout

    // Sauce specific
    const PRERUN                = 'prerun';                // Prerun executables
    const TUNNEL_IDENTIFIER     = 'tunnel-identifier';     // Use identified tunnel
    const SCREEN_RESOLUTION     = 'screen-resolution';     // Use specific screen resolution
    const DISABLE_POPUP_HANDLER = 'disable-popup-handler'; // Disable popup handler
    const AVOID_PROXY           = 'avoid-proxy';           // Avoid proxy
    const DEVICE_ORIENTATION    = 'deviceOrientation';     // Device orientation (portrait or landscape)
    const DEVICE_TYPE           = 'deviceType';            // Device type (phone or tablet)

    // Job Sharing
    const PUBLIC_RESULTS        = 'public';                // Make public, private, or share jobs
}
