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

/**
 * Require Spyc (Simple PHP YAML Class) YAML Parser for parsing constants configs
 */
require_once "Concatenator/spyc.php";

/**
 * A simple file concatenator and file dependency manager
 * 
 * @package Concatenator
 */
class Concatenator
{
	protected $loadPath    = array();
	protected $sourceFiles = array();
	protected $provide     = array();
	
	protected $root = ".";	
	
	protected $assetRoot;
	
	protected $stripComments = true;
	
	protected $constants;
	
	public function __construct(Array $options = array())
	{
		$this->setOptions($options);
	}
	
	public function getConcatenation()
	{
		$concatenation = new Concatenator_Concatenation();
		foreach ($this->sourceFiles as $file) {
			$this->addFile($file, $concatenation);
		}
		$concatenation->rewind();
		return $concatenation;
	}
	
	protected function addFile($file, Concatenator_Concatenation $concatenation)
	{
		if (substr($file, -3, 3) !== '.js') {
			$file .= '.js';
		}
		
		$file = $this->getRealpath($file);
		$file = new Concatenator_SourceFile($file);
		
		$constants          = $this->readConstants();
		$inMultilineComment = false;
		$inPdocComment      = false;
		
		foreach ($file as $line) {
			$line->interpolateConstants($constants);
			
			if ($line->isProvide()) {
				$argument = trim($line->getProvide(), '"');
				$this->provide[] = $argument;
				
			} else if ($line->isRequire()) {
				$argument = $line->getRequire();
				
				$searchLoadPath = substr($argument, 0, 1) === '<';
					
				$argument = trim($argument, '<>"');
				
				if (substr($argument, -3, 3) !== '.js') {
					$argument .= '.js';
				}
				
				$requiredFile = $searchLoadPath 
					? $this->getRealpath($argument) 
					: realpath(dirname($file) . DIRECTORY_SEPARATOR . $argument);
				
				if ($requiredFile) {	
					$this->addFile($requiredFile, $concatenation);
				} else {
					throw new Exception(sprintf(
						'%s not found in %s',
						$argument, $searchLoadPath ? join($this->loadPaths, ', ') : realpath(dirname($file))
					));
				}
			}
			
			if ($line->isProvide() or $line->isRequire()) continue;
			
			if ($this->stripComments) {
				// Strip C-style single line comments
				if ($line->isComment()) {
					continue;
				}
				
				if ($line->beginsPdocComment()) {
					$inPdocComment = true;
				}
			
				if ($inPdocComment) {
					$inPdocComment = ($line->endsPdocComment() or $line->endsMultilineComment()) 
						? false 
						: true;
						
					continue;
				}
				
				// Strip /* ... */ single line comments
				if ($line->beginsMultilineComment() and $line->endsMultilineComment()) {
					continue;
				}
			}
			
			$concatenation->fwrite($line->toString());
		}
		$concatenation->fwrite("\n");
		return $this;
	}
	
	public function installAssets()
	{}
	
	public function setAssetRoot($assetRoot)
	{
		if (!is_string($assetRoot) or empty($assetRoot)) {
			throw new InvalidArgumentException("Asset root must be a valid string");
		}
		if (!is_writable(realpath($assetRoot))) {
			throw new UnexpectedValueException("Asset root \"{$assetRoot}\" is not writable");
		}
		
		$this->assetRoot = $assetRoot;
		return $this;		
	}
	
	public function setStripComments($stripComments = true)
	{
		$this->stripComments = $stripComments;
		return $this;
	}
	
	public function setRoot($root)
	{
		$this->root = $root;
		return $this;
	}
	
	public function setLoadPath($loadPath)
	{
		if (!is_array($loadPath)) {
			$loadPath = array($loadPath);
		}
		$this->loadPath = array_merge($this->loadPath, $loadPath);
		return $this;
	}
	
	public function setSourceFiles(Array $sourceFiles)
	{
		$this->sourceFiles = $sourceFiles;
		return $this;
	}
	
	public function setConstants(Array $constants)
	{
		$this->constants = $constants;
		return $this;
	}
	
	public function setOptions(Array $options)
	{
		if (!$options) {
			return;
		}
		
		foreach ($options as $key => $value) {
			$setter = "set" . str_replace(" ", null, ucwords(str_replace("_", " ", $key)));
			$this->{$setter}($value);
		}
		
		return $this;
	}
	
	protected function readConstants()
	{
		if (null !== $this->constants) return $this->constants;
		
		$file = $this->getRealpath("constants.yml");
		
		if (!$file) {
			$this->constants = array();
			return $this->constants;
		}
		
		return $this->constants = Spyc::YAMLLoad($file);
	}
	
	protected function getRealpath($path)
	{
		if (realpath($path)) {
			return realpath($path);
		}
		
		if (!$this->loadPath) return false;
		
		foreach ($this->loadPath as $loadPath) {
			$absolutePath = realpath($loadPath . DIRECTORY_SEPARATOR . $path);
			if (is_readable($absolutePath)) {
				return $absolutePath;
			}
		}
		return false;
	}
}
