Poltergeist.NoSuchWindowError = (function (_super) {
  __extends(NoSuchWindowError, _super);

  function NoSuchWindowError() {
    _ref2 = NoSuchWindowError.__super__.constructor.apply(this, arguments);
    return _ref2;
  }

  NoSuchWindowError.prototype.name = "Poltergeist.NoSuchWindowError";

  NoSuchWindowError.prototype.args = function () {
    return [];
  };

  return NoSuchWindowError;

})(Poltergeist.Error);
