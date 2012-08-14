<?php
/*
Fusebox Software License
Version 1.0

Copyright (c) 2003, 2004, 2005, 2006 The Fusebox Corporation. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted 
provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions 
   and the following disclaimer.

2. Redistributions in binary form or otherwise encrypted form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in the documentation and/or other 
   materials provided with the distribution.

3. The end-user documentation included with the redistribution, if any, must include the following 
   acknowledgment:

   "This product includes software developed by the Fusebox Corporation (http://www.fusebox.org/)."

   Alternately, this acknowledgment may appear in the software itself, if and wherever such 
   third-party acknowledgments normally appear.

4. The names "Fusebox" and "Fusebox Corporation" must not be used to endorse or promote products 
   derived from this software without prior written (non-electronic) permission. For written 
   permission, please contact fusebox@fusebox.org.

5. Products derived from this software may not be called "Fusebox", nor may "Fusebox" appear in 
   their name, without prior written (non-electronic) permission of the Fusebox Corporation. For 
   written permission, please contact fusebox@fusebox.org.

If one or more of the above conditions are violated, then this license is immediately revoked and 
can be re-instated only upon prior written authorization of the Fusebox Corporation.

THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
DISCLAIMED. IN NO EVENT SHALL THE FUSEBOX CORPORATION OR ITS CONTRIBUTORS BE LIABLE FOR ANY 
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT 
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR 
BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE 
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

-------------------------------------------------------------------------------

This software consists of voluntary contributions made by many individuals on behalf of the 
Fusebox Corporation. For more information on Fusebox, please see <http://www.fusebox.org/>.

*/
class FuseboxApplication { //I am the Fusebox application object, formerly the application.fusebox data structure
	
	var $myVersion;
	var $factory;
	var $fuseboxLexicon;
	var $customAttributes;
	var $fuseboxVersion;
	var $appKey;
	var $coreRoot;
	
