<?php

namespace spec\Drupal\Driver\Exception;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BootstrapExceptionSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('Failed to bootstrap!');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\Driver\Exception\BootstrapException');
    }
}
