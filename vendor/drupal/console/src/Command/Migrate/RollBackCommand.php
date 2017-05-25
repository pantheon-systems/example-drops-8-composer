<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Migrate\RollBackCommand
 */

namespace Drupal\Console\Command\Migrate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\MigrationTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\Console\Utils\MigrateExecuteMessageCapture;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * @DrupalCommand(
 *     extension = "migrate",
 *     extensionType = "module"
 * )
 */

class RollBackCommand extends Command
{
    use MigrationTrait;
    use CommandTrait;

    /**
     * @var MigrationPluginManagerInterface $pluginManagerMigration
     */
    protected $pluginManagerMigration;

    /**
     * RollBackCommand constructor.
     *
     * @param MigrationPluginManagerInterface $pluginManagerMigration
     */
    public function __construct(MigrationPluginManagerInterface $pluginManagerMigration)
    {
        $this->pluginManagerMigration = $pluginManagerMigration;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('migrate:rollback')
            ->setDescription($this->trans('commands.migrate.rollback.description'))
            ->addArgument('migration-ids', InputArgument::IS_ARRAY, $this->trans('commands.migrate.rollback.arguments.id'))
            ->addOption(
                'source-base_path',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.source-base_path')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $sourceBasepath = $input->getOption('source-base_path');
        $configuration['source']['constants']['source_base_path'] = rtrim($sourceBasepath, '/') . '/';
        // --migration-id prefix
        $migration_id = $input->getArgument('migration-ids');
        $migrations_list = array_keys($this->getMigrations($version_tag));
        // If migrations weren't provided finish execution
        if (empty($migration_id)) {
            return;
        }


        if (!in_array('all', $migration_id)) {
            $migration_ids = $migration_id;
        } else {
            $migration_ids = $migrations_list;
        }

        foreach ($migration_ids as  $migration) {
            if (!in_array($migration, $migrations_list)) {
                $io->warning(
                    sprintf(
                        $this->trans('commands.migrate.rollback.messages.not-available'),
                        $migration
                    )
                );
                continue;
            }
            $migration_service = $this->pluginManagerMigration->createInstance($migration, $configuration);
            if ($migration_service) {
                $messages = new MigrateExecuteMessageCapture();
                $executable = new MigrateExecutable($migration_service, $messages);

                $migration_status = $executable->rollback();
                switch ($migration_status) {
                case MigrationInterface::RESULT_COMPLETED:
                    $io->info(
                        sprintf(
                            $this->trans('commands.migrate.rollback.messages.processing'),
                            $migration
                        )
                    );
                    break;
                case MigrationInterface::RESULT_INCOMPLETE:
                    $io->info(
                        sprintf(
                            $this->trans('commands.migrate.execute.messages.importing-incomplete'),
                            $migration
                        )
                    );
                    break;
                case MigrationInterface::RESULT_STOPPED:
                    $io->error(
                        sprintf(
                            $this->trans('commands.migrate.execute.messages.import-stopped'),
                            $migration
                        )
                    );
                    break;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        // Get migrations
        $migrations_list = $this->getMigrations($version_tag);

        // --migration-id prefix
        $migration_id = $input->getArgument('migration-ids');


        if (!$migration_id) {
            $migrations_ids = [];

            while (true) {
                $migration_id = $io->choiceNoList(
                    $this->trans('commands.migrate.execute.questions.id'),
                    array_keys($migrations_list),
                    'all'
                );

                if (empty($migration_id) || $migration_id == 'all') {
                    // Only add all if it's the first option
                    if (empty($migrations_ids) && $migration_id == 'all') {
                        $migrations_ids[] = $migration_id;
                    }
                    break;
                } else {
                    $migrations_ids[] = $migration_id;
                }
            }

            $input->setArgument('migration-ids', $migrations_ids);
        }

        // --source-base_path
        $sourceBasepath = $input->getOption('source-base_path');
        if (!$sourceBasepath) {
            $sourceBasepath = $io->ask(
                $this->trans('commands.migrate.setup.questions.source-base_path'),
                ''
            );
            $input->setOption('source-base_path', $sourceBasepath);
        }
    }
}
