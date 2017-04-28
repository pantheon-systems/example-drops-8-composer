<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\QueryCommand.
 *
 *
 * @TODO:
 *         - mysql -H option for html
 *         - mysql -X option for xml
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class QueryCommand extends Command
{
    use ConnectTrait;
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:query')
            ->setDescription($this->trans('commands.database.query.description'))
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                $this->trans('commands.database.query.arguments.query')
            )
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.query.arguments.database'),
                'default'
            )
            ->addOption('quick', '', InputOption::VALUE_NONE, $this->trans('commands.database.query.options.quick'))
            ->addOption('debug', '', InputOption::VALUE_NONE, $this->trans('commands.database.query.options.debug'))
            ->addOption('html', '', InputOption::VALUE_NONE, $this->trans('commands.database.query.options.html'))
            ->addOption('xml', '', InputOption::VALUE_NONE, $this->trans('commands.database.query.options.xml'))
            ->addOption('raw', '', InputOption::VALUE_NONE, $this->trans('commands.database.query.options.raw'))
            ->addOption('vertical', '', InputOption::VALUE_NONE, $this->trans('commands.database.query.options.vertical'))
            ->addOption('batch', '', InputOption::VALUE_NONE, $this->trans('commands.database.query.options.batch'))

            ->setHelp($this->trans('commands.database.query.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $query = $input->getArgument('query');
        $database = $input->getArgument('database');
        $learning = $input->getOption('learning');

        $databaseConnection = $this->resolveConnection($io, $database);

        $connection = sprintf(
            '%s -A --database=%s --user=%s --password=%s --host=%s --port=%s',
            $databaseConnection['driver'],
            $databaseConnection['database'],
            $databaseConnection['username'],
            $databaseConnection['password'],
            $databaseConnection['host'],
            $databaseConnection['port']
        );

        $args = explode(' ', $connection);
        $args[] = sprintf('--execute=%s', $query);

        $opts = ["quick", "debug", "html", "xml", "raw", "vertical", "batch"];
        array_walk(
            $opts, function ($opt) use ($input, &$args) {
                if ($input->getOption($opt)) {
                    switch ($opt) {
                    case "quick":
                        $args[] = "--quick";
                        break;
                    case "debug":
                        $args[] = "-T";
                        break;
                    case "html":
                        $args[] = "-H";
                        break;
                    case "xml":
                        $args[] = "-X";
                        break;
                    case "raw":
                        $args[] = "--raw";
                        break;
                    case "vertical":
                        $args[] = "-E";
                        break;
                    case "batch":
                        $args[] = "--batch";
                        break;
                    }
                }
            }
        );

        if ($learning) {
            $io->commentBlock(
                implode(" ", $args)
            );
        }

        $processBuilder = new ProcessBuilder([]);
        $processBuilder->setArguments($args);
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return 0;
    }
}
