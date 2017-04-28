<?php

use aik099\PHPUnit\BrowserTestCase;

use Drupal\Driver\DrupalDriver;

class GeneralTest extends BrowserTestCase
{

    /**
     * @var \Drupal\Driver\DriverInterface
     */
     protected static $driver;

    // Path to a Drupal install. This example assumes the directory is in the same one as the `composer.json` file.
    protected static $drupalRoot = './drupal';

    // Url to the homepage of the Drupal install.
    protected static $uri = 'http://d8.devl';

    public static $browsers = array(
        // Selenium info.
        array(
            'host' => 'localhost',
            'port' => 4444,
            'browserName' => 'firefox',
            'baseUrl' => 'http://d8.devl',
        ),
    );

     public static function setUpBeforeClass() {
        self::$driver = new DrupalDriver(static::$drupalRoot, static::$uri);
        self::$driver->setCoreFromVersion();
        self::$driver->bootstrap();
    }

    public function testUsingSession()
    {
        // This is Mink's Session.
        $session = $this->getSession();

        // Go to a page.
        $session->visit(static::$uri);

        // Validate text presence on a page.
        $this->assertTrue($session->getPage()->hasContent('Site-Install'));
    }

    public function testUsingBrowser()
    {
        // Prints the name of used browser.
        echo sprintf(
            "I'm executed using '%s' browser",
            $this->getBrowser()->getBrowserName()
        );
    }

    public function testNodeCreate() {
        $drupal = self::$driver;
        $node = (object) [
            'title' => $drupal->getRandom()->string(),
            'type' => 'article',
        ];
        $drupal->createNode($node);

        $session = $this->getSession();
        $session->visit(static::$uri . '/node/' . $node->nid);

        $this->assertTrue($session->getPage()->hasContent($node->title));
    }

}
