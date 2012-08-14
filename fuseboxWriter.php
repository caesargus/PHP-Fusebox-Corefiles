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
class FuseboxWriter { //I manage the creation of and writing to the parsed files.

	var $fuseboxApplication;
	var $myFusebox;
	var $parsedDir;
	var $phase;
	var $circuit;
	var $fuseaction;
	var $newline;
	var $content;
	
	function FuseboxWriter /*I am the constructor.*/ (
			&$fbApp, //I am the fusebox application object. I am required but it's faster to specify that I am not required.
			&$myFusebox //I am the myFusebox data structure. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->fuseboxApplication =& $fbApp;
		//$this->myFusebox =& $myFusebox;
		$this->parsedDir = $this->fuseboxApplication->getApplicationRoot() . $this->fuseboxApplication->parsePath;
		$this->phase = "";
		$this->circuit = "";
		$this->fuseaction = "";
		
		if ( !is_dir($this->parsedDir) ) {
			do {
				$okay = false;
				if ( false == ( @mkdir($this->parsedDir,"0777" ) ) ) break;
				$okay = true;
			} while ( false );
			if ( !$okay ) {
				__cfthrow(array( 'type'=>"fusebox.missingParsedDirException",
					'message'=>"The 'parsed' directory in the application root directory is missing, and could not be created",
					'detail'=>"You must manually create this directory, and ensure that CF has the ability to write and change files within the directory."
				));
			}
		}

		$this->_reset();

		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;

	}
	
	function &getMyFusebox() {
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $GLOBALS['myFusebox'];
		
	}

	function _reset() { //I reset the phase, circuit and fuseaction as well as initializing the file content object.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';

		$this->lastPhase = "";
		$this->lastCircuit = "";
		$this->lastFuseaction = "";
		$this->content = "";
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';

	}	

	function getNewline() {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return chr(10).chr(13);
	}
	
	function open /*I 'open' the parsed file. In fact I just setup the writing process. The file is only created when this writer object is 'closed'.*/ (
			$filename //I am the name of the parsed file to be created.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->filename = $filename;
		$this->_reset();
		$this->rawPrintln('<'.'?php');
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function close() { //I 'close' the parsed file and write it to disk.
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->rawPrintln('?'.'>');
		do {
			$okay = false;
			$fb_['file2write'] = $this->parsedDir.$this->filename;
			/*
			if ( false == ( $fpf = @fopen($fb_['file2write'],'w') ) ) break;
			if ( false === @fwrite($fpf,$this->content) ) break;
			if ( false == ( @fclose($fpf) ) ) break;
			*/
			$fpf = fopen($fb_['file2write'],'w');
			fwrite($fpf,$this->content);
			fclose($fpf);
			$this->_reset();
			$okay = true;
		} while ( false );
		if ( !$okay ) {
				
				__cfthrow(array( 'type'=>"fusebox.errorWritingParsedFile", 
					'message'=>"An Error during write of Parsed File or Parsing Directory not found.", 
					'detail'=>"Attempting to write the parsed file '".$this->filename."' threw an error. This can also occur if the parsed file directory cannot be found."
				));
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function setPhase /*I remember the currently executing plugin phase.*/ (
			$phase //I am the name of the current phase. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$p = $this->phase;
		
		$this->phase = $phase;
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $p;
		
	}
	
	function setCircuit /*I remember the currently executing circuit alias.*/ (
			$circuit //I am the name of the current circuit. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$c = $this->circuit;
		
		$this->circuit = $circuit;
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $c;
		
	}
	
	function setFuseaction /*I remember the currently executing fuseaction name.*/ (
			$fuseaction //I am the name of the current fuseaction. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$f = $this->fuseaction;
		
		$this->fuseaction = $fuseaction;
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $f;
		
	}
	
	function _print /*I print a string to the parsed file. I set the phase, circuit and fuseaction variables if necessary in the myFusebox structure.*/ (
			$text //I am the string to be printed. I am required but it's faster to specify that I am not required.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( $this->lastPhase != $this->phase ) {
			$this->rawPrintln('$myFusebox->thisPhase = "'.$this->phase.'";');
			$this->lastPhase = $this->phase;
		}
		if ( $this->lastCircuit != $this->circuit ) {
			$this->rawPrintln('$myFusebox->thisCircuit = "'.$this->circuit.'";');
			$this->lastCircuit = $this->circuit;
		}
		if ( $this->lastFuseaction != $this->fuseaction ) {
			$this->rawPrintln('$myFusebox->thisFuseaction = "'.$this->fuseaction.'";');
			$this->lastFuseaction = $this->fuseaction;
		}
		$this->content .= $text;
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function println /*I print a string to the parsed file, followed by a newline. I set the phase, circuit and fuseaction variables if necessary in the myFusebox structure.*/ (
			$text //I am the string to be printed. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->_print($text);
		$this->content .= $this->getNewline();
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function rawPrint /*I print a string to the parsed file, without setting any $this->*/ (
			$text //I am the string to be printed. I am required but it's faster to specify that I am not required.
		) {

		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->content .= $text;

		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function rawPrintln /*I print a string to the parsed file, followed by a newline, without setting any $this->*/ (
			$text //I am the string to be printed. I am required but it's faster to specify that I am not required.
		) {

		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->content .= $text . $this->getNewline();
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
		
}
?>