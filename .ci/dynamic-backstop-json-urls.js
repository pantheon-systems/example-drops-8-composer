// Require the node file system
var fs = require('fs');
// Stash the directory where the script was started from
var rootPath = process.cwd();
// Get the contents of the backstop.json template
var fileContents = require(rootPath + '/.ci/backstop.json');

// Loop through all scrnarios in the template
var newScenarios = fileContents.scenarios.map(function(scenario) {
    // Set url of the scenarios to the multidev URL
    scenario.url = process.env.MULTIDEV_SITE_URL;
    // Set reference url of the scenarios to the live URL
    scenario.referenceUrl = process.env.LIVE_SITE_URL;
    // Return the updated scenario
    return scenario;
});

// Update the scenarios from the template with the new
// version containing the actual URLs
fileContents.scenarios = newScenarios;

// Write the backstop.json file
fs.writeFileSync(rootPath + '/.ci/backstop.json', JSON.stringify(fileContents, null, 2), function (err) {
  if (err) return console.log(err);
  console.log(rootPath + '/.ci/backstop.json created successfully!');
});