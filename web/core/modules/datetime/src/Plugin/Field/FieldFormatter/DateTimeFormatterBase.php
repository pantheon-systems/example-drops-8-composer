<?php

namespace Drupal\datetime\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Base class for 'DateTime Field formatter' plugin implementations.
 */
abstract class DateTimeFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Constructs a new DateTimeDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format entity storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'timezone_override' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['timezone_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone override'),
      '#description' => $this->t('The time zone selected here will always be used'),
      '#options' => system_time_zones(TRUE),
      '#default_value' => $this->getSetting('timezone_override'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($override = $this->getSetting('timezone_override')) {
      $summary[] = $this->t('Time zone: @timezone', ['@timezone' => $override]);
    }

    return $summary;
  }

  /**
   * Creates a formatted date value as a string.
   *
   * @param object $date
   *   A date object.
   *
   * @return string
   *   A formatted date string using the chosen format.
   */
  abstract protected function formatDate($date);

  /**
   * Sets the proper time zone on a DrupalDateTime object for the current user.
   *
   * A DrupalDateTime object loaded from the database will have the UTC time
   * zone applied to it.  This method will apply the time zone for the current
   * user, based on system and user settings.
   *
   * @see drupal_get_user_timezone()
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A DrupalDateTime object.
   */
  protected function setTimeZone(DrupalDateTime $date) {
    if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      // A date without time has no timezone conversion.
      $timezone = DATETIME_STORAGE_TIMEZONE;
    }
    else {
      $timezone = drupal_get_user_timezone();
    }
    $date->setTimeZone(timezone_open($timezone));
  }

  /**
   * Gets a settings array suitable for DrupalDateTime::format().
   *
   * @return array
   *   The settings array that can be passed to DrupalDateTime::format().
   */
  protected function getFormatSettings() {
    $settings = [];

    if ($this->getSetting('timezone_override') != '') {
      $settings['timezone'] = $this->getSetting('timezone_override');
    }

    return $settings;
  }

}
