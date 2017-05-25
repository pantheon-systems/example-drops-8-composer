<?php

namespace spec\Drupal\Driver\Cores;

use Drupal\Component\Utility\Random;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class Drupal8Spec extends ObjectBehavior
{
    function let(Random $random)
    {
        $this->beConstructedWith('path', 'http://www.example.com', $random);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\Driver\Cores\Drupal8');
    }

    function it_should_return_a_random_generator()
    {
        $this->getRandom()->shouldBeAnInstanceOf('Drupal\Component\Utility\Random');
    }
}
