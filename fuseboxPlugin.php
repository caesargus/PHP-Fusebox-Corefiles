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
class FuseboxPlugin { //I represent a plugin declaration.

	var $name;
	var $fuseboxApplication;
	var $phase;
	var $path;
	var $template;
	var $rootPath;
	var $parameters;
	var $paramVerbs;
	
	function FuseboxPlugin /*I am the constructor.*/ (
			$phase, //I am the phase with which this plugin is associated.
			$pluginXML, //I am the XML representation of this plugin's declaration.
			&$fbApp //I am the fusebox application object.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
	
		$i = 0;
		$n = count($pluginXML['xmlChildren']);
		$attr = 0;
		$nAttrs = 2;
		$verbChildren = array();
		$factory =& $fbApp->getFuseactionFactory();
		$ext = "." . $fbApp->scriptFileDelimiter;
		
		if ( !array_key_exists("name",$pluginXML['xmlAttributes']) ) {
			__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
				'message'=>"Required attribute is missing",
				'detail'=>"The attribute 'name' is required, for a '$phase' plugin declaration in fusebox.xml."
			));
		}
		
		$this->name = $pluginXML['xmlAttributes']['name'];
		$this->fuseboxApplication =& $fbApp;

		if ( !array_key_exists("template",$pluginXML['xmlAttributes']) ) {
			__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
				'message'=>"Required attribute is missing",
				'detail'=>"The attribute 'template' is required, for the '".$this->getName()."' plugin declaration in fusebox.xml."
			));
		}

		$this->phase = $phase;
		if ( $pluginXML['xmlName'] == "plugin" ) {
			$this->path = $fbApp->getPluginsPath();
			if ( array_key_exists("path",$pluginXML['xmlAttributes']) ) {
				$this->path .= str_replace("\\","/",$pluginXML['xmlAttributes']['path']);
				$nAttrs = 3;
			}
			if ( substr($this->path,-1) != "/" ) {
				$this->path .= "/";
			}
			$this->template = $pluginXML['xmlAttributes']['template'];
			if ( strlen($this->template) > 4 || substr($this->template,-4) != $ext ) {
				$this->template .= $ext;
			}
			$this->rootpath =
					$fbApp->relativePath($fbApp->getApplicationRoot() .
													$this->path,$fbApp->getApplicationRoot());
			/* remove pairs of directory/../ to form canonical path: */
			while ( strpos($this->rootpath,'/../') !== false ) {
				$this->rootpath = ereg_replace("[^\\.:/]*/\\.\\./","",$this->rootpath);
			}
			if ( $fbApp->strictMode && count($pluginXML['xmlAttributes']) != $nAttrs ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
					'message'=>"Unexpected attributes",
					'detail'=>"Unexpected attributes were found in the '".$this->getName()."' plugin declaration in fusebox.xml."
				));
			}
			$this->parameters = $pluginXML['xmlChildren'];
			$this->paramVerbs = array();
			for ( $i = 0  ; $i < $n ; $i++ ) {
				if ( !array_key_exists("name",$this->parameters[$i]['xmlAttributes']) ) {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
						'message'=>"Required attribute is missing",
						'detail'=>"The attribute 'name' is required, for a 'parameter' to the '".$this->getName()."' plugin declaration in fusebox.xml."
					));
				}
				if ( !array_key_exists("value",$this->parameters[$i]['xmlAttributes']) ) {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
						'message'=>"Required attribute is missing",
						'detail'=>"The attribute 'value' is required, for a 'parameter' to the '".$this->getName()."' plugin declaration in fusebox.xml."
					));
				}
				if ( $fbApp->strictMode && count($this->parameters[$i].xmlAttributes) != 2 ) {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
						'message'=>"Unexpected attributes",
						'detail'=>"Unexpected attributes were found in the '".$this->parameters[$i]['xmlAttributes']['name']."' parameter of the '$this->getName()' plugin declaration in fusebox.xml."
					));
				}
				$attr = array();
				$attr['name'] = 'myFusebox->plugins["'.$this->getName().'"]["parameters"]["'.$this->parameters[$i]['xmlAttributes']['name'].'"]';
				$attr['value'] = $this->parameters[$i]['xmlAttributes']['value'];
				$this->paramVerbs[$i] = $factory->create("set",$this,$attr,$verbChildren);
			}
		} else {
			__cfthrow(array( 'type'=>"fusebox.badGrammar.illegalDeclaration", 
				'message'=>"Illegal declaration", 
				'detail'=>"The XML entity '{$pluginXML['xmlName']}' was found where a plugin declaration was expected in fusebox.xml."
			));
		}
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;
		
	}
	
	function compile /*I compile this plugin object.*/ (
			&$writer //I am the parsed file writer object. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$i = 0;
		$n = count($this->paramVerbs);
		$file = "";
		$p = "";
		
		if ( $_REQUEST['__fusebox']['SuppressPlugins'] ) {
			return;
		}
		switch ( $this->phase ) {
			case 'processError' :
			case 'fuseactionException' :
				$fplFilename = $this->fuseboxApplication->getApplicationRoot().$this->path.$this->template;
				$fpl = fopen($fplFilename,'r');
				$file = fread($fpl,filesize($fplFilename));
				fclose($fpl);
				$writer->rawPrintln($file);
				break;
			default :
				for ( $i = 0  ; $i < $n ; $i++ ) {
					$this->paramVerbs[$i]->compile($writer);
				}
				$p = $writer->setPhase($this->phase);
				$writer->println('$myFusebox->thisPlugin = "'.$this->getName().'";');
				$writer->_print('include(');
				$writer->_print('$application[$FUSEBOX_APPLICATION_KEY]->WebRootPathToappRoot."'.$this->path.$this->template);
				$writer->println('");');
				$writer->setPhase($p);
				break;
		}
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';

	}
	
	function getName() { //I return the name of the plugin.
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->name;
		
	}

	function &getCircuit() { //I return the enclosing application object. This is an edge case to allow code that works with fuseactions to work with plugins too
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->fuseboxApplication;
	
	}
	
}
?>