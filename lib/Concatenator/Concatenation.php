<?php
/**
 * A simple library for file concatenation and dependency tracking,
 * (almost) compatible to Sprockets {@link http://getsprockets.org}
 *
 * @package   Concatenator
 * @author    Christoph Hochstrasser <c.hochstrasser@szene1.at>
 * @copyright Copyright (c) 2010, Szene1 Entertainment GmbH (http://szene1.at)
 * @license   New BSD License (see LICENSE.txt distributed with the package)
 * @link      http://github.com/CHH/concatenator
 */
class Concatenator_Concatenation extends SplTempFileObject
{
	public function saveTo($location)
	{
		if (!is_writable($location)) {
			throw new InvalidArgumentException("File {$location} is not writable.");
		}
		file_put_contents($location, $this->toString());
		return $this;
	}
	
	public function toString()
	{
		$this->rewind();
		ob_start();
		$this->fpassthru();
		$content = ob_get_clean();
		$this->rewind();
		return $content;
	}
	
	public function __toString()
	{
		return $this->toString();
	}
}
