<?php
/**
 * Copyright (C) 2014 David Young
 *
 * Defines the interface for template compilers to implement
 */
namespace RDev\Views\Compilers;
use RDev\Views;
use RDev\Views\Cache;
use RDev\Views\Filters;

interface ICompiler
{
    /**
     * Gets the compiled template
     *
     * @param Views\ITemplate $template The template to render
     * @return string The compiled template
     * @throws \RuntimeException Thrown if there was an error compiling the template
     */
    public function compile(Views\ITemplate $template);

    /**
     * Executes a template function
     *
     * @param string $functionName The name of the template function to execute
     * @param array $args The list of arguments to pass into the template function
     * @return mixed The results of the template function
     * @throws \InvalidArgumentException Thrown if there is no function with the input name
     */
    public function executeTemplateFunction($functionName, array $args = []);

    /**
     * Gets a mapping of registered template function names to their callbacks
     *
     * @return array The mapping of function names to their callbacks
     */
    public function getTemplateFunctions();

    /**
     * Registers a sub-compiler
     *
     * @param SubCompilers\ISubCompiler $subCompiler The sub-compiler to register
     * @param int|null $priority The priority (1 is the highest) in which the sub-compiler is run
     *      If no priority is given, the sub-compiler will be executed in the order it was registered
     * @throw new \InvalidArgumentException Thrown if the sub-compiler is not callable or if the priority is invalid
     */
    public function registerSubCompiler(SubCompilers\ISubCompiler $subCompiler, $priority = null);

    /**
     * Registers a function that appears in a template
     * Useful for defining functions for consistent formatting in a template
     *
     * @param string $functionName The name of the function as it'll appear in the template
     * @param callable $function The function that returns the replacement string for the function in a template
     *      It must accept one parameter (the template's contents) and return a printable value
     */
    public function registerTemplateFunction($functionName, callable $function);
} 