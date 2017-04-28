<?php

namespace Drupal\Console\Utils;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Site\Settings;
use Drupal\Console\Core\Utils\ConfigurationManager;

class Site
{
    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $cacheDirectory;

    /**
     * Site constructor.
     *
     * @param string               $appRoot
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        $appRoot,
        ConfigurationManager $configurationManager
    ) {
        $this->appRoot = $appRoot;
        $this->configurationManager = $configurationManager;
    }

    public function loadLegacyFile($legacyFile, $relative = true)
    {
        if ($relative) {
            $legacyFile = realpath(
                sprintf('%s/%s', $this->appRoot, $legacyFile)
            );
        }

        if (file_exists($legacyFile)) {
            include_once $legacyFile;

            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getStandardLanguages()
    {
        $standardLanguages = LanguageManager::getStandardLanguageList();
        $languages = [];
        foreach ($standardLanguages as $langcode => $standardLanguage) {
            $languages[$langcode] = $standardLanguage[0];
        }

        return $languages;
    }

    /**
     * @return array
     */
    public function getDatabaseTypes()
    {
        $this->loadLegacyFile('/core/includes/install.inc');
        $this->setMinimalContainerPreKernel();

        $driverDirectories = [
            $this->appRoot . '/core/lib/Drupal/Core/Database/Driver',
            $this->appRoot . '/drivers/lib/Drupal/Driver/Database'
        ];

        $driverDirectories = array_filter(
            $driverDirectories,
            function ($directory) {
                return is_dir($directory);
            }
        );

        $finder = new Finder();
        $finder->directories()
            ->in($driverDirectories)
            ->depth('== 0');

        $databases = [];
        foreach ($finder as $driver_folder) {
            if (file_exists($driver_folder->getRealpath() . '/Install/Tasks.php')) {
                $driver  = $driver_folder->getBasename();
                $installer = db_installer_object($driver);
                // Verify is database is installable
                if ($installer->installable()) {
                    $reflection = new \ReflectionClass($installer);
                    $install_namespace = $reflection->getNamespaceName();
                    // Cut the trailing \Install from namespace.
                    $driver_class = substr($install_namespace, 0, strrpos($install_namespace, '\\'));
                    $databases[$driver] = ['namespace' => $driver_class, 'name' =>$installer->name()];
                }
            }
        }

        return $databases;
    }

    protected function setMinimalContainerPreKernel()
    {
        // Create a minimal mocked container to support calls to t() in the pre-kernel
        // base system verification code paths below. The strings are not actually
        // used or output for these calls.
        $container = new ContainerBuilder();
        $container->setParameter('language.default_values', Language::$defaultValues);
        $container
            ->register('language.default', 'Drupal\Core\Language\LanguageDefault')
            ->addArgument('%language.default_values%');
        $container
            ->register('string_translation', 'Drupal\Core\StringTranslation\TranslationManager')
            ->addArgument(new Reference('language.default'));

        // Register the stream wrapper manager.
        $container
            ->register('stream_wrapper_manager', 'Drupal\Core\StreamWrapper\StreamWrapperManager')
            ->addMethodCall('setContainer', [new Reference('service_container')]);
        $container
            ->register('file_system', 'Drupal\Core\File\FileSystem')
            ->addArgument(new Reference('stream_wrapper_manager'))
            ->addArgument(Settings::getInstance())
            ->addArgument((new LoggerChannelFactory())->get('file'));

        \Drupal::setContainer($container);
    }

    public function getDatabaseTypeDriver($driver)
    {
        // We cannot use Database::getConnection->getDriverClass() here, because
        // the connection object is not yet functional.
        $task_class = "Drupal\\Core\\Database\\Driver\\{$driver}\\Install\\Tasks";
        if (class_exists($task_class)) {
            return new $task_class();
        } else {
            $task_class = "Drupal\\Driver\\Database\\{$driver}\\Install\\Tasks";
            return new $task_class();
        }
    }

    /**
     * @return mixed
     */
    public function getAutoload()
    {
        $autoLoadFile = $this->appRoot.'/autoload.php';

        return include $autoLoadFile;
    }

    /**
     * @return boolean
     */
    public function multisiteMode($uri)
    {
        if ($uri != 'default') {
            return true;
        }

        return false;
    }

    /**
     * @return boolean
     */
    public function validMultisite($uri)
    {
        $multiSiteFile = sprintf(
            '%s/sites/sites.php',
            $this->appRoot
        );

        if (file_exists($multiSiteFile)) {
            include $multiSiteFile;
        } else {
            return false;
        }

        if (isset($sites[$uri]) && is_dir($this->appRoot . "/sites/" . $sites[$uri])) {
            return true;
        }

        return false;
    }

    public function getCacheDirectory()
    {
        if ($this->cacheDirectory) {
            return $this->cacheDirectory;
        }

        $configFactory = \Drupal::configFactory();
        $siteId = $configFactory->get('system.site')
            ->get('uuid');
        $pathTemporary = $configFactory->get('system.file')
            ->get('path.temporary');
        $configuration = $this->configurationManager->getConfiguration();
        $cacheDirectory = $configuration->get('application.cache.directory')?:'';
        if ($cacheDirectory) {
            if (strpos($cacheDirectory, '/') != 0) {
                $cacheDirectory = $this->configurationManager
                    ->getApplicationDirectory() . '/' . $cacheDirectory;
            }
            $cacheDirectories[] = $cacheDirectory . '/' . $siteId . '/';
        }
        $cacheDirectories[] = sprintf(
            '%s/cache/%s/',
            $this->configurationManager->getConsoleDirectory(),
            $siteId
        );
        $cacheDirectories[] = $pathTemporary . '/console/cache/' . $siteId . '/';

        foreach ($cacheDirectories as $cacheDirectory) {
            if ($this->isValidDirectory($cacheDirectory)) {
                $this->cacheDirectory = $cacheDirectory;
                break;
            }
        }

        return $this->cacheDirectory;
    }

    private function isValidDirectory($path)
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists($path)) {
            return true;
        }
        try {
            $fileSystem->mkdir($path);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function cachedServicesFile()
    {
        return $this->getCacheDirectory().'/console.services.yml';
    }

    public function cachedServicesFileExists()
    {
        return file_exists($this->cachedServicesFile());
    }

    public function removeCachedServicesFile()
    {
        if ($this->cachedServicesFileExists()) {
            unlink($this->cachedServicesFile());
        }
    }
}
