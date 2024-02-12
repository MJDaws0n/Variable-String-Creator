<?php
// Function to convert a string to hex with padding
function stringToHex($string) {
    $hex = bin2hex($string);
    $paddedHex = implode(' ', str_split($hex, 2));
    return $paddedHex;
}

// Function to convert hex back to the original string
function hexToString($hex) {
    if($hex == '@'){
        return '0';
    }
    $hex = str_replace(' ', '', $hex);
    $string = @hex2bin($hex);
    return $string;
}

function processVariables($string, $variables, $variableDeclarer){
    $variableDeclarer = stringToHex($variableDeclarer);
    
    // This is for ignoring invalid variables
    $varNames = [];
    foreach ($variables as $varName => $varValue){
        array_push($varNames, $varName);
    }

    $updatedString = stringToHex($string);

    $updatedString = str_replace("$variableDeclarer 7b", "+VARSTART", $updatedString);
    $updatedString = str_replace("7d", "VAREND", $updatedString);
    $updatedString = str_replace("5c 5c", '/', $updatedString);
    $updatedString = str_replace("5c", '\\', $updatedString);
    $updatedString = str_replace("\ +VARSTART", "$variableDeclarer 7b", $updatedString);


    $stringArray = explode(" ", $updatedString);

    $updatedString = '';

    $lastType = 'end';
    $index = 0;

    $insideVar = false;

    foreach ($stringArray as $character){
        if($character == '+VARSTART'){
            if($lastType != 'end'){
                $character = "$variableDeclarer 7b";
            } else{
                $lastType = 'start';
            }
        }
        if($character == 'VAREND'){
            if($lastType != 'start'){
                $character = '7d';
            } else{
                $lastType = 'end';
            }
        }

        if ($character === "+VARSTART") {
            $insideVar = true;
        } else if ($character === "VAREND") {
            $insideVar = false;
        } else if ($insideVar) {
            $character = hexToString($character);
        }

        $updatedString .= $character.' ';
    }

    // Fixed issues with some \ duplication
    $updatedString = str_replace("/ +VARSTART", '| +VARSTART', $updatedString);
    while(strpos($updatedString, '/ |') !== false) {
        $updatedString = str_replace("/ |", '| |', $updatedString);
    }


    $updatedString = str_replace("+VARSTART", '', $updatedString);
    $updatedString = str_replace("VAREND", '', $updatedString);

    $updatedString = str_replace("|", '5c', $updatedString);

    $updatedString = str_replace("/ +VARSTART", '5c', $updatedString);
    $updatedString = str_replace("\\ +VARSTART", '', $updatedString);

    $updatedString = str_replace("/", '5c 5c', $updatedString);
    $updatedString = str_replace("\\", '5c', $updatedString);
    

    foreach ($variables as $varName => $varValue){
        $updatedString = str_replace(implode(' ', str_split($varName)), str_replace('30', '@', stringToHex($varValue)), $updatedString);
    }

    // Loop through and check if we have any invalid characters for conversion, this will be the undefined variables
    $stringArray = explode(" ", $updatedString);
    $updatedString = '';
    
    $currentlyInvalid = false;

    foreach ($stringArray as $character){
        if(hexToString($character) || $character == ''){
            if($currentlyInvalid){
                $currentlyInvalid = false;
                $updatedString .= '}';
            }
            $updatedString .= hexToString($character);
        } else{
            if($character == '@'){
                $updatedString .= '0';
            } else{
                if(!$currentlyInvalid){
                    $currentlyInvalid = true;
                    $updatedString .= hexToString($variableDeclarer).'{'.trim($character);
                } else{
                    $updatedString .= trim($character);
                }
            }
        }
    }

    return $updatedString;
}
