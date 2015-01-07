<?php
/**
 * Copyright (C) 2015 David Young
 * 
 * Defines the console commands container
 */
namespace RDev\Console\Commands;

class Commands
{
    /** @var ICommand[] The list of commands */
    private $commands = [];

    /**
     * Adds a command
     *
     * @param ICommand $command The command to add
     * @throws \InvalidArgumentException Thrown if a command with the input name already exists
     */
    public function add(ICommand $command)
    {
        if($this->has($command->getName()))
        {
            throw new \InvalidArgumentException("A command with name \"{$command->getName()}\" already exists");
        }

        $this->commands[$command->getName()] = $command;
    }

    /**
     * Gets the command with the input name
     *
     * @param string $name The name of the command to get
     * @return ICommand The command
     * @throws \InvalidArgumentException Thrown if no command with the input name exists
     */
    public function get($name)
    {
        if(!$this->has($name))
        {
            throw new \InvalidArgumentException("No command with name \"$name\" exists");
        }

        return $this->commands[$name];
    }

    /**
     * Gets all the commands
     *
     * @return ICommand[] The list of commands
     */
    public function getAll()
    {
        return array_values($this->commands);
    }

    /**
     * Checks if the input name has been added
     *
     * @param string $name The name of the command to look for
     * @return bool True if the command has been added, otherwise false
     */
    public function has($name)
    {
        return isset($this->commands[$name]);
    }
}