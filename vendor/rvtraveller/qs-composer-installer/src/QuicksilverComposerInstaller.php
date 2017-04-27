<?php

namespace rvtraveller\QuicksilverComposerInstaller;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class QuicksilverComposerInstaller extends LibraryInstaller
{


  /**
   * Replace vars in a path
   *
   * @param  string $path
   * @param  array  $vars
   * @return string
   */
  protected function templatePath($path, array $vars = array())
  {
    if (strpos($path, '{') !== false) {
      extract($vars);
      preg_match_all('@\{\$([A-Za-z0-9_]*)\}@i', $path, $matches);
      if (!empty($matches[1])) {
        foreach ($matches[1] as $var) {
          $path = str_replace('{$' . $var . '}', $$var, $path);
        }
      }
    }

    return $path;
  }

  /**
   * Search through a passed paths array for a custom install path.
   *
   * @param  array  $paths
   * @param  string $name
   * @param  string $type
   * @param  string $vendor = NULL
   * @return string
   */
  protected function mapCustomInstallPaths(array $paths, $name, $type, $vendor = NULL)
  {
    foreach ($paths as $path => $names) {
      if (in_array($name, $names) || in_array('type:' . $type, $names) || in_array('vendor:' . $vendor, $names)) {
        return $path;
      }
    }

    return false;
  }

  /**
   * Return the install path based on package type.
   *
   * @param  PackageInterface $package
   * @param  string           $frameworkType
   * @return string
   */
  public function getInstallPath(PackageInterface $package, $frameworkType = '')
  {
    $packageType = $package->getType();

    $prettyName = $package->getPrettyName();
    if (strpos($prettyName, '/') !== false) {
      list($vendor, $name) = explode('/', $prettyName);
    } else {
      $vendor = '';
      $name = $prettyName;
    }

    $availableVars = [
      'name' => $name,
      'vendor' => $vendor,
      'type' => $packageType
    ];

    $extra = $package->getExtra();
    if (!empty($extra['installer-name'])) {
      $availableVars['name'] = $extra['installer-name'];
    }

    if ($this->composer->getPackage()) {
      $extra = $this->composer->getPackage()->getExtra();
      if (!empty($extra['installer-paths'])) {
        $customPath = $this->mapCustomInstallPaths($extra['installer-paths'], $prettyName, $packageType, $vendor);
        if ($customPath !== false) {
          return $this->templatePath($customPath, $availableVars);
        }
      }
    }

    $locations = [
      'quicksilver-script' => 'web/private/scripts/quicksilver/{$name}/',
      'quicksilver-module' => 'web/private/scripts/quicksilver/{$name}/',
    ];

    return $this->templatePath($locations[$packageType], $availableVars);
  }


  /**
   * {@inheritDoc}
   */
  public function supports($packageType)
  {
    return in_array($packageType, ['quicksilver-script', 'quicksilver-module']);
  }

}
