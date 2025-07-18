/**
 * @file
 * Connect Drupal.behaviors to htmx inserted content.
 */
(function (Drupal, htmx, drupalSettings, loadjs) {
  /**
   * Namespace for htmx utilities.
   */
  Drupal.htmx = {
    /**
     * Helper function to merge two objects recursively.
     *
     * @param current
     *   The object to receive the merged values.
     * @param sources
     *   The objects to merge into current.
     *
     * @return object
     *   The merged object.
     *
     * @see https://youmightnotneedjquery.com/#deep_extend
     */
    mergeSettings(current, ...sources) {
      if (!current) {
        return {};
      }

      sources
        .filter((obj) => Boolean(obj))
        .forEach((obj) => {
          Object.entries(obj).forEach(([key, value]) => {
            switch (Object.prototype.toString.call(value)) {
              case '[object Object]':
                current[key] = current[key] || {};
                current[key] = Drupal.htmx.mergeSettings(current[key], value);
                break;

              case '[object Array]':
                current[key] = Drupal.htmx.mergeSettings(
                  new Array(value.length),
                  value,
                );
                break;

              default:
                current[key] = value;
            }
          });
        });

      return current;
    },

    /**
     *
     * @param {array} data
     *
     * @return {Promise}
     */
    addAssets(data) {
      const bundleIds = data
        .filter(({ href, src }) => !loadjs.isDefined(href ?? src))
        .map(({ href, src, type, ...attributes }) => {
          const bundleId = href ?? src;
          let prefix = 'css!';
          if (src) {
            prefix = type === 'module' ? 'module!' : '';
          }

          loadjs(prefix + bundleId, bundleId, {
            // JS files are loaded in order, so this needs to be false when 'src'
            // is defined.
            async: !src,
            // Copy asset tag attributes to the new element.
            before(path, element) {
              // This allows all attributes to be added, like defer, async and
              // crossorigin.
              Object.entries(attributes).forEach(([name, value]) => {
                element.setAttribute(name, value);
              });
            },
          });

          return bundleId;
        });

      // Nothing to load, we resolve the promise right away.
      let assetsLoaded = Promise.resolve();
      // If there are assets to load, use loadjs to manage this process.
      if (bundleIds.length) {
        // Trigger the event once all the dependencies have loaded.
        assetsLoaded = new Promise((resolve, reject) => {
          loadjs.ready(bundleIds, {
            success: resolve,
            error(depsNotFound) {
              const message = Drupal.t(
                `The following files could not be loaded: @dependencies`,
                { '@dependencies': depsNotFound.join(', ') },
              );
              reject(message);
            },
          });
        });
      }

      return assetsLoaded;
    },
  };
})(Drupal, htmx, drupalSettings, loadjs);
