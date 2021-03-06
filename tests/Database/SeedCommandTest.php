<?php

use Illuminate\Container\Container;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Seeder;

class SeedCommandTest extends PHPUnit_Framework_TestCase
{
    public function testFire()
    {
        $seeder = Mockery::mock(Seeder::class);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldReceive('__invoke')->once();

        $resolver = Mockery::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = Mockery::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('make')->with('DatabaseSeeder')->andReturn($seeder);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        // call run to set up IO, then fire manually.
        $command->run(new Symfony\Component\Console\Input\ArrayInput(['--force' => true, '--database' => 'sqlite']), new Symfony\Component\Console\Output\NullOutput);
        $command->fire();

        $container->shouldHaveReceived('call')->with([$command, 'fire']);
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}
