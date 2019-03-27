var fs = require('fs');
var rootPath = process.cwd();
var fileContents = require(rootPath + '/.ci/backstop.default.json');

var newScenarios = fileContents.scenarios.map(function(scenario) {
    scenario.url = process.env.LIVE_SITE_URL;
    scenario.referenceUrl = process.env.MULTIDEV_SITE_URL;
    return scenario;
});

fileContents.scenarios = newScenarios;

fs.writeFileSync(rootPath + '/.ci/backstop.json', JSON.stringify(fileContents), function (err) {
  if (err) return console.log(err);
  console.log(rootPath + '/.ci/backstop.json created successfully!');
});