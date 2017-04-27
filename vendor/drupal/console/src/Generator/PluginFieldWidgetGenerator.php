<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginFieldWidgetGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginFieldWidgetGenerator extends Generator
{
    /**
     * PluginFieldWidgetGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Generator Plugin Field Formatter.
     *
     * @param string $module     Module name
     * @param string $class_name Plugin Class name
     * @param string $label      Plugin label
     * @param string $plugin_id  Plugin id
     * @param string $field_type Field type this widget supports
     */
    public function generate($module, $class_name, $label, $plugin_id, $field_type)
    {
        $parameters = [
            'module' => $module,
            'class_name' => $class_name,
            'label' => $label,
            'plugin_id' => $plugin_id,
            'field_type' => $field_type,
        ];

        $this->renderFile(
            'module/src/Plugin/Field/FieldWidget/fieldwidget.php.twig',
            $this->extensionManager->getPluginPath($module, 'Field/FieldWidget') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
