# Variable-String-Creator
Allows you to create strings that include variables and will process them returning the new string with the variable values in place of the variables.

## Variable Names
Variable names can only contain a-z A-Z 0-9 _ and -. Other characters may work, but may cause issues so it's not recomended.

## Escaping
Strings can even be escaped by using a \\. It works like any normal system so two \\\\ would cancel each other out.

## Undefined Varaibles
Using an undefined variable creates no errors, and will just give as was written. However when entering a backslash before a variable that does not exist, you will still escape processing it. This means that you will double the ammount of \ before an undefined variable. Confusing I know, you most likely will not notice anything when using it.

## How it works
It works by converting the string to Hexadecimal to allow for the string to be easily handled as certan characters can never appear in hexadecimal. Now thinking about it, I could of probably done it much easier by just replacing all occurrences of the variable then done some things with that, but, this works and it not to long so I cannot complain.

## Usage
Simply Include or Require this file, and use it, then run this:
```php
$processed = StringVariableProcessor::processVariables($string, $variables, $variableDeclarer);
```
Obviously replace these values with the correct values. This is an example on how it could look:
```php
$string = 'Hello {{name}}!';
$variables = ['name' => 'Max'];
$variableDeclarer = '{{';

$processed = StringVariableProcessor::processVariables($string, $variables, $variableDeclarer);
echo $processed;
```

## Practical use
This might be useful making your own website and wanting to change certain string to variables that ran through in PHP. This is slighly similar to [wordpress shortcodes](https://codex.wordpress.org/Shortcode_API) just more basic.
