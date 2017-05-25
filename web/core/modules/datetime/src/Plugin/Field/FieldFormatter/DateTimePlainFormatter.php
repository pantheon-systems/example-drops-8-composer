<?php

namespace Drupal\datetime\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Plugin implementation of the 'Plain' formatter for 'datetime' fields.
 *
 * @FieldFormatter(
 *   id = "datetime_plain",
 *   label = @Translation("Plain"),
 *   field_types = {
 *     "datetime"
 *   }
 *)
 */
class DateTimePlainFormatter extends DateTimeFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $output = '';
      if (!empty($item->date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item->date;

        if ($this->getFieldSetting('datetime_type') == 'date') {
          // A date without time will pick up the current time, use the default.
          datetime_date_default_time($date);
        }
        $this->setTimeZone($date);

        $output = $this->formatDate($date);
      }
      $elements[$delta] = [
        '#cache' => [
          'contexts' => [
            'timezone',
          ],
        ],
        '#markup' => $output,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date) {
    $format = $this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE ? DATETIME_DATE_STORAGE_FORMAT : DATETIME_DATETIME_STORAGE_FORMAT;
    $timezone = $this->getSetting('timezone_override');
    return $this->dateFormatter->format($date->getTimestamp(), 'custom', $format, $timezone != '' ? $timezone : NULL);
  }

}
