<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Rest\DisableCommand.
 */

namespace Drupal\Console\Command\Rest;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Command\Shared\RestTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\rest\Plugin\Type\ResourcePluginManager;

/**
 * @DrupalCommand(
 *     extension = "rest",
 *     extensionType = "module"
 * )
 */
class DisableCommand extends Command
{
    use CommandTrait;
    use RestTrait;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var ResourcePluginManager
     */
    protected $pluginManagerRest;

    /**
     * DisableCommand constructor.
     *
     * @param ConfigFactory         $configFactory
     * @param ResourcePluginManager $pluginManagerRest
     */
    public function __construct(
        ConfigFactory $configFactory,
        ResourcePluginManager $pluginManagerRest
    ) {
        $this->configFactory = $configFactory;
        $this->pluginManagerRest = $pluginManagerRest;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('rest:disable')
            ->setDescription($this->trans('commands.rest.disable.description'))
            ->addArgument(
                'resource-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.rest.debug.arguments.resource-id')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $resource_id = $input->getArgument('resource-id');
        $rest_resources = $this->getRestResources();
        $rest_resources_ids = array_merge(
            array_keys($rest_resources['enabled']),
            array_keys($rest_resources['disabled'])
        );

        if (!$resource_id) {
            $resource_id = $io->choice(
                $this->trans('commands.rest.disable.arguments.resource-id'),
                $rest_resources_ids
            );
        }

        $this->validateRestResource(
            $resource_id,
            $rest_resources_ids,
            $this->translator
        );
        $resources = \Drupal::service('entity_type.manager')
            ->getStorage('rest_resource_config')->loadMultiple();
        if ($resources[$this->getResourceKey($resource_id)]) {
            $routeBuilder = \Drupal::service('router.builder');
            $resources[$this->getResourceKey($resource_id)]->delete();
            // Rebuild routing cache.
            $routeBuilder->rebuild();

            $io->success(
                sprintf(
                    $this->trans('commands.rest.disable.messages.success'),
                    $resource_id
                )
            );
            return true;
        }
        $message = sprintf($this->trans('commands.rest.disable.messages.already-disabled'), $resource_id);
        $io->info($message);
        return true;
    }

    /**
     * The key used in the form.
     *
     * @param string $resource_id
     *   The resource ID.
     *
     * @return string
     *   The resource key in the form.
     */
    protected function getResourceKey($resource_id)
    {
        return str_replace(':', '.', $resource_id);
    }
}
