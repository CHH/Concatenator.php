<?php

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
