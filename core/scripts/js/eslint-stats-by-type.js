module.exports = function (results) {
  results = results || [];

  const errorType = {
    warnings: {},
    errors: {},
  };

  results.reduce((result, current) => {
    current.messages.forEach((msg) => {
      if (msg.severity === 1) {
        errorType.warnings[msg.ruleId] = errorType.warnings[msg.ruleId] + 1 || 1
      }
      if (msg.severity === 2) {
        errorType.errors[msg.ruleId] = errorType.errors[msg.ruleId] + 1 || 1
      }
    });
    return result;
  });

  const reduceErrorCounts = (errorType) => Object.entries(errorType).sort((a, b) => b[1] - a[1])
    .reduce((result, current) => result.concat([`${current[0]}: ${current[1]}`]), []).join('\n');

  const warnings = reduceErrorCounts(errorType.warnings);
  const errors = reduceErrorCounts(errorType.errors);

  return `
Errors:
${'='.repeat(30)}
${errors}
${'\n'.repeat(4)}
Warnings:
${'='.repeat(30)}
${warnings}`;
};
