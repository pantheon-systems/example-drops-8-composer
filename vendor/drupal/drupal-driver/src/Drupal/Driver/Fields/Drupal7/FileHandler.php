<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * File field handler for Drupal 7.
 */
class FileHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   *
   * Specify files in file fields by their filename.
   */
  public function expand($values) {
    $return = array();

    foreach ($values as $value) {
      $query = new \EntityFieldQuery();

      $query->entityCondition('entity_type', 'file')
        ->propertyCondition('filename', $value)
        ->propertyOrderBy('timestamp', 'DESC')
        ->range(0, 1);

      $result = $query->execute();

      if (!empty($result['file'])) {
        $files = entity_load('file', array_keys($result['file']));
        $file = current($files);

        $return[$this->language][] = array(
          'filename' => $file->filename,
          'uri' => $file->uri,
          'fid' => $file->fid,
          'display' => 1,
        );
      }
      else {
        throw new \Exception(sprintf('File with filename "%s" not found.', $value));
      }
    }

    return $return;
  }

}
