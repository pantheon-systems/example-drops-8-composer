<?php

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the user admin listing if views is not enabled.
 *
 * @group user
 * @see user_admin_account()
 */
class UserAdminListingTest extends WebTestBase {

  /**
   * Tests the listing.
   */
  public function testUserListing() {
    $this->drupalGet('admin/people');
    $this->assertResponse(403, 'Anonymous user does not have access to the user admin listing.');

    // Create a bunch of users.
    $accounts = [];
    for ($i = 0; $i < 3; $i++) {
      $account = $this->drupalCreateUser();
      $accounts[$account->label()] = $account;
    }
    // Create a blocked user.
    $account = $this->drupalCreateUser();
    $account->block();
    $account->save();
    $accounts[$account->label()] = $account;

    // Create a user at a certain timestamp.
    $account = $this->drupalCreateUser();
    $account->created = 1363219200;
    $account->save();
    $accounts[$account->label()] = $account;
    $timestamp_user = $account->label();

    $rid_1 = $this->drupalCreateRole([], 'custom_role_1', 'custom_role_1');
    $rid_2 = $this->drupalCreateRole([], 'custom_role_2', 'custom_role_2');

    $account = $this->drupalCreateUser();
    $account->addRole($rid_1);
    $account->addRole($rid_2);
    $account->save();
    $accounts[$account->label()] = $account;
    $role_account_name = $account->label();

    // Create an admin user and look at the listing.
    $admin_user = $this->drupalCreateUser(['administer users']);
    $accounts[$admin_user->label()] = $admin_user;

    $accounts['admin'] = User::load(1);

    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/people');
    $this->assertResponse(200, 'The admin user has access to the user admin listing.');

    $result = $this->xpath('//table[contains(@class, "responsive-enabled")]/tbody/tr');
    $result_accounts = [];
    foreach ($result as $account) {
      $name = (string) $account->td[0]->span;
      $roles = [];
      if (isset($account->td[2]->div->ul)) {
        foreach ($account->td[2]->div->ul->li as $element) {
          $roles[] = (string) $element;
        }
      }
      $result_accounts[$name] = [
        'name' => $name,
        'status' => (string) $account->td[1],
        'roles' => $roles,
        'member_for' => (string) $account->td[3],
        'last_access' => (string) $account->td[4],
      ];
    }

    $this->assertFalse(array_keys(array_diff_key($result_accounts, $accounts)), 'Ensure all accounts are listed.');
    foreach ($result_accounts as $name => $values) {
      $this->assertEqual($values['status'] == t('active'), $accounts[$name]->status->value, 'Ensure the status is displayed properly.');
    }

    $expected_roles = ['custom_role_1', 'custom_role_2'];
    $this->assertEqual($result_accounts[$role_account_name]['roles'], $expected_roles, 'Ensure roles are listed properly.');

    $this->assertEqual($result_accounts[$timestamp_user]['member_for'], \Drupal::service('date.formatter')->formatTimeDiffSince($accounts[$timestamp_user]->created->value), 'Ensure the right member time is displayed.');

    $this->assertEqual($result_accounts[$timestamp_user]['last_access'], 'never', 'Ensure the last access time is "never".');
  }

}