	function FuseboxApplication() {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		// initialize the fusebox (available to be read by developers but not to be written to)
		$this->isFullyLoaded = false;
		$this->circuits = array();
		$this->classes = array();
		$this->lexicons = array();
		$this->plugins = array();
		$this->pluginPhases = array();
		$this->nonFatalExceptionPrefix = "INFORMATION (can be ignored): ";
	
		$this->precedenceFormOrURL = "form";
		$this->defaultFuseaction = "";
		$this->fuseactionVariable = "fuseaction";
		// this is ignored:
		$this->parseWithComments = false;
		$this->ignoreBadGrammar = true;
		$this->allowLexicon = true;
		$this->useAssertions = true;
		$this->conditionalParse = false;
		
		$this->password = "";
		$this->mode = "production";
		$this->scriptLanguage = "php4";
		$this->scriptFileDelimiter = "php";
		$this->scriptVersion = phpversion();
		$this->maskedFileDelimiters = "htm,cfm,cfml,php,php4,asp,aspx";
		$this->characterEncoding = "utf-8";
		// this is ignored:
		$this->parseWithIndentation = $this->parseWithComments;
		$this->strictMode = false;
		$this->allowImplicitCircuits = false;
		$this->debug = false;
        $this->hasProcess = array('appinit'=>false,'preprocess'=>false,'postprocess'=>false);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function &init /*I am the constructor */ (
			$appKey, //I am FUSEBOX_APPLICATION_KEY
			$appPath, //I am FUSEBOX_APPLICATION_PATH
			$appName, //I am FUSEBOX_APPLICATION_Name
			&$myFusebox //I am the myFusebox data structure
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->myVersion = "5.0.0.GR.0";
		require_once("fuseboxFactory.php");
		if ( !isPHP5() ) { eval('$this->factory =& new FuseboxFactory();'); } else { $this->factory = new FuseboxFactory(); }
		
		$this->fuseboxLexicon = $this->factory->getBuiltinLexicon();
		$this->customAttributes = array();
		
		$this->fuseboxVersion = $this->myVersion;
		
		$this->appKey = $appKey;
		$this->appName = $appName;
		$this->webrootdirectory = str_replace("\\","/",getcwd().DIRECTORY_SEPARATOR);
		$this->coreRoot = str_replace("\\","/",dirname(__FILE__).DIRECTORY_SEPARATOR);

		$this->approotdirectory = str_replace("\\","/",$this->webrootdirectory) . str_replace("\\","/",$appPath);
		if ( substr($this->approotdirectory,-1) != "/" ) {
			$this->approotdirectory .= "/";
		}
		// remove pairs of directory/../ to form canonical path:
		while ( strpos($this->approotdirectory,'/../') !== false ) {
			$this->approotdirectory = ereg_replace("[^\.:/]*/\.\./","",$this->approotdirectory);
		}
		// this works on all platforms:
		$this->osdelimiter = "/";

		$this->coreToAppRootPath = $this->relativePath($this->coreRoot,$this->approotdirectory);
		$this->appRootPathToCore = $this->relativePath($this->approotdirectory,$this->coreRoot);
		$this->coreToWebRootPath = $this->relativePath($this->coreRoot,$this->webrootdirectory);
		$this->WebRootPathToCore = $this->relativePath($this->webrootdirectory,$this->coreRoot);
		$this->WebRootPathToappRoot = $this->relativePath($this->webrootdirectory,$this->approotdirectory);
		
		$this->parsePath = "parsed/";
		$this->parseRootPath = "../";
		$this->pluginsPath = "plugins/";
		$this->lexiconPath = "lexicon/";
		$this->errortemplatesPath = "errortemplates/";
		
		$this->circuits = array();
		$this->reload($appKey,$appPath,$myFusebox);

		if ( $this->strictMode ) {
			// rootdirectory was deprecated in Fusebox 5 so we no longer set it it strict mode:
			$this->rootdirectory = null;
		} else {
			// for FB4.0 compatibility:
			$this->rootdirectory = $this->approotdirectory;
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;

	}

	function reload /*I (re)load the fusebox.xml file into memory and (re)load all of the application components referenced by that.*/ (
			$appKey, //I am FUSEBOX_APPLICATION_KEY
			$appPath, //I am FUSEBOX_APPLICATION_PATH.
			&$myFusebox //I am the myFusebox data structure.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		$fbFile = "fusebox.xml.php";
		$fbXML = "";
		$fbCode = "";
		$encodings = 0;
		$needToLoad = true;
		$fuseboxFiles = 0;

		if ( isset($this->timestamp) && ( false !== ( $fuseboxFiles = opendir($this->approotdirectory) ) ) ) {
			$found = false;
			while ( false !== ( $file = readdir($fuseboxFiles) ) && !$found ) {
				if ( strpos('fusebox.xml',$file) === 0 ) {
					$needToLoad = ( filemtime($this->approotdirectory.$file) > $this->timestamp );
					$found = true;
				}
			// else ignore the ambiguity
			}
		}

		// FB5: fusebox.loadclean will delete all the parsed files
		if ( $myFusebox->parameters['clean'] ) {
			$this->deleteParsedFiles();
		}
		
		require_once("udf_XMLUtils.php");
		if ( $needToLoad ) {
			if ( $this->debug ) {
				$myFusebox->trace("Compiler","Loading fusebox.xml file");
			}
			// attempt to load fusebox.xml(.php):
			if ( !file_Exists($this->approotdirectory . $fbFile) ) {
				$fbFile = "fusebox.xml";
			}
			
			do {
				$okay = false;
				if ( false == ( $fbXMLfile = @fopen($this->approotdirectory . $fbFile,"r") ) ) break;
				if ( false == ( $fbXML = @fread($fbXMLfile,filesize($this->approotdirectory . $fbFile)) ) ) break;
				if ( false == ( @fclose($fbXMLfile) ) ) break;
				$okay = true;
			} while ( false );
			if ( !$okay ) {
				__cfthrow(array( 'type'=>"fusebox.missingFuseboxXML", 
					'message'=>"missing fusebox.xml", 
					'detail'=>"The file '".$fbFile."' could not be found in $this->approotdirectory."
				));
			}
			
			do {
				$okay = false;
				// see if we need to re-read based on the encoding being different to our default
				if ( ereg('(<parameters>).+(<parameter name="characterEncoding" value=")([^"]+)',$fbXML,$match) ) {
					$this->encodings =  trim($match[3]);
				} else {
					$this->encodings = "utf-8";
				}
				if ( false == ($fbCode = @xmlParse($fbXML,$this->encodings) ) ) break;
				$okay = true;
			} while ( false );
			if ( !$okay ) {
				__cfthrow(array( 'type'=>"fusebox.fuseboxXMLError", 
					'message'=>"Error reading fusebox.xml", 
					'detail'=>"A problem was encountered while reading the ".$fbFile." file. This is usually caused by unmatched XML tags (a &lt;tag&gt; without a &lt;/tag&gt; or without use of the &lt;tag/&gt; short-cut.)"
				));
			}
			
			$this->loadParameters($fbCode);
			$this->loadLexicons($fbCode);
			$this->loadClasses($fbCode);
			$this->loadPlugins($fbCode);
			$this->loadGlobalPreAndPostProcess($fbCode);
			// save fusebox.xml DOM internally for (re-)loading circuits
			$this->cacheFBCode($fbCode,$myFusebox);
			$this->fbFile = $fbFile;
		} else {
			$fbCode = $this->getFBCode();
		}
		
		// to track circuit loads on this request
		if ( !isset($_REQUEST['__fusebox']['CircuitsLoaded']) ) $_REQUEST['__fusebox']['CircuitsLoaded'] = array();		
		$this->loadCircuits($fbCode,$myFusebox);
		
		
		// application data available to developers via getApplicationData() method:
		$this->data = array();
		
		$this->isFullyLoaded = true;
		$this->applicationStarted = false;
		$this->timestamp = microtime();
		$this->timestamp = (substr($this->timestamp,-strpos($this->timestamp,' '))) * 1000;
		$this->dateLastLoaded = microtime();
		$this->dateLastLoaded = (substr($this->dateLastLoaded,-strpos($this->dateLastLoaded,' '))) * 1000;
		
		/*
			The following documented parts of application.fusebox are not supported in Fusebox 5:
			- application.fusebox.xml
			- application.fusebox.globalfuseactions.*
			- application.fusebox.circuits.*.xml
			- application.fusebox.circuits.preFuseaction.*
			- application.fusebox.circuits.postFuseaction.*
			- application.fusebox.circuits.*.fuseactions.*.xml
		*/
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function getPluginsPath() { //I am a convenience method to return the location of the plugins.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->pluginsPath;
	
	}
	
	function getApplicationData() { //I return a reference to the application data cache. This is a new concept in Fusebox 5.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->data;
	
	}
	
	function getApplicationRoot() { //I am a convenience method to return the full application root directory path.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->approotdirectory;
	
	}
	
	function getFuseboxXMLFilename() { //I return the actual name of the fusebox.xml(.cfm) file.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->fbFile;
	
	}
	
	function getCoreToAppRootPath() { //I am a convenience method to return the relative path from the core files to the application root.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->coreToAppRootPath;
	
	}
	
	function getWebRootPathToappRoot() { // I am a convenience method to return the relative path from the webroot to the application root.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->WebRootPathToappRoot;
	}
	
	function compileAll /*I compile all the public fuseactions in the application. */ (
			&$myFusebox //I am the myFusebox data structure.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';

		$c = 0;
		$a = 0;
		$f = 0;
	
		foreach ( array_keys($this->circuits) as $c ) {
			$cir =& $this->circuits[$c];
			$a =& $cir->getFuseactions();
			foreach ( array_keys($a) as $f ) {
				if ( $a[$f]->access == "public" ) {
					$this->compileRequest($c . "." . $f,$myFusebox);
				}
			}
		}
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';

	}
	
	function compileRequest /*I compile a specific (public) fuseaction as an external request.*/ (
			$circuitFuseaction, //I am the full name of the requested fuseaction (circuit.fuseaction).
			&$myFusebox //I am the myFusebox data structure.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$myVersion = $this->getVersion();
		list($circuit,$fuseaction) = explode('.',$circuitFuseaction);
		$i = 0;
		$n = 0;
		$needRethrow = true;
		$needTryOnFuseaction = false;
		$parsedName = strtolower($circuitFuseaction).".php";
		$parsedFile = $this->getWebRootPathToappRoot().$this->parsePath.$parsedName;
		$fullParsedFile = $this->getApplicationRoot().$this->parsePath.$parsedName;
		$result = array();
		$writer = 0;
		
		// validate format of the fuseaction:
		if ( count(explode(".",$circuitFuseaction)) != 2 || substr($circuitFuseaction,-1) == '.' ) {
			__cfthrow(array( 'type'=>"fusebox.malformedFuseaction", 
				'message'=>"malformed Fuseaction", 
				'detail'=>"You specified a malformed Fuseaction of ".$circuitFuseaction.". A fully qualified Fuseaction must be in the form [Circuit].[Fuseaction]."
			));
		}
		
		// to track reloads on this request
		if ( !isset($_REQUEST['__fusebox']['CircuitsLoaded']) ) $_REQUEST['__fusebox']['CircuitsLoaded'] = array();
		if ( !isset($_REQUEST['__fusebox']['fuseactionsDone']) ) $_REQUEST['__fusebox']['fuseactionsDone'] = array();
		
		// set up myFusebox values for this request:
		$myFusebox->originalCircuit = $circuit;
		$myFusebox->originalFuseaction = $fuseaction;
		foreach ( array_keys($this->plugins) as $i ) {
			$myFusebox->plugins[$i] = array();
		}

		// note that in Fusebox 5, these are really all the same set of files
		$myFusebox->version['loader'] = $myVersion;
		$myFusebox->version['parser'] = $myVersion;
		$myFusebox->version['transformer'] = $myVersion;
		// legacy test from FB41 although it's a bit pointless
		if ( $myFusebox->version['runtime'] != $myFusebox->version['loader'] ) {
			__cfthrow(array( 'type'=>"fusebox.versionMismatchException",
				'message'=>"The loader is not the same version as the runtime",
				'detail'=>""
			));
		}
		
		// check access on request - if the circuit/fuseaction doesn't exist we trap it later
		if ( array_key_exists($circuit,$this->circuits) && 
				array_key_exists($fuseaction,$this->circuits[$circuit]->fuseactions) &&
				$this->circuits[$circuit]->fuseactions[$fuseaction]->getAccess() != "public" ) {
			__cfthrow(array( 'type'=>"fusebox.invalidAccessModifier", 
				'message'=>"Invalid Access Modifier", 
				'detail'=>"You tried to access $circuit.$fuseaction which does not have access modifier of public. A Fuseaction which is to be accessed from anywhere outside the application (such as called via an URL, or a FORM, or as a web service) must have an access modifier of public or if unspecified at least inherit such a modifier from its circuit."
			));
		}
		if ( !file_Exists($fullParsedFile) || $myFusebox->parameters['parse'] ) {
			$fp = @fopen($fullParsedFile,"r");
			//if ( !flock($fp,LOCK_EX) ) die('Could not get exclusive lock to parsed fuseaction file');
				if ( !file_Exists($fullParsedFile) || $myFusebox->parameters['parse'] ) {
					$_REQUEST['__fusebox']['SuppressPlugins'] = false;
					require_once("fuseboxWriter.php");
					if ( !isPHP5() ) { eval('$writer =& new FuseboxWriter($this,$myFusebox);'); } else { $writer = new FuseboxWriter($this,$myFusebox); }
					$writer->open($parsedName);
					$writer->rawPrintln("// circuit: $circuit");
					$writer->rawPrintln("// fuseaction: $fuseaction");
					if ( array_key_exists("processError",$this->pluginPhases) &&
							!$_REQUEST['__fusebox']['SuppressPlugins'] ) {
						if ( $this->scriptVersion{0} == '5' ) {
							$writer->rawPrintln("try {");
						} else {
							$writer->rawPrintln("do {");
							$writer->rawPrintln("ini_set('track_errors','1');");
							$writer->rawPrintln('$php_errormsg = false;');
						}
					}
					$writer->setCircuit($circuit);
					$writer->setFuseaction($fuseaction);
					if ( $this->hasProcess["appinit"] ) {
						$writer->setPhase("appinit");
						$writer->println('if ( $myFusebox->applicationStart ) { $_fba =& $myFusebox->getApplication();');
						$writer->println('	if ( !$_fba->applicationStarted ) {');
						$writer->println('		if ( !$_fba->applicationStarted ) {');
						$_REQUEST['__fusebox']['SuppressPlugins'] = true;
						$this->process["appinit"]->compile($writer);
						$writer->println('			$_fba->applicationStarted = true;');
						$writer->println('		}');
						$writer->println('	}');
						$writer->println("}");
					}
					$_REQUEST['__fusebox']['SuppressPlugins'] = false;
					if ( array_key_exists("preProcess",$this->pluginPhases) ) {
						$n = count($this->pluginPhases["preProcess"]);
						for ( $i = 0 ; $i < $n ; $i++ ) {
							$this->pluginPhases["preProcess"][$i]->compile($writer);
						}
					}
					$writer->setPhase("preprocessFuseactions");
					if ( $this->hasProcess["preprocess"] ) {
						$this->process["preprocess"]->compile($writer);
					}
					if ( array_key_exists("fuseactionException",$this->pluginPhases) &&
							count($this->pluginPhases["fuseactionException"]) > 0 &&
							!$_REQUEST['__fusebox']['SuppressPlugins'] ) {
						$needTryOnFuseaction = true;
						if ( $this->scriptVersion{0} == '5' ) {
							$writer->rawPrintln("try {");
						} else {
							$writer->rawPrintln("do {");
							$writer->rawPrintln("ini_set('track_errors','1');");
							$writer->rawPrintln('$php_errormsg = false;');
						}
					}
					if ( array_key_exists("preFuseaction",$this->pluginPhases) ) {
						$n = count($this->pluginPhases["preFuseaction"]);
						for ( $i = 0 ; $i < $n ; $i++ ) {
							$this->pluginPhases["preFuseaction"][$i]->compile($writer);
						}
					}
					$writer->setPhase("requestedFuseaction");
					$this->compile($writer,$circuit,$fuseaction);
					if ( array_key_exists("postFuseaction",$this->pluginPhases) ) {
						$n = count($this->pluginPhases["postFuseaction"]);
						for ( $i = 0 ; $i < $n ; $i++ ) {
							$this->pluginPhases["postFuseaction"][$i]->compile($writer);
						}
					}
					if ( $needTryOnFuseaction ) {
						$writer->rawPrintln("}");
						if ( $this->scriptVersion{0} != '5' ) {
							$writer->rawPrintln(" while ( false );");
							$writer->rawPrintln('if ( $php_errormsg ) {');
						}
						$n = count($this->pluginPhases["fuseactionException"]);
						for ( $i = 0 ; $i < $n ; $i++ ) {
							$this->pluginPhases["fuseactionException"][$i]->compile($writer);
						}
						if ( $this->scriptVersion{0} != '5' ) {
							$writer->rawPrintln('}');
						}
					}
					$writer->setPhase("postprocessFuseactions");
					if ( $this->hasProcess["postprocess"] ) {
						$this->process["postprocess"]->compile($writer);
					}
					if ( array_key_exists("postProcess",$this->pluginPhases) ) {
						$n = count($this->pluginPhases["postProcess"]);
						for ( $i = 0 ; $i < $n ; $i++ ) {
							$this->pluginPhases["postProcess"][$i]->compile($writer);
						}
					}
					if ( array_key_exists("processError",$this->pluginPhases) &&
							!$_REQUEST['__fusebox']['SuppressPlugins'] ) {
						$writer->rawPrintln('}');
						if ( $this->scriptVersion{0} != '5' ) {
							$writer->rawPrintln(' while ( false );');
							$writer->rawPrintln('if ( $php_errormsg ) {');
						}
						$n = count($this->pluginPhases["processError"]);
						for ( $i = 0 ; $i < $n ; $i++ ) {
							//$needRethrow = false;
							$this->pluginPhases["processError"][$i]->compile($writer);
						}
						if ( $this->scriptVersion{0} != '5' ) $writer->rawPrintln('}');
					}
					if ( $this->scriptVersion{0} != '5' ) $writer->rawPrintln("ini_restore('track_errors');");
					/*
					if ( needRethrow>
						$writer->rawPrintln('<' & 'cfcatch><' & 'cfrethrow><' & '/cfcatch>');
					}
					*/
					$writer->close();
				}
			//flock($fp,LOCK_UN);
			@fclose($fp);
		}
		$result['parsedName'] = $parsedName;
		$result['parsedFile'] = $parsedFile;
		$result['lockName'] = $fullParsedFile;
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $result;
		
	}
	
	function compile /*I compile a specific fuseaction during a request (such as for a 'do' verb).*/ (
			&$writer, //I am the parsed file writer object. I am required but it's faster to specify that I am not required.
			$circuit, //I am the circuit name. I am required but it's faster to specify that I am not required.
			$fuseaction //I am the fuseaction name, within the specified circuit.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		// if ( array_key_exists('home',$GLOBALS['application']['fusebox']->circuits) ) echo "<br />".get_class($this).'.compile start: '.$GLOBALS['_fba']->circuits['home']->alias;
		$c = "";
		if ( !array_key_exists($circuit,$writer->fuseboxApplication->circuits) ) {
			__cfthrow(array( 'type'=>"fusebox.undefinedCircuit", 
				'message'=>"undefined Circuit", 
				'detail'=>"The fuseboxApplication received a compile request for a Circuit of $circuit which is not defined, during phase ".$writer->phase
			));
		}
		// FB5: development-circuit-load only reloads the requested circuit
		if ( $this->mode == "development-circuit-load" ) {
			// FB5: ensure we only reload each circuit once per request
			if ( !array_key_exists($circuit,$_REQUEST['__fusebox']['CircuitsLoaded']) ) {
				$_REQUEST['__fusebox']['CircuitsLoaded'][$circuit] = true;
				$this->circuits[$circuit]->reload($writer->getMyFusebox());
			}
		}
	
		$c = $writer->setCircuit($circuit);
		$writer->fuseboxApplication->circuits[$circuit]->compile($writer,$fuseaction);
		$writer->setCircuit($c);
		// if ( array_key_exists('home',$GLOBALS['application']['fusebox']->circuits) ) echo "<br />".get_class($this).'.compile end: '.$GLOBALS['_fba']->circuits['home']->alias;
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function handleFuseboxException /*I attempt to handle a Fusebox exception by looking for a handler file in the errortemplates/ directory. I return true if I handle the exception, else I return false.*/ (
			$cfcatch //I am the original cfcatch structure from the exception that fusebox5.cfm caught.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$handled = false;
		$type = $cfcatch['type'];
		$ext = "." . $this->scriptFileDelimiter;
		$errorFile = $this->errortemplatesPath . $type . $ext;
		$handlerExists = ( file_Exists($this->getApplicationRoot() . $errorFile) );
		$FUSEBOX_APPLICATION_KEY = $this->appKey;
		
		while ( !$handlerExists && strlen($type) > 0 ) {
			$type = implode('.',array_pop(explode('.',$type)));
			$errorFile = $this->errortemplatesPath . $type . $ext;
			$handlerExists = ( file_Exists($this->getApplicationRoot() . $errorFile) );
		}
		if ( $handlerExists ) {
			include($this->getCoreToAppRootPath().$errorFile);
			$handled = true;
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $handled;
		
	}
	
	function &getFuseactionFactory() {
				//I return the factory object that makes fuseaction objects for the framework.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->factory;

	}
	
	function &getClassDefinition /*I return the class declaration for a given class. I throw an exception if the class has no declaration.*/ (
			$className //I am the name of the class whose declaration should be returned.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->classes[$className];

	}
	
	function getLexiconDefinition /*I return the lexicon definition for a given namespace. I return either the internal Fusebox lexicon or a declared (Fusebox 4.1 style) lexicon.*/ (
			$namespace //I am the namespace of the lexicon whose definition should be returned. I am required but it's faster to specify that I am not required.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		if ( $namespace == $this->fuseboxLexicon['namespace'] ) {
			return $this->fuseboxLexicon;
		} else {
			return $this->fb41Lexicons[$namespace];
		}

	}
	
	function getVersion() {
				//I return the version of this Fusebox 5 object. This is the preferred way to obtain the version in Fusebox 5.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->fuseboxVersion;
		
	}
	
	function getAlias() {
				//I return the fake circuit alias for the application.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return '$fusebox';
	
	}
	
	function &getApplication() {
				//I return the fusebox application object.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;
	
	}
	
	function getCustomAttributes /*I return any custom attributes for the specified namespace prefix.*/ (
			$ns //I am the namespace for which to return custom attributes.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		if ( array_key_exists($ns,$this->customAttributes) ) {
			// we structCopy() this so folks can't poke values back into the metadata!
			//return structCopy($this->customAttributes[$ns]);
			return $this->customAttributes[$ns];
		} else {
			return array();
		}
		
	}
	
	function deleteParsedFiles() {
				//I delete all the script files in the parsed/ directory.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		$fileQuery = 0;
		$parseDir = $this->getApplicationRoot() . $this->parsePath;
		
		do {
			if ( false == ( $fileQuery = @opendir($parseDir) ) ) break;
			while ( false !== ( $file = readdir($fileQuery) ) ) {
				$substr = 0 - strlen($this->scriptFileDelimiter);
				if ( substr($file,$substr) == $this->scriptFileDelimiter ) {
					@unlink($parseDir.$file);
				}
			}
			if ( false == ( @closedir($fileQuery) ) ) break;
		} while ( false ); 
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	
	}
	
	function loadCircuits /*I (re)load all the circuits in an application.*/ (
			$fbCode, //I am the parsed XML representation of the fusebox.xml file.
			&$myFusebox //I am the myFusebox data structure.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$children = xmlSearch($fbCode,"//fusebox/circuits/circuit",true);
		$i = 0;
		$n = count($children);
		$previousCircuits =& $this->circuits;
		$alias = "";
		$parent = "";
		$nAttrs = 0;
		
		$this->circuits = array();
		
		// pass 1: build the circuits
		for ( $i = 0 ; $i < $n ; $i++ ) {
			if ( !array_key_exists("alias",$children[$i]['xmlAttributes']) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'alias' is required, for a 'circuit' declaration in fusebox.xml."
				));
			}
			if ( !array_key_exists("path",$children[$i]['xmlAttributes']) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'path' is required, for a 'circuit' declaration in fusebox.xml."
				));
			}
			if ( array_key_exists("parent",$children[$i]['xmlAttributes']) ) {
				$parent = $children[$i]['xmlAttributes']['parent'];
				$nAttrs = 3;
			} else {
				$parent = "";
				$nAttrs = 2;
			}
			if ( $this->strictMode && $nAttrs != count($children[$i]['xmlAttributes']) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
					'message'=>"Unexpected attributes",
					'detail'=>"Attributes other than 'alias', 'path' and 'parent' were found in the declaration of the '$alias' circuit in fusebox.xml."
				));
			}
			$alias = $children[$i]['xmlAttributes']['alias'];
			// record each circuit load per request - optimization for development-circuit-load mode
			$_REQUEST['__fusebox']['CircuitsLoaded'][$alias] = true;
			if ( array_key_exists($alias,$previousCircuits) &&
					$children[$i]['xmlAttributes']['path'] == $previousCircuits[$alias]->getOriginalPath() &&
					$parent == $previousCircuits[$alias]->parent ) {
				// old circuit, we can just reload it
				$this->circuits[$alias] =& $previousCircuits[$alias]->reload($myFusebox);
			} else {
				// new circuit, we must create it from scratch
				require_once("fuseboxCircuit.php");
				if ( !isPHP5() ) { eval('$this->circuits[$alias] =& new FuseboxCircuit($this,$alias,$children[$i]["xmlAttributes"]["path"],$parent,$myFusebox);'); } else { $this->circuits[$alias] = new FuseboxCircuit($this,$alias,$children[$i]['xmlAttributes']['path'],$parent,$myFusebox); }
			}
		}
		
		// pass 2: build the circuit trace for each circuit
		foreach( array_keys($this->circuits) as $i ) {
			$this->circuits[$i]->buildCircuitTrace();
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function loadLexicons /*I load any lexicon declarations (both the Fusebox 4.1 style lexicon declarations and the Fusebox 5 style namespace declarations).*/ (
			$fbCode //I am the parsed XML representation of the fusebox.xml file.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$children = xmlSearch($fbCode,"//fusebox/lexicons/lexicon",true);
		$i = 0;
		$n = count($children);
		$aLex = "";
		$attributes = $fbCode['xmlRoot']['xmlAttributes'];
		$attr = "";
		$ns = "";
		
		if ( $n > 0 && $this->strictMode ) {
			__cfthrow(array( 'type'=>"fusebox.badGrammar.deprecated", 
				'message'=>"Deprecated feature",
				'detail'=>"Using the 'lexicon' declaration in fusebox.xml was deprecated in Fusebox 5."
			));
		}

		// load the legacy FB41 lexicons from the XML
		$this->fb41Lexicons = array();
		if ( $n > 0 ) {
			for ( $i = 0 ; $i < $n ; $i++ ) {
				$aLex = array();
				$aLex['namespace'] = $children[$i]['xmlAttributes']['namespace'];
				$aLex['path'] = str_replace('\\',"/",$children[$i]['xmlAttributes']['path']);
				if ( substr($aLex['path'],-1) != "/" ) {
					$aLex['path'] .= "/";
				}
				$aLex['path'] = $this->getWebRootPathToappRoot() . "lexicon/" . $aLex['path'];
				$this->fb41Lexicons[$children[$i]['xmlAttributes']['namespace']] = $aLex;
			}
		}
		// now load the new FB5 implicit lexicons from the <fusebox> tag
		
		// pass 1: pull out any namespace declarations
		foreach ( array_keys($attributes) as $attr ) {
			if ( strlen($attr) > 6 && substr($attr,0,6) == "xmlns:" ) {
				// found a namespace declaration, pull it out:
				$aLex = array();
				$aLex['namespace'] = array_pop(explode(":",$attr));
				if ( $aLex['namespace'] == $this->fuseboxLexicon['namespace'] ) {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.reservedName",
						'message'=>"Attempt to use reserved namespace", 
						'detail'=>"You have attempted to declare a namespace '{$aLex['namespace']}' (in fusebox.xml) which is reserved by the Fusebox framework."
					));
				}
				$aLex['path'] = $this->getApplication();
				$aLex['path'] = $aLex['path']->getWebRootPathToappRoot() . $aLex['path']->lexiconPath . $attributes[$attr];
				$this->lexicons[$aLex['namespace']] = $aLex;
				$this->customAttributes[$aLex['namespace']] = array();
			}
		}
		
		// pass 2: pull out any custom attributes
		foreach ( array_keys($attributes) as $attr ) {
			if ( count(explode(":",$attr)) == 2 ) {
				// looks like a custom attribute:
				$ns = array_shift(explode(":",$attr));
				if ( $ns == "xmlns" ) {
					// special case - need to ignore xmlns:foo="bar"
				} elseif ( array_key_exists($ns,$this->customAttributes) ) {
					$this->customAttributes[$ns][array_pop(explode(":",$attr))] = $attributes[$attr];
				} else {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.undeclaredNamespace", 
						'message'=>"Undeclared lexicon namespace", 
						'detail'=>"The lexicon prefix '$ns' was found on a custom attribute in the <fusebox> tag but no such lexicon namespace has been declared."
					));
				}
			} elseif ( $this->strictMode ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
					'message'=>"Unexpected attributes",
					'detail'=>"Unexpected attributes were found in the 'fusebox' tag in fusebox.xml."
				));
			}
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function loadClasses /*I load any class declarations, including custom attributes (based on Fusebox 5 namespace declarations).*/ (
			$fbCode //I am the parsed XML representation of the fusebox.xml file.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$children = xmlSearch($fbCode,"//fusebox/classes/class",true);
		$i = 0;
		$n = count($children);
		$attribs = 0;
		$attr = "";
		$ns = "";
		$customAttribs = 0;
		$constructor = "";
		$type = "";
		$nAttrs = 0;
		
		$this->classes = array();
		
		for ( $i = 0 ; $i < $n ; $i++ ) {
			$attribs = $children[$i]['xmlAttributes'];

			if ( !array_key_exists("alias",$attribs) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'alias' is required, for a 'class' declaration in fusebox.xml."
				));
			}
			if ( !array_key_exists("classpath",$attribs) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'classpath' is required, for a 'class' declaration in fusebox.xml."
				));
			}
			if ( array_key_exists("constructor",$attribs) ) {
				$constructor = $attribs['constructor'];
				$nAttrs = 3;
			} else {
				$constructor = "";
				$nAttrs = 2;
			}
			// FB5: allow sensible default for type
			if ( array_key_exists("type",$attribs) ) {
				$type = $attribs['type'];
				$nAttrs++;
			} else {
				$type = "component";
			}

			// scan for custom attributes
			$customAttribs = array();
			foreach ( array_keys($attribs) as $attr ) {
				if ( count(explode(":",$attr)) == 2 ) {
					$nAttrs++;
					// looks like a custom attribute:
					$ns = array_shift(explode(":",$attr));
					if ( array_key_exists($ns,$this->customAttributes) ) {
						$customAttribs[$ns][array_pop(explode(":",$attr))] = $attribs[$attr];
					} else {
						__cfthrow(array( 'type'=>"fusebox.badGrammar.undeclaredNamespace", 
							'message'=>"Undeclared lexicon namespace", 
							'detail'=>"The lexicon prefix '$ns' was found on a custom attribute in the <class> tag but no such lexicon namespace has been declared."
						));
					}
				}
			}
			
			if ( $this->strictMode && count($attribs) != $nAttrs ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
					'message'=>"Unexpected attributes",
					'detail'=>"Unexpected attributes were found in the '{$attribs['alias']}' class declaration in fusebox.xml."
				));
			}

			require_once("fuseboxClassDefinition.php");
			if ( !isPHP5() ) { eval('$this->classes[$attribs["alias"]] =& new FuseboxClassDefinition($type,$attribs["classpath"],$constructor,$customAttribs);'); } else { $this->classes[$attribs['alias']] = new FuseboxClassDefinition($type,$attribs['classpath'],$constructor,$customAttribs); }
			
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function loadPlugins /*I load any plugin declarations.*/ (
			$fbCode //I am the parsed XML representation of the fusebox.xml file.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$children = xmlSearch($fbCode,"//fusebox/plugins/phase",true);
		$i = 0;
		$n = count($children);
		$j = 0;
		$nn = 0;
		$phase = "";
		$plugin = 0;
		
		$this->plugins = array();
		$this->pluginPhases = array();
		
		for ( $i = 0 ; $i < $n ; $i++ ) {
			if ( !array_key_exists("name",$children[$i]['xmlAttributes']) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'name' is required, for a 'plugin' declaration in fusebox.xml."
				));
			}
			$phase = $children[$i]['xmlAttributes']['name'];
			if ( $this->strictMode && count($children[$i]['xmlAttributes']) != 1 ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
					'message'=>"Unexpected attributes",
					'detail'=>"Unexpected attributes were found in the '$phase' phase declaration in fusebox.xml."
				));
			}
			$nn = count($children[$i]['xmlChildren']);
			for ( $j = 0 ; $j < $nn ; $j++ ) {
				require_once("fuseboxPlugin.php");
				if ( !isPHP5() ) { eval('$plugin =& new FuseboxPlugin($phase,$children[$i]["xmlChildren"][$j],$this);'); } else { $plugin = new FuseboxPlugin($phase,$children[$i]['xmlChildren'][$j],$this); }
				$this->plugins[$plugin->getName()][$phase] = $plugin;
				if ( !array_key_exists($phase,$this->pluginPhases) ) {
					$this->pluginPhases[$phase] = array();
				}
				$this->pluginPhases[$phase][] = $plugin;
			}
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function loadParameters /*I load any parameter declarations (and ensure none of them can overwrite public methods in this object!).*/ (
			$fbCode //I am the parsed XML representation of the fusebox.xml file.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$children = xmlSearch($fbCode,"//fusebox/parameters/parameter",true);
		$i = 0;
		$n = count($children);
		$p = "";
		
		for ( $i = 0 ; $i < $n ; $i++ ) {
			if ( !array_key_exists("name",$children[$i]['xmlAttributes']) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'name' is required, for a 'parameter' declaration in fusebox.xml."
				));
			}
			$p = $children[$i]['xmlAttributes']['name'];
			if ( !array_key_exists("value",$children[$i]['xmlAttributes']) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'value' is required, for the '$p' parameter declaration in fusebox.xml."
				));
			}
			if ( $this->strictMode && count($children[$i]['xmlAttributes']) != 2 ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
					'message'=>"Unexpected attributes",
					'detail'=>"Unexpected attributes were found in the '$p' parameter declaration in fusebox.xml."
				));
			}
			if ( array_key_exists($p,$this) && method_exists($this,$p) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.reservedName",
					'message'=>"Attempt to use reserved parameter name",
					'detail'=>"You have attempted to set a parameter called '$p' which is reserved by the Fusebox framework."
				));
			} else {
				$this->$p = $children[$i]['xmlAttributes']['value'];
			}
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function loadGlobalProcess /*I load the globalfuseaction for the specified processing phase.*/ (
			$fbCode, //I am the parsed XML representation of the fusebox.xml file.
			$processPhase //I am the name of the processing phase to load (appinit, preprocess or postprocess).
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$children = xmlSearch($fbCode,"//fusebox/globalfuseactions/$processPhase",true);
		$n = count($children);
		
		if ( $n == 0 || ( $n == 1 && count($children[0]['xmlChildren']) == 0 ) ) {
			$this->hasProcess[$processPhase] = false;
		} elseif ( $n == 1 ) {
			$this->hasProcess[$processPhase] = true;
			require_once("fuseboxAction.php");
			if ( !isPHP5() ) {
				eval('$this->process[$processPhase] =& new FuseboxAction($this, "\$globalfuseaction/$processPhase", "internal", $children[0]["xmlChildren"],true);'); 
			} else {
				$this->process[$processPhase] = new FuseboxAction($this, "\$globalfuseaction/$processPhase", "internal", $children[0]['xmlChildren'],true);
			}
		} else {
			__cfthrow(array( 'type'=>"fusebox.badGrammar.nonUniqueDeclaration", 
				'message'=>"Declaration was not unique", 
				'detail'=>"More than one &lt;$process&gt; declaration was found in the &lt;globalfuseactions&gt; section in fusebox.xml."
			));
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function loadGlobalPreAndPostProcess /* load any globalfuseaction declarations.*/ (
			$fbCode //I am the parsed XML representation of the fusebox.xml file.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$children = xmlSearch($fbCode,"//fusebox/globalfuseactions/*",true);
		$i = 0;
		$n = count($children);
		
		for ( $i = 0 ; $i < $n ; $i++ ) {
			if ( !in_array($children[$i]['xmlName'],array("appinit","preprocess","postprocess")) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.illegalDeclaration",
					'message'=>"Illegal declaration",
					'detail'=>"The tag '".$children[$i]['xmlName']."' was found where 'appinit', 'preprocess' or 'postprocess' was expected in the &lt;globalfuseactions&gt; section in fusebox.xml."
				));
			}
		}

		$this->hasProcess = array('appinit'=>false,'preprocess'=>false,'postprocess'=>false);
		$this->process = array();
		$this->loadGlobalProcess($fbCode,"appinit");
		$this->loadGlobalProcess($fbCode,"preprocess");
		$this->loadGlobalProcess($fbCode,"postprocess");
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function relativePath /*I compute the relative path from one file system location to another.*/ (
			$from, //I am the full pathname from which the relative path should be computed.
			$to //I am the full pathname to which the relative path should be computed.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$from = explode("/",$from);
		$to = explode("/",$to);
		$relative = "";
		$fromFirst = $from[0];
		$fromRest = $from;
		$toFirst = $to[0];
		$toRest = $to;
		$needSlash = false;
		
		// trap special case first
		if ( $from == $to ) {
			if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
			return "";
		}
	
		// walk down the common part of the paths
		while ( $fromFirst == $toFirst ) {
			$needSlash = true;
			
			$fromFirst = array_shift($from);
			$fromRest = $from;
			$toFirst = array_shift($to);
			$toRest = $to;
			$fromFirst = $fromRest[0];
			$toFirst = $toRest[0];
		}	
		// at this point the paths differ
		$toRest = implode("/",$toRest);
		$fromRest = implode("/",$fromRest);
		if ( substr($fromRest,-1) == "/" ) $fromRest = substr($fromRest,0,strlen($fromRest)-1);
		if ( substr($toRest,-1) == "/" ) $toRest = substr($toRest,0,strlen($toRest)-1);
		if ( !$needSlash ) {
			// the paths differed from the top so we need to strip the leading /
			$toRest = substr($toRest,1);
		}
		$relative = ( strlen($fromRest) > 0 ) ? 
			str_repeat("../",count(explode("/",$fromRest))) . $toRest :
			$toRest;
		/*
			ensure the trailing / is present - strictly speaking this is a bug fix for Railo
			but it's probably a good practice anyway
		*/
		if ( substr($relative,-1) != "/" ) {
			$relative .= "/";
		}
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $relative;
		
	}
	
	function cacheFBCode($fbCode,&$myFusebox) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$fbc = fopen($this->approotdirectory."parsed/xml_{$this->appName}.php", 'w');
		if(!flock($fbc,LOCK_EX)){
			$myFusebox->trace("Could not get exclusive lock to the cached application xml file");
		}
		if (!fwrite($fbc, "<?php\n\$fbCode = " . var_export($fbCode, true) . ";\n?>\n")) {
			die("An Error occured during write of application cached xml file.");
		}
		@flock($fbc,LOCK_UN);
		fclose($fbc);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function getFBCode() {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		include($this->WebRootPathToappRoot."parsed/xml_{$this->appName}.php");
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
        return $fbCode;
	}
	
	function writeFullObjectToDisk() { // I write the full application object to disk, to speed up subsequent calls when a fuseaction needs to be parsed but a full load isn't needed
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		global $application;
		$fb_['content'] = "<?"."php \n\$coreroot = '$this->WebRootPathToCore'; \nrequire_once(\$coreroot.\"fuseboxApplication.php\");\nrequire_once(\$coreroot.\"fuseboxAction.php\");\nrequire_once(\$coreroot.\"fuseboxCircuit.php\");\nrequire_once(\$coreroot.\"fuseboxClassDefinition.php\");\nrequire_once(\$coreroot.\"fuseboxDoFuseaction.php\");\nrequire_once(\$coreroot.\"fuseboxFactory.php\");\nrequire_once(\$coreroot.\"fuseboxLexiconCompiler.php\");\nrequire_once(\$coreroot.\"fuseboxPlugin.php\");\nrequire_once(\$coreroot.\"fuseboxVerb.php\");\nrequire_once(\$coreroot.\"fuseboxWriter.php\");\n\$fb_[\"application\"] = '".str_replace("'","\'",serialize($application))."'; \n\$application = unserialize(\$fb_[\"application\"]); ?".">";
		$fa = fopen($this->approotdirectory."parsed/_app_".$this->appName.".php","w+");
		if ( false == ( fwrite($fa,$fb_['content']) ) ) {
			__cfthrow(array(
				'type'=>'fusebox.errorWritingAppFile',
				'message'=>'Error writing application datafile',
				'detail'=>'Could not write the application datafile to disk'
			));
		}
		fclose($fa);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function writeSlimObjectToDisk() { /* I create a trimmed-down copy of the fusebox object without affecting the active one. I then write it to disk to speed up subsequent calls.*/
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->process = array();
		$this->hasProcess = array('appinit'=>false,'preprocess'=>false,'postprocess'=>false);
		$this->classes = array();
		$this->lexicons = array();
		foreach ( array_keys($this->circuits) as $c ) {
			foreach ( array_keys($this->circuits[$c]->fuseactions) as $f ) {
				$this->circuits[$c]->fuseactions[$f]->nChildren = 0;
				$this->circuits[$c]->fuseactions[$f]->actions = array();
			}
		}
		$application[$this->appKey] = $this;
		$fb_['content'] = "<?"."php \n\$coreroot = '$this->WebRootPathToCore'; \nrequire_once(\$coreroot.\"fuseboxApplication.php\");\nrequire_once(\$coreroot.\"fuseboxAction.php\");\nrequire_once(\$coreroot.\"fuseboxCircuit.php\");\nrequire_once(\$coreroot.\"fuseboxPlugin.php\");\nrequire_once(\$coreroot.\"fuseboxFactory.php\");\n\$fb_[\"application\"] = '".str_replace("'","\'",serialize($application))."'; \n\$application = unserialize(\$fb_[\"application\"]); ?".">";
		$fa = fopen($this->approotdirectory."parsed/app_".$this->appName.".php","w+");
		if ( false == ( fwrite($fa,$fb_['content']) ) ) {
			__cfthrow(array(
				'type'=>'fusebox.errorWritingAppFile',
				'message'=>'Error writing application datafile',
				'detail'=>'Could not write the application datafile to disk'
			));
		}
		fclose($fa);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
}
?>
