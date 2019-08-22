<?php
use Drupal\DrupalExtension\Context\RawDrupalContext;
use FailAid\Context\FailureContext;

/**
 * Define application features from the specific context.
 */
class FeatureContext extends RawDrupalContext
{
    /**
     * @BeforeStep
     */
    public function beforeStep()
    {
        // Start a session if needed
        $session = $this->getSession();
        if (! $session->isStarted() ) {
            $session->start();
        }

        // Stash the current URL
        $current_url = $session->getCurrentUrl();

        // If we aren't on a valid page
        if ('about:blank' === $current_url ) {
            // Go to the home page
            $session->visit($this->getMinkParameter('base_url'));
        }
    }

    /**
     * Take a screenshot
     *
     * Example: And I take a Chrome screenshot
     * Example: And I take a Chrome screenshot "some-page.png"
     *
     * @Then /^(?:|I )take a Chrome screenshot "(?P<file_name>[^"]+)"$/
     * @Given I take a Chrome screenshot
     */
    public function takeScreenshot($file_name=null)
    {
        $driver = $this->getSession()->getDriver();
        $ss_path = 'behat-screenshots/' . date('Y-m-d');
        if (!file_exists($ss_path)) {
            mkdir($ss_path, 0777, true);
        }
        if ( null == $file_name ) {
            $file_name = 'screenshot-' . date('Y-m-d-H-i-s') . '.png';
        }
        $driver->captureScreenshot($ss_path . '/' . $file_name);
    }
}
