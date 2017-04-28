<?php

namespace Drupal\dblog\Tests\Rest;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests the watchdog database log resource.
 *
 * @group dblog
 */
class DbLogResourceTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['hal', 'dblog'];

  protected function setUp() {
    parent::setUp();
    // Enable REST API for the watchdog resource.
    $this->enableService('dblog');
  }

  /**
   * Writes a log messages and retrieves it via the REST API.
   */
  public function testWatchdog() {
    // Write a log message to the DB.
    $this->container->get('logger.channel.rest')->notice('Test message');
    // Get the ID of the written message.
    $id = db_query_range("SELECT wid FROM {watchdog} WHERE type = :type ORDER BY wid DESC", 0, 1, [':type' => 'rest'])
      ->fetchField();

    // Create a user account that has the required permissions to read
    // the watchdog resource via the REST API.
    $account = $this->drupalCreateUser(['restful get dblog']);
    $this->drupalLogin($account);

    $response = $this->httpRequest(Url::fromRoute('rest.dblog.GET.' . $this->defaultFormat, ['id' => $id, '_format' => $this->defaultFormat]), 'GET');
    $this->assertResponse(200);
    $this->assertHeader('content-type', $this->defaultMimeType);
    $log = Json::decode($response);
    $this->assertEqual($log['wid'], $id, 'Log ID is correct.');
    $this->assertEqual($log['type'], 'rest', 'Type of log message is correct.');
    $this->assertEqual($log['message'], 'Test message', 'Log message text is correct.');

    // Request an unknown log entry.
    $response = $this->httpRequest(Url::fromRoute('rest.dblog.GET.' . $this->defaultFormat, ['id' => 9999, '_format' => $this->defaultFormat]), 'GET');
    $this->assertResponse(404);
    $decoded = Json::decode($response);
    $this->assertEqual($decoded['message'], 'Log entry with ID 9999 was not found', 'Response message is correct.');

    // Make a bad request (a true malformed request would never be a route match).
    $response = $this->httpRequest(Url::fromRoute('rest.dblog.GET.' . $this->defaultFormat, ['id' => 0, '_format' => $this->defaultFormat]), 'GET');
    $this->assertResponse(400);
    $decoded = Json::decode($response);
    $this->assertEqual($decoded['message'], 'No log entry ID was provided', 'Response message is correct.');
  }

}
