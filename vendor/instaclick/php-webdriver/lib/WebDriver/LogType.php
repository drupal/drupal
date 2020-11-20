<?php
/**
 * Copyright 2014-2017 Anthon Pang. All Rights Reserved.
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

namespace WebDriver;

/**
 * WebDriver\LogType class
 *
 * @package WebDriver
 */
final class LogType
{
    /**
     * Log Type
     *
     * @see https://code.google.com/p/selenium/source/browse/java/client/src/org/openqa/selenium/logging/LogType.java
     */
    const BROWSER     = 'browser';
    const CLIENT      = 'client';
    const DRIVER      = 'driver';
    const PERFORMANCE = 'performance';
    const PROFILER    = 'driver';
    const SERVER      = 'server';
}
