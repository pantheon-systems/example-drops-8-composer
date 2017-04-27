<?php

namespace Drupal\Driver;

/**
 * Interface for discovery of sub-drivers.
 */
interface SubDriverFinderInterface {

  /**
   * Returns an array of paths in which to look for Drupal sub-drivers.
   *
   * @return array
   *   An array of paths in which to find sub-drivers.
   */
  public function getSubDriverPaths();

}
