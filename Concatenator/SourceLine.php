<?php

class Concatenator_SourceLine
{
	protected $sourceFile;
	protected $line;
	protected $lineNumber;
	
	protected $comment;
	protected $command;
	
	const COMMAND_START = "=";
	
	public function __construct(Concatenator_SourceFile $sourceFile, $line, $lineNumber)
	{
		$this->sourceFile = $sourceFile;
		$this->line       = $line;
		$this->lineNumber = $lineNumber;
	}
	
	public function isComment()
	{
		return substr(trim($this->line), 0, 2) === '//';
	}
	
	public function isRequire()
	{
		list($commandName, $argument) = $this->parseCommand();
		return $commandName === 'require';
	}
	
	public function getRequire()
	{
		if (!$this->isRequire()) return;
		list($commandName, $argument) = $this->parseCommand();
		return $argument;
	}
	
	public function isProvide()
	{
		list($commandName, $argument) = $this->parseCommand();
		return $commandName === 'provide';
	}
	
	public function getProvide()
	{
		if (!$this->isProvide()) return;
		list($commandName, $argument) = $this->parseCommand();
		return $argument;
	}
	
	public function getComment()
	{
		if ($this->comment !== null) {
			return $this->comment;
		}
		if (!$this->isComment()) {
			return null;
		}
		return $this->comment = trim(substr(trim($this->line), 2));
	}
	
	public function beginsPdocComment()
	{
		return substr(trim($this->line), 0, 3) == '/**';
	}
	
	public function endsPdocComment()
	{
		return substr(trim($this->line), 0, 3) == '**/';
	}
	
	public function beginsMultilineComment()
	{
		return substr(trim($this->line), 0, 2) == '/*';
	}
	
	public function endsMultilineComment()
	{
		return substr(trim($this->line), 0, 2) == '*/';
	}
	
	protected function parseCommand()
	{
		if ($this->command !== null) {
			return $this->command;
		}
		
		if (!$this->isComment()) {
			return null;
		}
		$comment = $this->getComment();
		if (substr($comment, 0, 1) !== static::COMMAND_START) {
			return null;
		}
		$command = trim(substr($comment, 1));
		$parts = explode(" ", $command);
		
		return $this->command = array($parts[0], $parts[1]);
	}
	
	public function toString()
	{
		return $this->line;
	}
	
	public function __toString()
	{
		return $this->line;
	}
	
	public function interpolateConstants(Array $constants)
	{
		$result = preg_match_all('/<%=(.*?)%>/', $this->line, $matches, PREG_SET_ORDER);
		
		if (!$result) return;
		
		foreach ($matches as $match) {
			list($replace, $constant) = $match;
		
			$constant = trim($constant);
		
			if (!isset($constants[$constant])) continue;
		
			$this->line = str_replace($replace, (string) $constants[$constant], $this->line);
		}
		
		return $this;
	}
}
