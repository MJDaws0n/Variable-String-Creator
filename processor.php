<?php

/**
 * Class StringVariableProcessor
 * 
 * Provides utilities for converting strings to hex and back,
 * and for processing variables within a string using hex encoding.
 */
class StringVariableProcessor
{
    /**
     * Converts a string to its hex representation, padded with spaces.
     *
     * @param string $string The input string.
     * @return string The hex representation of the string, space-separated.
     */
    public static function stringToHex(string $string): string
    {
        return implode(' ', str_split(bin2hex($string), 2));
    }

    /**
     * Converts a space-separated hex string back to the original string.
     *
     * @param string $hex The hex string to convert.
     * @return string The original decoded string.
     */
    public static function hexToString(string $hex): string
    {
        if ($hex === '@') {
            return '0';
        }
        return @hex2bin(str_replace(' ', '', $hex)) ?: '';
    }

    /**
     * Processes a string, replacing declared variables with their corresponding values.
     *
     * @param string $string The original string containing variables.
     * @param array $variables An associative array of variable names to values.
     * @param string $variableDeclarer A character or string used to declare a variable.
     * @return string The processed string with variables replaced.
     */
    public static function processVariables(string $string, array $variables, string $variableDeclarer): string
    {
        $variableDeclarerHex = self::stringToHex($variableDeclarer);
        $updatedString = self::stringToHex($string);

        $replacements = [
            "$variableDeclarerHex 7b" => '+VARSTART',
            '7d' => 'VAREND',
            '5c 5c' => '/',
            '5c' => '\\',
            "\ +VARSTART" => "$variableDeclarerHex 7b"
        ];

        $updatedString = strtr($updatedString, $replacements);
        $stringArray = explode(' ', $updatedString);

        $updatedString = '';
        $insideVar = false;
        $lastType = 'end';

        foreach ($stringArray as $char) {
            if ($char === '+VARSTART') {
                if ($lastType !== 'end') {
                    $char = "$variableDeclarerHex 7b";
                } else {
                    $lastType = 'start';
                    $insideVar = true;
                    continue;
                }
            }
            if ($char === 'VAREND') {
                if ($lastType !== 'start') {
                    $char = '7d';
                } else {
                    $lastType = 'end';
                    $insideVar = false;
                    continue;
                }
            }

            if ($insideVar) {
                $char = self::hexToString($char);
            }

            $updatedString .= $char . ' ';
        }

        $updatedString = self::cleanUpString($updatedString, $variableDeclarerHex);

        foreach ($variables as $varName => $varValue) {
            $updatedString = str_replace(
                implode(' ', str_split($varName)),
                str_replace('30', '@', self::stringToHex($varValue)),
                $updatedString
            );
        }

        return self::replaceInvalidChars($updatedString, $variableDeclarerHex);
    }

    /**
     * Cleans up escaped sequences in the string.
     *
     * @param string $string The input string.
     * @param string $variableDeclarerHex The hex value of the variable declarer.
     * @return string Cleaned up string.
     */
    private static function cleanUpString(string $string, string $variableDeclarerHex): string
    {
        $string = str_replace('/ +VARSTART', '| +VARSTART', $string);

        while (strpos($string, '/ |') !== false) {
            $string = str_replace('/ |', '| |', $string);
        }

        $cleanup = [
            '+VARSTART' => '',
            'VAREND' => '',
            '|' => '5c',
            '/ +VARSTART' => '5c',
            '\\ +VARSTART' => '',
            '/' => '5c 5c',
            '\\' => '5c'
        ];

        return strtr($string, $cleanup);
    }

    /**
     * Replaces invalid hex characters and undefined variables in the string.
     *
     * @param string $string The input string.
     * @param string $variableDeclarerHex The hex representation of the variable declarer.
     * @return string The final processed string.
     */
    private static function replaceInvalidChars(string $string, string $variableDeclarerHex): string
    {
        $stringArray = explode(' ', $string);
        $result = '';
        $currentlyInvalid = false;

        foreach ($stringArray as $char) {
            $decoded = self::hexToString($char);
            if ($decoded || $char === '') {
                if ($currentlyInvalid) {
                    $currentlyInvalid = false;
                    $result .= '}';
                }
                $result .= $decoded;
            } else {
                if ($char === '@') {
                    $result .= '0';
                } elseif (!$currentlyInvalid && $char !== '30') {
                    $currentlyInvalid = true;
                    $result .= self::hexToString($variableDeclarerHex) . '{' . trim($char);
                } elseif ($currentlyInvalid) {
                    $result .= trim($char);
                }
            }
        }

        return $result;
    }
}
