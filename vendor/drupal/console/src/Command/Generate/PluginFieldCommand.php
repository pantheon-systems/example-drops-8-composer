<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;

class PluginFieldCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;
    use CommandTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * PluginFieldCommand constructor.
     *
     * @param Manager         $extensionManager
     * @param StringConverter $stringConverter
     * @param ChainQueue      $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        StringConverter $stringConverter,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->stringConverter = $stringConverter;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:field')
            ->setDescription($this->trans('commands.generate.plugin.field.description'))
            ->setHelp($this->trans('commands.generate.plugin.field.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'type-class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.field.options.type-class')
            )
            ->addOption(
                'type-label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.type-label')
            )
            ->addOption(
                'type-plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.type-plugin-id')
            )
            ->addOption(
                'type-description',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.type-type-description')
            )
            ->addOption(
                'formatter-class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.field.options.class')
            )
            ->addOption(
                'formatter-label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.formatter-label')
            )
            ->addOption(
                'formatter-plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.formatter-plugin-id')
            )
            ->addOption(
                'widget-class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.field.options.formatter-class')
            )
            ->addOption(
                'widget-label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.widget-label')
            )
            ->addOption(
                'widget-plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.widget-plugin-id')
            )
            ->addOption(
                'field-type',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.field-type')
            )
            ->addOption(
                'default-widget',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.default-widget')
            )
            ->addOption(
                'default-formatter',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.default-formatter')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $this->chainQueue
            ->addCommand(
                'generate:plugin:fieldtype', [
                '--module' => $input->getOption('module'),
                '--class' => $input->getOption('type-class'),
                '--label' => $input->getOption('type-label'),
                '--plugin-id' => $input->getOption('type-plugin-id'),
                '--description' => $input->getOption('type-description'),
                '--default-widget' => $input->getOption('default-widget'),
                '--default-formatter' => $input->getOption('default-formatter'),
                ],
                false
            );

        $this->chainQueue
            ->addCommand(
                'generate:plugin:fieldwidget', [
                '--module' => $input->getOption('module'),
                '--class' => $input->getOption('widget-class'),
                '--label' => $input->getOption('widget-label'),
                '--plugin-id' => $input->getOption('widget-plugin-id'),
                '--field-type' => $input->getOption('field-type'),
                ],
                false
            );
        $this->chainQueue
            ->addCommand(
                'generate:plugin:fieldformatter', [
                '--module' => $input->getOption('module'),
                '--class' => $input->getOption('formatter-class'),
                '--label' => $input->getOption('formatter-label'),
                '--plugin-id' => $input->getOption('formatter-plugin-id'),
                '--field-type' => $input->getOption('field-type'),
                ],
                false
            );

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery'], false);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        // --type-class option
        $typeClass = $input->getOption('type-class');
        if (!$typeClass) {
            $typeClass = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.type-class'),
                'ExampleFieldType'
            );
            $input->setOption('type-class', $typeClass);
        }

        // --type-label option
        $label = $input->getOption('type-label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.type-label'),
                $this->stringConverter->camelCaseToHuman($typeClass)
            );
            $input->setOption('type-label', $label);
        }

        // --type-plugin-id option
        $plugin_id = $input->getOption('type-plugin-id');
        if (!$plugin_id) {
            $plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.type-plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($typeClass)
            );
            $input->setOption('type-plugin-id', $plugin_id);
        }

        // --type-description option
        $description = $input->getOption('type-description');
        if (!$description) {
            $description = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.type-description'),
                'My Field Type'
            );
            $input->setOption('type-description', $description);
        }

        // --widget-class option
        $widgetClass = $input->getOption('widget-class');
        if (!$widgetClass) {
            $widgetClass = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.widget-class'),
                'ExampleWidgetType'
            );
            $input->setOption('widget-class', $widgetClass);
        }

        // --widget-label option
        $widgetLabel = $input->getOption('widget-label');
        if (!$widgetLabel) {
            $widgetLabel = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.widget-label'),
                $this->stringConverter->camelCaseToHuman($widgetClass)
            );
            $input->setOption('widget-label', $widgetLabel);
        }

        // --widget-plugin-id option
        $widget_plugin_id = $input->getOption('widget-plugin-id');
        if (!$widget_plugin_id) {
            $widget_plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.widget-plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($widgetClass)
            );
            $input->setOption('widget-plugin-id', $widget_plugin_id);
        }

        // --formatter-class option
        $formatterClass = $input->getOption('formatter-class');
        if (!$formatterClass) {
            $formatterClass = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.formatter-class'),
                'ExampleFormatterType'
            );
            $input->setOption('formatter-class', $formatterClass);
        }

        // --formatter-label option
        $formatterLabel = $input->getOption('formatter-label');
        if (!$formatterLabel) {
            $formatterLabel = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.formatter-label'),
                $this->stringConverter->camelCaseToHuman($formatterClass)
            );
            $input->setOption('formatter-label', $formatterLabel);
        }

        // --formatter-plugin-id option
        $formatter_plugin_id = $input->getOption('formatter-plugin-id');
        if (!$formatter_plugin_id) {
            $formatter_plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.formatter-plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($formatterClass)
            );
            $input->setOption('formatter-plugin-id', $formatter_plugin_id);
        }

        // --field-type option
        $field_type = $input->getOption('field-type');
        if (!$field_type) {
            $field_type = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.field-type'),
                $plugin_id
            );
            $input->setOption('field-type', $field_type);
        }

        // --default-widget option
        $default_widget = $input->getOption('default-widget');
        if (!$default_widget) {
            $default_widget = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.default-widget'),
                $widget_plugin_id
            );
            $input->setOption('default-widget', $default_widget);
        }

        // --default-formatter option
        $default_formatter = $input->getOption('default-formatter');
        if (!$default_formatter) {
            $default_formatter = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.default-formatter'),
                $formatter_plugin_id
            );
            $input->setOption('default-formatter', $default_formatter);
        }
    }
}
