<?php

namespace Drupal\user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Provides the user permissions administration form for a specific role.
 */
class UserPermissionsRoleSpecificForm extends UserPermissionsForm {

  /**
   * The specific role for this form.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $userRole;

  /**
   * {@inheritdoc}
   */
  protected function getRoles() {
    return [$this->userRole->id() => $this->userRole];
  }

  /**
   * {@inheritdoc}
   *
   * @param string $role_id
   *   The user role ID used for this form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RoleInterface $user_role = NULL) {
    $this->userRole = $user_role;
    return parent::buildForm($form, $form_state);
  }

}
