Poltergeist.StatusFailError = (function (_super) {
  __extends(StatusFailError, _super);

  function StatusFailError() {
    _ref1 = StatusFailError.__super__.constructor.apply(this, arguments);
    return _ref1;
  }

  StatusFailError.prototype.name = "Poltergeist.StatusFailError";

  StatusFailError.prototype.args = function () {
    return [];
  };

  return StatusFailError;

})(Poltergeist.Error);
