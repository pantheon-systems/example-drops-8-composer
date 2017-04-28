<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Locale\LanguageDeleteDebugCommand.
 */

namespace Drupal\Console\Command\Locale;

use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\LocaleTrait;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Utils\Site;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * @DrupalCommand(
 *     extension = "locale",
 *     extensionType = "module"
 * )
 */
class LanguageDeleteCommand extends Command
{
    use CommandTrait;
    use LocaleTrait;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * LoginUrlCommand constructor.
     *
     * @param Site                       $site
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param ModuleHandlerInterface     $moduleHandler
     */
    public function __construct(
        Site $site,
        EntityTypeManagerInterface $entityTypeManager,
        ModuleHandlerInterface $moduleHandler
    ) {
        $this->site = $site;
        $this->entityTypeManager = $entityTypeManager;
        $this->moduleHandler = $moduleHandler;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('locale:language:delete')
            ->setDescription($this->trans('commands.locale.language.delete.description'))
            ->addArgument(
                'language',
                InputArgument::REQUIRED,
                $this->trans('commands.locale.translation.status.arguments.language')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $moduleHandler = $this->moduleHandler;
        $moduleHandler->loadInclude('locale', 'inc', 'locale.translation');
        $moduleHandler->loadInclude('locale', 'module');

        $language = $input->getArgument('language');

        $languagesObjects = locale_translatable_language_list();
        $languages = $this->site->getStandardLanguages();

        if (isset($languagesObjects[$language])) {
            $languageEntity = $languagesObjects[$language];
        } elseif (array_search($language, $languages)) {
            $langcode = array_search($language, $languages);
            $languageEntity = $languagesObjects[$langcode];
        } else {
            $io->error(
                sprintf(
                    $this->trans('commands.locale.language.delete.messages.invalid-language'),
                    $language
                )
            );

            return 1;
        }

        try {
            $configurable_language_storage = $this->entityTypeManager->getStorage('configurable_language');
            $configurable_language_storage->load($languageEntity->getId())->delete();

            $io->info(
                sprintf(
                    $this->trans('commands.locale.language.delete.messages.language-deleted-successfully'),
                    $languageEntity->getName()
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
