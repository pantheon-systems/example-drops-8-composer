<?php
/**
 * @file
 * Contains \Drupal\Console\Command\User\CreateCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\user\Entity\User;
use Drupal\Console\Core\Style\DrupalStyle;

class CreateCommand extends Command
{
    use CommandTrait;
    use ConfirmationTrait;

    /**
     * @var Connection
     */
    protected $database;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var DateFormatterInterface
     */
    protected $dateFormatter;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * CreateCommand constructor.
     *
     * @param Connection                 $database
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param DateFormatterInterface     $dateFormatter
     * @param DrupalApi                  $drupalApi
     */
    public function __construct(
        Connection $database,
        EntityTypeManagerInterface $entityTypeManager,
        DateFormatterInterface $dateFormatter,
        DrupalApi $drupalApi
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entityTypeManager;
        $this->dateFormatter = $dateFormatter;
        $this->drupalApi = $drupalApi;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription($this->trans('commands.user.create.description'))
            ->setHelp($this->trans('commands.user.create.help'))
            ->addArgument('username', InputArgument::OPTIONAL, $this->trans('commands.user.create.options.username'))
            ->addArgument('password', InputArgument::OPTIONAL, $this->trans('commands.user.create.options.password'))
            ->addOption('roles', null, InputOption::VALUE_OPTIONAL, $this->trans('commands.user.create.options.roles'))
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, $this->trans('commands.user.create.options.email'))
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, $this->trans('commands.user.create.options.status'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $roles = $input->getOption('roles');
        $email = $input->getOption('email');
        $status = $input->getOption('status');

        $user = $this->createUser($username, $password, $roles, $email, $status);

        $tableHeader = ['Field', 'Value'];

        $tableFields = [
            $this->trans('commands.user.create.messages.user-id'),
            $this->trans('commands.user.create.messages.username'),
            $this->trans('commands.user.create.messages.password'),
            $this->trans('commands.user.create.messages.email'),
            $this->trans('commands.user.create.messages.roles'),
            $this->trans('commands.user.create.messages.created'),
            $this->trans('commands.user.create.messages.status'),
        ];

        if ($user['success']) {
            $tableData = array_map(
                function ($field, $value) {
                    return [$field, $value];
                },
                $tableFields,
                $user['success']
            );

            $io->table($tableHeader, $tableData);
            $io->success(
                sprintf(
                    $this->trans('commands.user.create.messages.user-created'),
                    $user['success']['username']
                )
            );

            return 0;
        }

        if ($user['error']) {
            $io->error($user['error']['error']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $username = $input->getArgument('username');
        while (!$username) {
            $username = $io->askEmpty(
                $this->trans('commands.user.create.questions.username'),
                null
            );
        }
        $input->setArgument('username', $username);

        $password = $input->getArgument('password');
        if (!$password) {
            $password = $io->askEmpty(
                $this->trans('commands.user.create.questions.password'),
                null
            );
        }
        $input->setArgument('password', $password);

        $roles = $input->getOption('roles');
        if (!$roles) {
            $systemRoles = $this->drupalApi->getRoles(false, false, false);
            $roles = $io->choice(
                $this->trans('commands.user.create.questions.roles'),
                array_values($systemRoles),
                null,
                true
            );

            $roles = array_map(
                function ($role) use ($systemRoles) {
                    return array_search($role, $systemRoles);
                },
                $roles
            );

            $input->setOption('roles', $roles);
        }

        $email = $input->getOption('email');
        if (!$email) {
            $email = $io->askEmpty(
                $this->trans('commands.user.create.questions.email'),
                null
            );
        }
        $input->setOption('email', $email);

        $status = $input->getOption('status');
        if (!$status) {
            $status = $io->choice(
                $this->trans('commands.user.create.questions.status'),
                [0, 1],
                1
            );
        }
        $input->setOption('status', $status);
    }

    private function createUser($username, $password, $roles, $email = null, $status = null)
    {
        $password = $password?:$this->generatePassword();
        $user = User::create(
            [
                'name' => $username,
                'mail' => $email ?: $username . '@example.com',
                'pass' => $password,
                'status' => $status,
                'roles' => $roles,
                'created' => REQUEST_TIME,
            ]
        );

        $result = [];

        try {
            $user->save();

            $result['success'] = [
                'user-id' => $user->id(),
                'username' => $user->getUsername(),
                'password' => $password,
                'email' => $user->getEmail(),
                'roles' => implode(', ', $roles),
                'created' => $this->dateFormatter->format(
                    $user->getCreatedTime(),
                    'custom',
                    'Y-m-d h:i:s'
                ),
                'status' => $status

            ];
        } catch (\Exception $e) {
            $result['error'] = [
                'vid' => $user->id(),
                'name' => $user->get('name'),
                'error' => 'Error: ' . get_class($e) . ', code: ' . $e->getCode() . ', message: ' . $e->getMessage()
            ];
        }

        return $result;
    }

    private function generatePassword()
    {
        $length = mt_rand(8, 16);
        $str = '';

        for ($i = 0; $i < $length; $i++) {
            $str .= chr(mt_rand(32, 126));
        }

        return $str;
    }
}
