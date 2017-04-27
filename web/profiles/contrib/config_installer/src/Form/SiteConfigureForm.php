<?php

namespace Drupal\config_installer\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates the user 1 account.
 *
 * This is based on the install_configure_form provided by core.
 *
 * @see \Drupal\Core\Installer\Form\SiteConfigureForm
 */
class SiteConfigureForm extends FormBase {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * The module handler.
   *
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new SiteConfigureForm.
   *
   * @param string $root
   *   The app root.
   * @param string $site_path
   *   The site path.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($root, $site_path, UserStorageInterface $user_storage, StateInterface $state, ModuleHandlerInterface $module_handler) {
    $this->root = $root;
    $this->sitePath = $site_path;
    $this->userStorage = $user_storage;
    $this->state = $state;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('app.root'),
      $container->get('site.path'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('state'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_installer_site_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Configure site');

    // Warn about settings.php permissions risk.
    $settings_file = $this->sitePath . '/settings.php';
    // Check that $_POST is empty so we only show this message when the form is
    // first displayed, not on the next page after it is submitted. We do not
    // want to repeat it multiple times because it is a general warning that is
    // not related to the rest of the installation process; it would also be
    // especially out of place on the last page of the installer, where it would
    // distract from the message that the Drupal installation has completed
    // successfully.
    $post_params = $this->getRequest()->request->all();
    if (empty($post_params)) {
      $original_profile_name = _config_installer_get_original_install_profile();
      if ($original_profile_name) {
        $settings['settings']['install_profile'] = (object) [
          'value' => $original_profile_name,
          'required' => TRUE,
        ];
        drupal_rewrite_settings($settings);
      }

      if (!drupal_verify_install_file($this->root . '/' . $settings_file, FILE_EXIST | FILE_READABLE | FILE_NOT_WRITABLE) || !drupal_verify_install_file($this->root . '/' . $this->sitePath, FILE_NOT_WRITABLE, 'dir')) {
        drupal_set_message(t('All necessary changes to %dir and %file have been made, so you should remove write permissions to them now in order to avoid security risks. If you are unsure how to do so, consult the <a href="@handbook_url">online handbook</a>.', [
          '%dir' => $this->sitePath,
          '%file' => $settings_file,
          '@handbook_url' => 'http://drupal.org/server-permissions',
        ]), 'warning');
      }
    }

    $form['#attached']['library'][] = 'system/drupal.system';

    $form['admin_account'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Site maintenance account'),
    ];
    $form['admin_account']['account']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t('Spaces are allowed; punctuation is not allowed except for periods, hyphens, and underscores.'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['username']],
    ];
    $form['admin_account']['account']['pass'] = [
      '#type' => 'password_confirm',
      '#required' => TRUE,
      '#size' => 25,
    ];
    $form['admin_account']['account']['#tree'] = TRUE;
    $form['admin_account']['account']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    ];

    // Use default drush options if available whilst running a site install.
    if (function_exists('drush_get_option') && function_exists('drush_generate_password')) {
      $form['admin_account']['account']['name']['#default_value'] = drush_get_option('account-name', 'admin');
      $form['admin_account']['account']['pass']['#type'] = 'textfield';
      $form['admin_account']['account']['pass']['#default_value'] = drush_get_option('account-pass', drush_generate_password());
      $form['admin_account']['account']['mail']['#default_value'] = drush_get_option('account-mail', 'admin@example.com');
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#weight' => 15,
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($error = user_validate_name($form_state->getValue(['account', 'name']))) {
      $form_state->setErrorByName('account][name', $error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $account_values = $form_state->getValue('account');

    // We precreated user 1 with placeholder values. Let's save the real values.
    $account = $this->userStorage->load(1);
    $account->init = $account->mail = $account_values['mail'];
    $account->roles = $account->getRoles();
    $account->activate();
    $account->timezone = $form_state->getValue('date_default_timezone');
    $account->pass = $account_values['pass'];
    $account->name = $account_values['name'];
    $account->save();

    // Record when this install ran.
    $this->state->set('install_time', $_SERVER['REQUEST_TIME']);
  }

}
