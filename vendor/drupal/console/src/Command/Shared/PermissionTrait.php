<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\PermissionsTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;

trait PermissionTrait
{
    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function permissionQuestion(DrupalStyle $output)
    {
        $permissions = [];
        $boolOrNone = ['true','false','none'];
        while (true) {
            $permission = $output->ask(
                $this->trans('commands.generate.permission.questions.permission'),
                'access content'
            );
            $title = $output->ask(
                $this->trans('commands.generate.permission.questions.title'),
                'Access content'
            );
            $description = $output->ask(
                $this->trans('commands.generate.permission.questions.description'),
                'Allow access to my content'
            );
            $restrictAccess = $output->choiceNoList(
                $this->trans('commands.generate.permission.questions.restrict-access'),
                $boolOrNone,
                'none'
            );

            $permission = $this->stringConverter->camelCaseToLowerCase($permission);
            $title = $this->stringConverter->anyCaseToUcFirst($title);

            array_push(
                $permissions,
                [
                    'permission' => $permission,
                    'title' => $title,
                    'description' => $description,
                    'restrict_access' => $restrictAccess,
                ]
            );

            if (!$output->confirm(
                $this->trans('commands.generate.permission.questions.add'),
                true
            )
            ) {
                break;
            }
        }

        return $permissions;
    }
}
