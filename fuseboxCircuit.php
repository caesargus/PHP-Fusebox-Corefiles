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
class FuseboxCircuit { //I represent a circuit.
	var $fuseboxApplication;
	var $alias;
	var $fuseboxLexicon;
	var $customAttributes;
	var $originalPath;
	var $appPath;
	var $lexicons;
	var $relativePath;
	var $parent;
	
	function FuseboxCircuit /*I am the constructor.*/ (
			&$fbApp, //I am the fusebox application object.
			$alias, //I am the circuit alias.
			$path, //I am the path from the application root to the circuit directory.
			$parent, //I am the alias of the parent circuit.
			&$myFusebox //I am the myFusebox data structure.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->fuseboxApplication =& $fbApp;
		$this->alias = $alias;

		$factory =& $this->fuseboxApplication->getFuseactionFactory();
		$this->fuseboxLexicon = $factory->getBuiltinLexicon();
				
		$this->customAttributes = array();
		
		$this->originalPath = $path;
		$this->parent = $parent;
		$this->appPath = $this->fuseboxApplication->getApplicationRoot();
		$this->lexicons = array();
		
		$this->relativePath = str_replace("\\","/",$path);
		if ( strlen($this->relativePath) && substr($this->relativePath,-1) != "/" ) {
			$this->relativePath .= "/";
		}
		$this->path = $this->relativePath;
		$this->fullPath = $this->appPath . $this->relativePath;
		/* remove pairs of directory/../ to form canonical path: */
		while ( strpos($this->fullPath,'/../') !== false ) {
			$this->fullPath = ereg_replace("[^\\.:/]*/\\.\\./","",$this->fullPath);
		}
		$this->rootPath = $this->fuseboxApplication->relativePath($this->fullPath,$this->appPath);

		$this->reload($myFusebox);
				
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;

	}
	
