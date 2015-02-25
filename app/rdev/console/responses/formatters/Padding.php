<?php
/**
 * Copyright (C) 2015 David Young
 * 
 * Defines the padding formatter
 */
namespace RDev\Console\Responses\Formatters;

class Padding
{
    /** @var bool Whether or not to pad after the string */
    private $padAfter = true;
    /** @var string The padding string */
    private $paddingString = " ";
    /** @var string The end-of-line character */
    private $eolChar = PHP_EOL;

    /**
     * Equalizes the length of each line in an array
     *
     * @param array $lines The lines to pad
     * @return int The max length
     */
    public function equalizeLineLengths(array &$lines)
    {
        $maxLength = 0;

        // Find the max length
        foreach($lines as $line)
        {
            if(count($line) > $maxLength)
            {
                $maxLength = count($line);
            }
        }

        // Equalize the lengths
        foreach($lines as &$line)
        {
            $line = array_pad($line, $maxLength, "");
        }

        return $maxLength;
    }

    /**
     * Formats lines of text so that the first item in each line has an equal amount of padding around it
     *
     * @param array $lines The list of lines, which can have the following formats:
     *      A string of items to pad,
     *      An array with two entries:  the string to pad and the text that comes after it
     * @param callable $callback The callback that returns a formatted single line of text
     * @return array A list of formatted lines
     * @throws \InvalidArgumentException Thrown if the input lines are not of the correct format
     */
    public function format(array $lines, callable $callback)
    {
        if(count($lines) > 0 && is_array($lines[0]))
        {
            $formattedLines = $this->formatLineArrays($lines, $this->padAfter, $this->paddingString);
        }
        else
        {
            $formattedLines = $this->formatLineStrings($lines, $this->padAfter, $this->paddingString);
        }

        $formattedText = "";

        foreach($formattedLines as $formattedLine)
        {
            $formattedText .= call_user_func($callback, $formattedLine) . $this->eolChar;
        }

        // Trim the excess separator
        $formattedText = preg_replace("/" . preg_quote($this->eolChar, "/") . "$/", "", $formattedText);

        return $formattedText;
    }

    /**
     * @return string
     */
    public function getEOLChar()
    {
        return $this->eolChar;
    }

    /**
     * Gets the maximum length of each column in a list of lines
     *
     * @param array $lines The lines to measure
     * @return array The max lengths for each column
     */
    public function getMaxLengths(array &$lines)
    {
        $numItems = $this->equalizeLineLengths($lines);
        $maxLengths = array_pad([], $numItems, 0);

        // Get the max lengths
        foreach($lines as &$line)
        {
            foreach($line as $index => &$item)
            {
                $item = trim($item);
                $maxLengths[$index] = max($maxLengths[$index], mb_strlen($item));
            }
        }

        return $maxLengths;
    }

    /**
     * @param string $eolChar
     */
    public function setEOLChar($eolChar)
    {
        $this->eolChar = $eolChar;
    }

    /**
     * @param boolean $padAfter
     */
    public function setPadAfter($padAfter)
    {
        $this->padAfter = $padAfter;
    }

    /**
     * @param string $paddingString
     */
    public function setPaddingString($paddingString)
    {
        $this->paddingString = $paddingString;
    }

    /**
     * Formats lines of text so that all items in each line has an equal amount of spacing around them
     *
     * @param array $lines The list of array lines
     * @param bool $addPaddingAfter True if we are going to add padding after the string, otherwise we'll add it before
     * @param string $paddingString The string to pad with
     * @return array The formatted lines
     */
    private function formatLineArrays(array $lines, $addPaddingAfter, $paddingString)
    {
        $maxLengths = $this->getMaxLengths($lines);
        $paddingType = $addPaddingAfter ? STR_PAD_RIGHT : STR_PAD_LEFT;

        // Format the lines
        foreach($lines as &$line)
        {
            foreach($line as $index => &$item)
            {
                $item = str_pad($item, $maxLengths[$index], $paddingString, $paddingType);
            }
        }

        return $lines;
    }

    /**
     * Formats lines of text so that each line has an equal amount of spacing around it
     *
     * @param array $lines The list of string lines
     * @param bool $addPaddingAfter True if we are going to add padding after the string, otherwise we'll add it before
     * @param string $paddingString The string to pad with
     * @return array The formatted lines
     */
    private function formatLineStrings(array $lines, $addPaddingAfter, $paddingString)
    {
        $maxLength = 0;

        // Get the max length
        foreach($lines as &$line)
        {
            $line = trim($line);
            $maxLength = max($maxLength, mb_strlen($line));
        }

        // Format the lines
        foreach($lines as &$line)
        {
            $line = str_pad($line, $maxLength, $paddingString, $addPaddingAfter ? STR_PAD_RIGHT : STR_PAD_LEFT);
        }

        return $lines;
    }
}