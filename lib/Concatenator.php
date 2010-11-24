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
 * Require Spyc (Simple PHP YAML Class) YAML Parser for parsing constants config
 */
require_once "Concatenator/spyc.php";

/**
 * A simple file concatenator and file dependency manager
 * 
 * @package Concatenator
 */
class Concatenator
{
	/**
	 * List of load paths
	 * @var array
	 */
	protected $loadPath = array();
	
	/**
	 * List of source files which should be concatenated, can contain Wildcards (*)
	 * in filenames if expand_paths option is ON (default is ON)
	 * @var array
	 */
	protected $sourceFiles = array();
	
	/**
	 * List of all assets which should be installed on installAssets()
	 * @var array
	 */
	protected $provide = array();
	
	/**
	 * Root for all paths
	 * @var string
	 */
	protected $root = ".";	
	
	/**
	 * Directory for installing assets
	 * @var string
	 */
	protected $assetRoot;
	
	/**
	 * Flags
	 */
	protected $writeInfos    = false;
	protected $expandPaths   = true;
	protected $stripComments = true;
	
	/**
	 * Helper var, gets incremented for each file which gets added to the Concatenation
	 * @var int
	 */
	protected $numFiles = 0;
	
	/**
	 * Contains the defined constants and their values from the constants.yml config
	 *
	 * @var array
	 */
	protected $constants;
	
	public function __construct(Array $options = array())
	{
		$this->setOptions($options);
	}
	
	/**
	 * Adds all source files to the concatenation
	 *
	 * @return Concatenator_Concatenation
	 */
	public function getConcatenation()
	{
		$concatenation = new Concatenator_Concatenation();
		$startTime     = microtime(true);
		
		foreach ($this->sourceFiles as $file) {
			$this->addFile($file, $concatenation);
		}
		
		if ($this->writeInfos) {
			$concatenation->fwrite(sprintf(
				'// Built %d source file(s) in %f Seconds', 
				$this->numFiles, 
				microtime(true) - $startTime
			));
		}
		
		$concatenation->rewind();
		return $concatenation;
	}
	
	/**
	 * Processes a single file line by line and adds it to the concatenation
	 *
	 * @param  string                     $file File name
	 * @param  Concatenator_Concatenation $concatenation
	 * @return Concatenator
	 */
	protected function addFile($file, Concatenator_Concatenation $concatenation)
	{
		if (substr($file, -3, 3) !== '.js') {
			$file .= '.js';
		}
		
		$file = $this->find($file);
		
		if (!$file) {
			throw new InvalidArgumentException("File not found");
		}
		
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
					? $this->find($argument) 
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
				if ($line->isComment()) continue;
			
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
		$this->numFiles++;
		return $this;
	}
	
	/**
	 * Copies all assets specified by "provide" diretives to the asset_root
	 *
	 * Currently a stub, as support for the "provide" directive is currently not
	 * implemented.
	 *
	 * @return Concatenator
	 */
	public function installAssets()
	{
		return $this;
	}
	
	/** 
	 * Set the asset root
	 *
	 * @param  string $assetRoot
	 * @return Concatenator
	 */
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
	
	public function setWriteInfos($writeInfos = true)
	{
		$this->writeInfos = $writeInfos;
		return $this;
	}
	
	public function setExpandPaths($expandPaths = true)
	{
		$this->expandPaths = $expandPaths;
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
		$this->loadPath = array();
		if (!is_array($loadPath)) {
			$loadPath = array($loadPath);
		}
		foreach ($loadPath as $path) {
			if ($this->expandPaths) {
				$matches = glob($path);
				if (false === $matches) {
					continue;
				}
				foreach ($matches as $match) {
					$this->loadPath[] = $match;
				}
				continue;
			}
			$this->loadPath[] = $path;
		}
		return $this;
	}
	
	public function setSourceFiles(Array $sourceFiles)
	{
		$this->sourceFiles = array();
		foreach ($sourceFiles as $path) {
			if ($this->expandPaths) {
				$matches = glob($path);
				if (false === $matches) {
					continue;
				}
				foreach ($matches as $match) {
					$this->sourceFiles[] = $match;
				}
				continue;
			}
			$this->sourceFiles[] = $path;
		}
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
			
			if (!is_callable(array($this, $setter))) {
				throw new UnexpectedValueException("Option {$key} is not implemented");
			}
			
			$this->{$setter}($value);
		}
		
		return $this;
	}
	
	/**
	 * Look for a file named "constants.yml" in the load path and parse it with Spyc
	 *
	 * @return array Constants and their respective values as array
	 */
	protected function readConstants()
	{
		if (null !== $this->constants) return $this->constants;
		
		$file = $this->find("constants.yml");
		
		if (!$file) {
			$this->constants = array();
			return $this->constants;
		}
		
		return $this->constants = Spyc::YAMLLoad($file);
	}
	
	/**
	 * Searches the load path for a given path
	 * 
	 * @param  string      $path
	 * @return bool|string Returns FALSE if the file was not found or the full path
	 */
	protected function find($path)
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
