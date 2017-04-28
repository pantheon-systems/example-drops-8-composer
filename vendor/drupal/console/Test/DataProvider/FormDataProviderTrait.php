<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class FormDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait FormDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();
        
        return [
          ['Foo', 'foo' . rand(), 'id' . rand(), null, null, 'FormBase', '/form/path', true, 'foo', 'system.admin_config_development', 'foo']
        ];
    }
}
