<?php

namespace rvtraveller\QuicksilverComposerInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class QuicksilverComposerInstallerPlugin implements PluginInterface
{
  public function activate(Composer $composer, IOInterface $io)
  {
    // Strange autoloading problem on CircleCI
    if (!class_exists(QuickSilverComposerInstaller::class)) {
      include_once __DIR__ . '/QuicksilverComposerInstaller.php';
    }
    $installer = new QuickSilverComposerInstaller($io, $composer);
    $composer->getInstallationManager()->addInstaller($installer);
  }
}
