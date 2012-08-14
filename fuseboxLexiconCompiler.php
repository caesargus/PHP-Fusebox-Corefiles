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
class FuseboxLexiconCompiler { //I compile a lexicon verb. I am created for each verb that needs to be compiled and I provide the thread-safe context in which that verb is compiled. That includes the various fb_* methods used to write to the parsed file.

	var $fb_writer;
	var $fb_;
	var $lexiconInfo;
	
	function &init /*I am the constructor.*/ (
			&$writer, //I am the parsed file writer object. I am required but it's faster to specify that I am not required.
			&$verbInfo, //I am the verb compilation context. I am required but it's faster to specify that I am not required.
			&$lexiconInfo //I am the lexicon definition that supports this verb. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->fb_writer =& $writer;
		//$this->fb_ = array();
		$this->fb_['verbInfo'] =& $verbInfo;
		$this->lexiconInfo =& $lexiconInfo;
		$this->compiled = false;
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;
		
	}
	
	function compile() { //I compile a lexicon verb by including its implementation file.

		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		// if ( array_key_exists('home',$GLOBALS['application']['fusebox']->circuits) ) echo "<br />".get_class($this).'.compile start: '.$GLOBALS['_fba']->circuits['home']->alias;
		$info =& $this->lexiconInfo;
		$lexiconFile = $info->lexicon['path'];
		if ( !array_key_exists('targetCircuit',$this->fb_) ) $this->fb_['targetCircuit'] = $info->action->circuit;
		$lexiconFile .= $info->verb;
		$lexiconFile .= ".php";
		do {
			$okay = false;
			$fb_ =& $this->fb_;
			//echo '<br>including '.$lexiconFile;
			include($lexiconFile);
			//if ( false == ( @include($lexiconFile) ) ) break;
			$this->fb_ =& $fb_;
			$okay = true;
		} while ( false );
		if ( !$okay ) {
			__cfthrow(array('type'=>"fusebox.badGrammar.missingImplementationException",
				'message'=>"Bad Grammar verb in circuit file",
				'detail'=>"The implementation file for the '{$info['verb']}' verb from the '{$info['lexicon']['namespace']}'" .
					" custom lexicon could not be found.  It is used in the '".$this->fb_['verbInfo']['circuit'].".".$this->fb_['verbInfo']['fuseaction']."' fuseaction."
			));
		}
		if ( $this->fb_['verbInfo']['executionMode'] == "end" ) {
			if ( !$this->compiled && (
					array_key_exists('fuseactionException',$this->fb_writer->fuseboxApplication->pluginPhases) ||
					array_key_exists('processError',$this->fb_writer->fuseboxApplication->pluginPhases) 
					) &&
					$this->fb_writer->fuseboxApplication->scriptVersion{0} != '5' ) {
				$this->fb_writer->rawPrintln("if ( \$php_errormsg ) break;");
			}
			$this->compiled = true;
		}
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function fb_appendLine /*I append a line to the parsed file.*/ (
			$lineContent //I am the line of text to append.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->fb_writer->println($lineContent);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function fb_appendIndent() { //I am a no-op provided for backward compatibility.
	}
	
	function fb_appendSegment /*I append a segment of text to the parsed file.*/ (
			$segmentContent //I am the segment of text to append.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->fb_writer->_print($segmentContent);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function fb_appendNewline() { //I append a newline to the parsed file.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$this->fb_writer->println("");
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function fb_increaseIndent() { //I am a no-op provided for backward compatibility.
	}
	
	function fb_decreaseIndent() { //I am a no-op provided for backward compatibility.
	}
	
	function fb_throw /*I throw the specified exception.*/ (
			$type, //I am the type of exception to throw.
			$message, //I am the message to include in the thrown exception.
			$detail //I am the detail to include in the thrown exception.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		__cfthrow(array( 'type'=>$type, 'message'=>$message, 'detail'=>$detail));

		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
}
?>