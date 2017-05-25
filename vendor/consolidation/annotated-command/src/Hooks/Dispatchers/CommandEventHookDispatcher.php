<?php

namespace Consolidation\AnnotatedCommand\Hooks\Dispatchers;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Call hooks
 */
class CommandEventHookDispatcher extends HookDispatcher
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function callCommandEventHooks(ConsoleCommandEvent $event)
    {
        $hooks = [
            HookManager::PRE_COMMAND_EVENT,
            HookManager::COMMAND_EVENT,
            HookManager::POST_COMMAND_EVENT
        ];
        $commandEventHooks = $this->getHooks($hooks);
        foreach ($commandEventHooks as $commandEvent) {
            if (is_callable($commandEvent)) {
                $commandEvent($event);
            }
        }
    }
}
