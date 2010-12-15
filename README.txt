Copyright (c) 2010 Szene1 Entertainment GmbH

Concatenator is a simple library for concatenating text files and managing their
dependencies via directives. It's primarly designed for concatenating Javascript
files, but may work for other text files too.

_______________________
R E Q U I R E M E N T S

- PHP 5.3+
- SPL (especially SplFileObject & SplTempFileObject)

Installation:
Put the contents of the "lib" folder into your library path.

_________
U S A G E

The Concatenator class acts as a facade for the functionality provided by the library.

The constructor takes an array of options. Concatenator supports most of the options
of the original Sprockets library (http://getsprockets.org/installation_and_usage).

Currently the "provide" directive and the installAssets() method are not implemented.
The "require" directive works as expected.

To get an concatenation, simply call "$concatenator->getConcatenation()". This returns
an instance of the Concatenator_Concatenation class which is a subclass of SplTempFileObject.

You can dump this concatenation to a file by calling saveTo($fileName) on the concatenation.
To write the contents of the concatenation to the output buffer, call fpassthru().

List of implemented options:
- source_files: List of files which should get processed
- load_path: Path(s) which get traversed if a file is requested 
from the load_path via //= require <file>
- write_infos (default=FALSE): Write number of processed files, processing time and Filesize
to the end of the concatenation. This may look like: /* Built 21 source file(s) in 0.154947 Seconds, 98202 Bytes */ 
- expand_paths (default=TRUE): Whether shell glob patterns (e.g. *.js) should be expanded to valid paths
- root
- strip_comments (default=TRUE): Should PDOC (http://pdoc.org) and single line
comments (//, /* .. */) get stripped off?

_____________
E X A M P L E: Build all files on every Request

Put this in a File:
<?php

define("JS_PATH", realpath("path/to/your/js/files"));

$options = array(
	"source_files" => array(JS_PATH . "/foo.js", JS_PATH . "/bar.js"),
	"load_path" => "path/to/your/vendor/libs",
	"write_infos" => true
);

$concatenator = new Concatenator($options);

// Send the concatenated output as Javascript to the client
header("Content-type: application/x-javascript");
$concatenator->getConcatenation()->fpassthru();
?>

and then put a script tag into your layout, requesting the previously created file:
<script type="text/javascript" src="/path/to/the/concatenating/script"></script>

Have fun!


