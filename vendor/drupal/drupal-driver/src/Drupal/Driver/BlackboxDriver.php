<?php

namespace Drupal\Driver;

/**
 * Implements DriverInterface.
 */
class BlackboxDriver extends BaseDriver {

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped() {
    // Assume the blackbox is always bootstrapped.
    return TRUE;
  }

}
