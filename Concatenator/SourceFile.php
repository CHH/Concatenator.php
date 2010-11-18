<?php

class Concatenator_SourceFile extends SplFileObject
{
	public function current()
	{
		return new Concatenator_SourceLine($this, parent::current(), $this->key());
	}
}
