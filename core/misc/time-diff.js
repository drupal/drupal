/**
 * @file
 * Dynamic time difference formatting.
 */

((Drupal, once) => {
  /**
   * @typedef {object} timeDiffValue
   *
   * @prop {number} [year]
   *   Years count.
   * @prop {number} [month]
   *   Months count.
   * @prop {number} [week]
   *   Weeks count.
   * @prop {number} [day]
   *   Days count.
   * @prop {number} [hour]
   *   Hours count.
   * @prop {number} [minute]
   *   Minutes count.
   * @prop {number} [second]
   *   Seconds count.
   */

  /**
   * @typedef {object} timeDiff
   *
   * @prop {string} formatted
   *   A translated string representation of the interval.
   * @prop {timeDiffValue} value
   *   The elements composing the time difference interval. Example: { day: 2,
   *   hour: 2, minute: 32, second: 15 }.
   */

  /**
   * List of time intervals.
   *
   * @type {object}
   *
   * @prop {number} year
   *   Year duration in seconds.
   * @prop {number} month
   *   Month duration in seconds.
   * @prop {number} week
   *   Week duration in seconds.
   * @prop {number} day
   *   Day duration in seconds.
   * @prop {number} hour
   *   Hour duration in seconds.
   * @prop {number} minute
   *   Minute duration in seconds.
   * @prop {number} second
   *   One second.
   */
  const intervals = {
    year: 31536000,
    month: 2592000,
    week: 604800,
    day: 86400,
    hour: 3600,
    minute: 60,
    second: 1,
  };

  /**
   * List of available time intervals names.
   *
   * @type {string[]}
   */
  const intervalsNames = Object.keys(intervals);

  /**
   *
   * @type {WeakMap<HTMLElement, number>}
   */
  const timers = new WeakMap();

  /**
   * @namespace
   */
  Drupal.timeDiff = {
    /**
     * Fills a HTML5 time element text with a computed time difference string.
     *
     * @param {Element} timeElement
     *   The time DOM element.
     */
    show(timeElement) {
      const timestamp = new Date(
        timeElement.getAttribute('datetime'),
      ).getTime();
      const timeDiffSettings = JSON.parse(
        timeElement.getAttribute('data-drupal-time-diff'),
      );

      const now = Date.now();
      const diff = Math.round((timestamp - now) / 1000);
      const options = { granularity: timeDiffSettings.granularity };
      const timeDiff = Drupal.timeDiff.format(diff, options);
      const format = diff > 0 ? 'future' : 'past';
      timeElement.textContent = Drupal.formatString(
        timeDiffSettings.format[format],
        {
          '@interval': timeDiff.formatted,
        },
      );

      if (timeDiffSettings.refresh > 0) {
        const refreshInterval = Drupal.timeDiff.refreshInterval(
          timeDiff.value,
          timeDiffSettings.refresh,
          timeDiffSettings.granularity,
        );
        clearTimeout(timers.get(timeElement));
        timers.set(
          timeElement,
          setTimeout(Drupal.timeDiff.show, refreshInterval * 1000, timeElement),
        );
      }
    },

    /**
     * Computes the refresh interval.
     *
     * There are cases when the refresh occurs even when it is not needed. For
     * example if the refresh interval is '10 seconds', the granularity is 2 and
     * the time difference is '1 hour 32 minutes', there's no need to refresh
     * every 10 seconds but every 1 minute. This function optimizes the refresh
     * interval to higher values, if the structure of the time difference
     * doesn't require refreshing more often.
     *
     * @param {timeDiffValue} value
     *   The time difference object.
     * @param {number} refresh
     *   The configured refresh interval in seconds.
     * @param {number} granularity
     *   The time difference granularity.
     *
     * @return {number}
     *   The computed refresh interval in seconds.
     */
    refreshInterval(value, refresh, granularity) {
      const units = Object.keys(value);
      const unitsCount = units.length;
      const lastUnit = units.pop();

      // If the lowest unit of time difference is 'minute' or greater but the
      // refresh interval is lower, do not refresh often than the duration of
      // the lowest unit of time difference.
      if (lastUnit !== 'second') {
        // If the time difference value parts count equals the granularity and
        // lowest unit duration is bigger than the refresh interval, use the
        // interval duration. For example, if the refresh interval is
        // '10 seconds', the granularity is 2 and the time difference is
        // '1 hour 32 minutes', do not refresh every 10 seconds but every one
        // minute (60 seconds).
        if (unitsCount === granularity) {
          intervalsNames.every((interval) => {
            const duration = intervals[interval];
            if (interval === lastUnit) {
              refresh = refresh < duration ? duration : refresh;
              return false;
            }
            return true;
          });
          return refresh;
        }
        // The time difference value parts count might be smaller than the
        // granularity when the lowest part is missed because is 0. In this case
        // the missed part interval duration is used as refresh. For example, if
        // the refresh is '10 seconds', the granularity is 2 and the time
        // difference is '59 minutes 59 seconds', on the next refresh the time
        // difference will be '1 hour' (because minutes are 0, therefore are not
        // shown) but we want the next refresh to occur, not in one hour, but in
        // one minute.
        const lastIntervalIndex = intervalsNames.indexOf(lastUnit);
        const nextInterval = intervalsNames[lastIntervalIndex + 1];
        refresh = intervals[nextInterval];
      }
      return refresh;
    },

    /**
     * Formats a time interval between two timestamps.
     *
     * @param {number} diff
     *   A UNIX timestamps difference in seconds.
     * @param {object} [options]
     *   An optional object with additional options.
     * @param {number} [options.granularity=2]
     *   An integer value that signals how many different units to display in the
     *   string. Defaults to 2.
     * @param {boolean} [options.strict=false]
     *   A boolean value indicating whether or not, a negative diff should be
     *   rendered as "0 seconds". If the time difference is negative (i.e. the
     *   timestamp is in the past) and this option is false (default) the result
     *   string will be the formatted time difference. If the option is true the
     *   result string will be "0 seconds".
     *
     * @return {timeDiff}
     *   A time difference type object.
     */
    format(diff, options = {}) {
      // Provide appropriate defaults.
      options = { granularity: 2, strict: false, ...options };

      if (options.strict && diff < 0) {
        return {
          formatted: Drupal.formatPlural(0, '1 second', '@count seconds'),
          value: { second: 0 },
        };
      }
      diff = Math.abs(diff);

      const output = [];
      const value = {};
      let units;
      let { granularity } = options;

      intervalsNames.every((interval) => {
        const duration = intervals[interval];
        units = Math.floor(diff / duration);
        if (units > 0) {
          diff %= units * duration;
          switch (interval) {
            case 'year':
              output.push(Drupal.formatPlural(units, '1 year', '@count years'));
              break;
            case 'month':
              output.push(
                Drupal.formatPlural(units, '1 month', '@count months'),
              );
              break;
            case 'week':
              output.push(Drupal.formatPlural(units, '1 week', '@count weeks'));
              break;
            case 'day':
              output.push(Drupal.formatPlural(units, '1 day', '@count days'));
              break;
            case 'hour':
              output.push(Drupal.formatPlural(units, '1 hour', '@count hours'));
              break;
            case 'minute':
              output.push(
                Drupal.formatPlural(units, '1 minute', '@count minutes'),
              );
              break;
            default:
              output.push(
                Drupal.formatPlural(units, '1 second', '@count seconds'),
              );
          }
          value[interval] = units;

          granularity -= 1;
          if (granularity <= 0) {
            // Limit the granularity of the output.
            return false;
          }
        } else if (output.length > 0) {
          // Exit if there was previous output but not any output at this level,
          // to avoid skipping levels and getting output like "1 year 1 second".
          return false;
        }
        return true;
      });

      if (output.length === 0) {
        return {
          formatted: Drupal.formatPlural(0, '1 second', '@count seconds'),
          value: { second: 0 },
        };
      }
      return { formatted: output.join(' '), value };
    },
  };

  /**
   * Fills all time[data-drupal-time-diff] elements with a refreshing time diff.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Initializes refresh of time differences.
   *
   * @prop {Drupal~behaviorDetach} detach
   *   Clear timers associated with time diff elements.
   */
  Drupal.behaviors.timeDiff = {
    attach(context) {
      // Replace each <time> element text with a time difference representation.
      once('time-diff', 'time[data-drupal-time-diff]', context).forEach(
        Drupal.timeDiff.show,
      );
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        once
          .remove('time-diff', 'time[data-drupal-time-diff]', context)
          .forEach((timeElement) => clearTimeout(timers.get(timeElement)));
      }
    },
  };
})(Drupal, once);
