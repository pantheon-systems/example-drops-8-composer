<?php

namespace Consolidation\AnnotatedCommand\Hooks\Dispatchers;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\Hooks\InitializeHookInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Call hooks
 */
class InitializeHookDispatcher extends HookDispatcher implements InitializeHookInterface
{
    public function initialize(
        InputInterface $input,
        AnnotationData $annotationData
    ) {
        $hooks = [
            HookManager::PRE_INITIALIZE,
            HookManager::INITIALIZE,
            HookManager::POST_INITIALIZE
        ];
        $providers = $this->getHooks($hooks, $annotationData);
        foreach ($providers as $provider) {
            $this->callInitializeHook($provider, $input, $annotationData);
        }
    }

    protected function callInitializeHook($provider, $input, AnnotationData $annotationData)
    {
        if ($provider instanceof InitializeHookInterface) {
            return $provider->initialize($input, $annotationData);
        }
        if (is_callable($provider)) {
            return $provider($input, $annotationData);
        }
    }
}