	function &reload /*I reload the circuit file and build the in-memory structures from it.*/ (
			&$myFusebox //I am the myFusebox data structure.
		) {

		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$circuitFile = "circuit.xml.php";
		$circuitXML = "";
		$circuitCode = "";
		$needToLoad = true;
		$circuitFiles = 0;

		if ( array_key_exists("timestamp",get_object_vars($this)) && $circuitFiles = opendir($this->fullPath) ) {
			$found = false;
			while ( false != ( $file = readdir($circuitFiles) ) && !$found ) {
				if ( strpos('circuit.xml',$file) === 0 ) {
					$needToLoad = ( filemtime($this->fullPath.$file) > $this->timestamp );
					$found = true;
				}
			/* else ignore the ambiguity */
			}
		}
		require_once("udf_XMLUtils.php");
		if ( $needToLoad ) {
			if ( $this->fuseboxApplication->debug ) {
				$myFusebox->trace("Compiler","Loading $this->alias circuit.xml file");
				//$myFusebox->trace("Compiler","Loading $this->getAlias() circuit.xml file");
			}
			/* attempt to load circuit.xml(.php): */
			if ( !file_Exists($this->fullPath . $circuitFile) ) {
				$circuitFile = "circuit.xml";
			}
		do {
				$okay = false;
				$this->circuitPath = $this->fullPath . $circuitFile;
				if ( false == ( $fc = @fopen($this->circuitPath,'r') ) ) break;
				if ( false == ( $circuitXML = @fread($fc,filesize($this->circuitPath)) ) ) break;
				if ( false == ( @fclose($fc) ) ) break;
				$okay = true;
			} while ( false );
			if ( !$okay ) {
				if ( $this->fuseboxApplication->allowImplicitCircuits == "true" ) {
						$circuitXML = "<circuit/>";
				} else {
					__cfthrow(array( 'type'=>"fusebox.missingCircuitXML", 
						'message'=>"missing circuit.xml", 
						'detail'=>"The circuit xml file, $circuitFile, for circuit $this->alias could not be found."
					));
				}
			}
			
			do {
				$okay = false;
				if ( false == ( $circuitCode = xmlParse($circuitXML,$this->fuseboxApplication->encodings) ) ) break;
				$okay = true;
			} while ( false );
			if ( !$okay ) {
				__cfthrow(array( 'type'=>"fusebox.circuitXMLError", 
					'message'=>"Error reading circuit.xml", 
					'detail'=>"A problem was encountered while reading the circuit file $circuitFile for circuit ".$this->alias.". This is usually caused by unmatched XML tag-pairs. Close all XML tags explicitly or use the / (slash) short-cut."
				));
			}
	
			if ( $circuitCode['xmlRoot']['xmlName'] != "circuit" ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.badCircuitFile",
					'detail'=>"Circuit file does contain 'circuit' XML", 
					'message'=>"Circuit file ".$this->circuitPath." does not contain 'circuit' as the root XML node."
				));
			}
			if ( array_key_exists("access",$circuitCode['xmlRoot']['xmlAttributes']) ) {
				if ( !in_array($circuitCode['xmlRoot']['xmlAttributes']['access'],array("private","internal","public")) ) {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.illegalAccess",
						'message'=>"Circuit access illegal",
						'detail'=>"The 'access' value '{$circuitCode['xmlRoot']['xmlAttributes']['access']}' is illegal in Circuit ".$this->getAlias().". 'private', 'internal' or 'public' are the only legal values."
					));
				}
				$this->access = $circuitCode['xmlRoot']['xmlAttributes']['access'];
			} else {
				$this->access = "internal";
			}
			if ( array_key_exists("permissions",$circuitCode['xmlRoot']['xmlAttributes']) ) {
				$this->permissions = $circuitCode['xmlRoot']['xmlAttributes']['permissions'];
			} else {
				$this->permissions = "";
			}
	
			$this->loadLexicons($circuitCode);		
			$this->loadPreAndPostFuseactions($circuitCode);
			$this->loadFuseactions($circuitCode);
			$this->circuitFile = $circuitFile;
		
			$this->timestamp = microtime();
		}
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;
		
	}

	function compile /*I compile a given fuseaction within this circuit.*/ (
			&$writer, //I am the parsed file writer object. I am required but it's faster to specify that I am not required.
			$fuseaction //I am the name of the fuseaction to compile. I am required but it's faster to specify that I am not required.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		// if ( array_key_exists('home',$GLOBALS['application']['fusebox']->circuits) ) echo "<br />".get_class($this).'.compile start: '.$GLOBALS['_fba']->circuits['home']->alias;
		$f = $writer->setFuseaction($fuseaction);
		$i = 0;
		$n = 0;
		$this->compilePreOrPostFuseaction($writer,"pre");
		
		if ( !array_key_exists($fuseaction,$this->fuseactions) ) {
			__cfthrow(array( 'type'=>"fusebox.undefinedFuseaction", 
				'message'=>"undefined Fuseaction", 
				'detail'=>"You specified a Fuseaction of $fuseaction which is not defined in Circuit ".$this->alias."."
			));
		}
		$this->fuseactions[$fuseaction]->compile($writer);
		$this->compilePreOrPostFuseaction($writer,"post");
		$writer->setFuseaction($f);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function compilePreOrPostFuseaction /*I compile the pre/post-fuseaction for a circuit.*/ (
			&$writer, //I am the parsed file writer object. I am required but it's faster to specify that I am not required.
			$preOrPost //I am either 'pre' or 'post' to indicate whether this is a prefuseaction or a postfuseaction. I am required but it's faster to specify that I am not required.
		) {
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$c = "";

		if ( $this->hasAction[$preOrPost] ) {
			if ( $preOrPost == "pre" && $this->callsuper["pre"] == "true" && $this->hasParent() ) {
				$_getparent = $this->getParent();
				$_getparent->compilePreOrPostFuseaction($writer,$preOrPost);
			}
			//$c = $writer->setCircuit($this->getAlias());
			$c = $writer->setCircuit($this->alias);
			$this->action[$preOrPost]->compile($writer);
			$writer->setCircuit($c);
			if ( $preOrPost == "post" && $this->callsuper["post"] == "true" && $this->hasParent() ) {
				$thisParent = $this->getParent();
				$thisParent->compilePreOrPostFuseaction($writer,$preOrPost);
			}
		}
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	
	}
	
	function buildCircuitTrace() { //I build the 'circuit trace' structure - the array of parents. Required for Fusebox 4.1 compatibility.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$c = $this->getParentName();
		$seen = array();
		
		//$seen[$this->getAlias()] = true;
		$seen[$this->alias] = true;
		$this->circuitTrace = array();
		//$this->circuitTrace[] = $this->getAlias();
		$this->circuitTrace[] = $this->alias;
		while ( $c != '' ) {
			if ( array_key_exists($c,$seen) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.circularParent", 
					'message'=>"Circular parent for Circuit", 
					'detail'=>"You specified a parent Circuit of $c (for Circuit ".$this->getAlias().") which creates a circular dependency."
				));
			}
			$seen[$c] = true;
			if ( !array_key_exists($c,$this->fuseboxApplication->circuits) ) {
				__cfthrow(array( 'type'=>"fusebox.undefinedCircuit", 
					'message'=>"undefined Circuit", 
					'detail'=>"You specified a parent Circuit of $c (for Circuit ".$this->getAlias().") which is not defined."
				));
			}
			$this->circuitTrace[] = $c;
			$c = $this->fuseboxApplication->circuits[$c]->getParentName();
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function getOriginalPath() { //I return the original relative path specified in the circuit declaration.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->originalPath;
	
	}
	
	function getCircuitRoot() { //I return the full file system path to the circuit directory.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->fullPath;
	
	}

	function getCircuitXMLFilename() { //I return the actual name of the circuit XML file: circuit.xml or circuit.xml.php.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->circuitFile;
	
	}

	function getParentName() { //I return the name (alias) of this circuit's parent.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->parent;
	
	}

	function hasParent() { //I return true if this circuit has a parent, otherwise I return false.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return ( $this->getParentName() != "" );
	
	}

	function &getParent() { //I return this circuit's parent circuit object. I throw an exception if hasParent() returns false.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		/*
			note that this will throw an exception if the circuit has no parent
			code should call hasParent() first
		*/
		return $this->fuseboxApplication->circuits[$this->getParentName()];
	
	}

	function getPermissions /*I return the aggregated permissions for this circuit.*/ (
			$useCircuitTrace //I indicate whether or not to inherit the parent circuit's permissions if this circuit has no permissions specified.
		) {
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		if ( $this->permissions == "" && $useCircuitTrace && $this->hasParent() ) {
			$thisParent = $this->getParent();
			return $thisParent->getPermissions($useCircuitTrace);
		} else {
			return $this->permissions;
		}
	
	}
	
	function getRelativePath() { //I return the normalized relative path from the application root to this circuit's directory.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->relativePath;
	
	}
	
	function &getFuseactions() { //I return the structure containing the definitions of the fuseactions within this circuit.
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->fuseactions; 
		
	}
	
	function getLexiconDefinition /*I return the definition of the specified lexicon.*/ (
			$namespace //I am the namespace whose lexicon is to be retrieved. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		if ( $namespace == $this->fuseboxLexicon['namespace'] ) {
			return $this->fuseboxLexicon;
		} else {
			return $this->lexicons[$namespace];
		}

	}
	
	function getAccess() { //I return the access specified for this circuit.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->access;
	
	}
	
	function getAlias() { //I return the circuit alias.
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->alias;
	
	}
	
	function &getApplication() { //I return the fusebox application object.
	
		return $this->fuseboxApplication;
	
	}
	
	function getCustomAttributes /*I return any custom attributes for the specified namespace prefix.*/ (
			$ns //I am the namespace for which to return custom attributes.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		if ( array_key_exists($ns,$this->customAttributes) ) {
			/* we structCopy() this so folks can't poke values back into the metadata! */
			//return $structCopy($this->customAttributes[$ns]);
			return $this->customAttributes[$ns];
		} else {
			return array();
		}
		
	}
	
	function loadLexicons /*I load the lexicon definitions and custom attributes out of the namespace declarations in the circuit tag.*/ (
			$circuitCode //I am the XML representation of the circuit file.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$attributes = $circuitCode['xmlRoot']['xmlAttributes'];
		$attr = "";
		$aLex = "";
		$ns = "";
		$strict = $this->fuseboxApplication->strictMode;
		
		/* pass 1: pull out any namespace declarations */
		foreach ( array_keys($attributes) as $attr ) {
			if ( strlen($attr) > 6 && substr($attr,0,6) == "xmlns:" ) {
				/* found a namespace declaration, pull it out: */
				$aLex = array();
				$aLex['namespace'] = explode(":",$attr);
				$aLex['namespace'] = $aLex['namespace'][1];
				if ( is_object($this->fuseboxLexicon) && $aLex['namespace'] == $this->fuseboxLexicon->namespace ) {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.reservedName",
						'message'=>"Attempt to use reserved namespace", 
						'detail'=>"You have attempted to declare a namespace '{$aLex['namespace']}' (in Circuit ".$this->getAlias().") which is reserved by the Fusebox framework."
					));
				}
				$aLex['path'] = $this->fuseboxApplication->getWebRootPathToappRoot() . $this->fuseboxApplication->lexiconPath . $attributes[$attr];
				$this->lexicons[$aLex['namespace']] = $aLex;
				$this->customAttributes[$aLex['namespace']] = array();
			}
		}
		
		/* pass 2: pull out any custom attributes */
		foreach ( array_keys($attributes) as $attr ) {
			$arAttr = explode(":",$attr);
			if ( count($arAttr) == 2 && $arAttr[count($arAttr)-1] != "" ) {
				/* looks like a custom attribute: */
				list($ns,$nsAttr) = $arAttr;
				if ( $ns == "xmlns" ) {
					/* special case - need to ignore xmlns:foo="bar" */
				} else if ( array_key_exists($ns,$this->customAttributes) ) {
					$this->customAttributes[$ns][$nsAttr] = $attributes[$attr];
				} else {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.undeclaredNamespace", 
						'message'=>"Undeclared lexicon namespace", 
						'detail'=>"The lexicon prefix '$ns' was found on a custom attribute in the <circuit> tag of Circuit ".$this->getAlias()." but no such lexicon namespace has been declared."
					));
				}
			} else if ( $strict and !in_array($attr,array("access","permissions")) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
					'message'=>"Unexpected attributes",
					'detail'=>"Unexpected attributes were found in the 'circuit' tag of the '".$this->getAlias()."' circuit.xml file."
				));
			}
		}
				
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function loadPreAndPostFuseactions /*I load the prefuseaction and postfuseaction definitions from the circuit file.*/ (
			$circuitCode //I am the XML representation of the circuit file.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->hasAction = array();
		$this->action = array();
		$this->callsuper = array();
		$this->loadPrePostFuseaction($circuitCode,"pre");
		$this->loadPrePostFuseaction($circuitCode,"post");
				
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function loadPrePostFuseaction /*I load the either a prefuseaction or a postfuseaction definition from the circuit file.*/ (
			$circuitCode, //I am the XML representation of the circuit file.
			$prePost //I specify whether to load a 'pre'fuseaction or a 'post'fuseaction.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$children = xmlSearch($circuitCode,"//circuit/{$prePost}fuseaction",true);
		$i = 0;
		$n = count($children);
		$nAttrs = 0;
		
		if ( $n == 0 ) {
			$this->hasAction[$prePost] = false;
		} else if ( $n == 1 ) {
			$this->hasAction[$prePost] = true;
			if ( array_key_exists("callsuper",$children[0]['xmlAttributes']) ) {
				if ( !in_array($children[0]['xmlAttributes']['callsuper'],array("true","false")) ) {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.invalidAttributeValue",
						'message'=>"Attribute has invalid value", 
						'detail'=>"The attribute 'callsuper' must either be \"true\" or \"false\", for a '{$prePost}fuseaction' in Circuit ".$this->alias."."
					));
				}
				$nAttrs = 1;
				$this->callsuper[$prePost] = $children[0]['xmlAttributes']['callsuper'];
			} else {
				$this->callsuper[$prePost] = false;
			}
			if ( $this->fuseboxApplication->strictMode && count($children[0]['xmlAttributes']) != $nAttrs ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
					'message'=>"Unexpected attributes",
					'detail'=>"Unexpected attributes found on '{$prePost}fuseaction' in Circuit ".$this->getAlias()."."
				));
			}
			require_once("fuseboxAction.php");
			if ( !isPHP5() ) { 
				eval('$this->action[$prePost] =& new FuseboxAction($this,"\${$prePost}fuseaction","internal",$children[0]["xmlChildren"]);'); 
			} else {
				$this->action[$prePost] = new FuseboxAction($this,
							"\${$prePost}fuseaction",
								"internal",
									$children[0]['xmlChildren']); 
			}
		} else {
			__cfthrow(array( 'type'=>"fusebox.badGrammar.nonUniqueDeclaration", 
				'message'=>"Declaration was not unique", 
				'detail'=>"More than one &lt;{$prePost}fuseaction&gt; declaration was found in Circuit $this->getAlias()."
			));
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function loadFuseactions /*I load all of the fuseaction definitions from the circuit file.*/ (
			$circuitCode //I am the XML representation of the circuit file.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$children = xmlSearch($circuitCode,"//circuit/fuseaction",true);
		$i = 0;
		$n = count($children);
		$attribs = 0;
		$attr = "";
		$ns = "";
		$customAttribs = 0;
		$access = "";
		$permissions = "";
		$strict = $this->fuseboxApplication->strictMode;
		
		$this->fuseactions = array();
		for ( $i = 0 ; $i < $n ; $i++ ) {
			/* default fuseaction access to circuit access */
			$access = $this->access;
			/* default fuseaction permissions to null */
			$permissions = "";
			$attribs = $children[$i]['xmlAttributes'];
			
			if ( !array_key_exists("name",$attribs) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'name' is required, for a 'fuseaction' declaration in circuit ".$this->getAlias()."."
				));
			}

			/* scan for custom attributes */
			$customAttribs = array();
			foreach ( array_keys($attribs) as $attr ) {

				switch ( $attr ) {
					
					case "name" :
						if ( array_key_exists($attribs['name'],$this->fuseactions) ) {
							__cfthrow(array( 'type'=>"fusebox.overloadedFuseaction", 
								'message'=>"overloaded Fuseaction", 
								'detail'=>"You referenced a fuseaction, {$attribs['name']}, which has been defined multiple times in circuit ".$this->getAlias().". Fusebox does not allow overloaded methods."
							));
						}
						break;
					
					case "access" :
						$access = $attribs['access'];
						if ( !in_array($access,array("private","internal","public")) ) {
							__cfthrow(array( 'type'=>"fusebox.badGrammar.illegalAccess",
								'message'=>"Fuseaction access illegal",
								'detail'=>"The 'access' value '{$access}' is illegal on Fuseaction {$attribs['name']} in Circuit ".$this->getAlias().". 'private', 'internal' or 'public' are the only legal values."
							));
						}
						break;
					
					case "permissions" :
						$permissions = $attribs['permissions'];
						break;
					
					default :
						$arAttr = explode(":",$attr);
						if ( count($arAttr) == 2 && strlen($arAttr[1]) > 0 ) {
							/* looks like a custom attribute: */
							$ns = $arAttr[0];
							if ( array_key_exists($ns,$this->customAttributes) ) {
								$customAttribs[$ns][$arAttr[1]] = $attribs[$attr];
							} else {
								__cfthrow(array( 'type'=>"fusebox.badGrammar.undeclaredNamespace", 
									'message'=>"Undeclared lexicon namespace", 
									'detail'=>"The lexicon prefix '{$ns}' was found on a custom attribute in the Fuseaction {$attribs['name']} in Circuit ".$this->getAlias()." but no such lexicon namespace has been declared."
								));
							}
						
						} else if ( $strict ) {
							__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
								'message'=>"Unexpected attributes",
								'detail'=>"Unexpected attribute '{$attr}' found on Fuseaction {$attribs['name']} in Circuit ".$this->getAlias()."."
							));
						}
						break;
				}
			}
			require_once("fuseboxAction.php");
			if ( !isPHP5() ) { eval('$this->fuseactions[$attribs["name"]] =& new FuseboxAction($this,$attribs["name"],$access,$children[$i]["xmlChildren"],false,$customAttribs);'); } else { $this->fuseactions[$attribs['name']] = new FuseboxAction($this,$attribs['name'],$access,$children[$i]['xmlChildren'],false,$customAttribs); }
			/* FB41 security plugin compatibility: */
			$this->fuseactions[$attribs['name']]->permissions = $permissions;
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
}
?>