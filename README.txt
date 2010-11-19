Copyright (c) 2010 Szene1 Entertainment GmbH

Concatenator is a simple library for concatenating text files and managing their
dependencies via directives. It's tested with PHP 5.3, but may work on 5.2 also since it does not rely on any
of 5.3's language features. It relies heavily on SplFileObject and SplTempFileObject.

Installation:
Put the contents of the "lib" folder into your library path.

Usage:

For best performance you shoud rely on an autoloader which loads files by their absolute
filenames instead of the PHP include paths.

Example: 
function __autoload($class)
{
	require_once YOUR_LIBRARY_PATH . DIRECTORY_SEPARATOR . str_replace(array("_", "\\"), DIRECTORY_SEPARATOR, $class) . ".php";
}

The Concatenator class acts as a facade for the functionality provided by the library.
To do a new concatenation just instantiate a new Concatenator object:

<?php
$concatenator = new Concatenator($options);
?>

The constructor takes an array of options. Concatenator supports most of the options
of the original Sprockets library (http://getsprockets.org/installation_and_usage).

Currently the "provide" directive and the installAssets() method are not implemented.
The "require" directive" works as expected.

To get an concatenation, simply call "$concatenator->getConcatenation()". This returns
an instance of the Concatenator_Concatenation class which is a subclass of SplTempFileObject.

You can dump this concatenation to a file by calling saveTo($fileName) on the concatenation.
To write the contents of the concatenation to the output buffer, call fpassthru().
