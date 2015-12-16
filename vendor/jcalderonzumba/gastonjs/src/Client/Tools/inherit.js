var __extends;
/**
 * Helper function so objects can inherit from another
 * @param child
 * @param parent
 * @return {Object}
 * @private
 */
__extends = function (child, parent) {
  var __hasProp;
  __hasProp = {}.hasOwnProperty;
  for (var key in parent) {
    if (parent.hasOwnProperty(key)) {
      if (__hasProp.call(parent, key)) {
        child[key] = parent[key];
      }
    }
  }

  function ClassConstructor() {
    this.constructor = child;
  }

  ClassConstructor.prototype = parent.prototype;
  child.prototype = new ClassConstructor();
  child.__super__ = parent.prototype;
  return child;
};
