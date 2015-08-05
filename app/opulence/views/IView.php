<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Defines the interface for all views to implement
 */
namespace Opulence\Views;

interface IView
{
    /** The directive delimiter */
    const DELIMITER_TYPE_DIRECTIVE = 1;
    /** The sanitized tag delimiter */
    const DELIMITER_TYPE_SANITIZED_TAG = 2;
    /** The unsanitized tag delimiter */
    const DELIMITER_TYPE_UNSANITIZED_TAG = 3;

    /**
     * Gets the uncompiled contents
     *
     * @return string The uncompiled contents
     */
    public function getContents();

    /**
     * Gets the open and close delimiters for a particular type
     *
     * @param mixed $type The type of delimiter to get
     * @return array An array containing the open and close delimiters
     */
    public function getDelimiters($type);

    /**
     * Gets the parent view if there is one
     *
     * @return IView|null The parent view if there is one, otherwise null
     */
    public function getParent();

    /**
     * Gets the contents of a view part
     *
     * @param string $name The name of the view part to get
     * @return string The contents of the view part
     */
    public function getPart($name);

    /**
     * Gets the list of view parts
     *
     * @return array The part name => content mappings
     */
    public function getParts();

    /**
     * Gets the path of the raw view
     *
     * @return string The path of the raw view
     */
    public function getPath();

    /**
     * Gets the value for a variable
     *
     * @param string $name The name of the variable to get
     * @return mixed|null The value of the variable if it exists, otherwise null
     */
    public function getVar($name);

    /**
     * Gets the list of variables defined in this view
     *
     * @return array The variable name => value mappings
     */
    public function getVars();

    /**
     * Sets the uncompiled contents of the view
     *
     * @param string $contents The uncompiled contents
     */
    public function setContents($contents);

    /**
     * Sets the values for a delimiter type
     *
     * @param mixed $type The type of delimiter to set
     * @param array $values An array containing the open and close delimiter values
     */
    public function setDelimiters($type, array $values);

    /**
     * Sets the parent of this view
     *
     * @param IView $parent The parent of this view
     */
    public function setParent(IView $parent);

    /**
     * Sets the content of a view part
     *
     * @param string $name The name of the part to set
     * @param string $content The content of the part
     */
    public function setPart($name, $content);

    /**
     * Sets multiple parts' contents in the view
     *
     * @param array $namesToContents The mapping of part names to their respective values
     */
    public function setParts(array $namesToContents);

    /**
     * Sets the path of the raw view
     *
     * @param string $path The path of the raw view
     */
    public function setPath($path);

    /**
     * Sets the value for a variable in the view
     *
     * @param string $name The name of the variable whose value we're setting
     *      For example, if we are setting the value of a variable named "$email" in the view, pass in "email"
     * @param mixed $value The value of the variable
     */
    public function setVar($name, $value);

    /**
     * Sets multiple variables' values in the view
     *
     * @param array $namesToValues The mapping of variable names to their respective values
     */
    public function setVars(array $namesToValues);

}