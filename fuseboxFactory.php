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
Fusebox Corporation. For more information on Fusebox, please see <http://www.fusebox.org;.

*/
class FuseboxFactory { //I am a factory object that creates verb objects.

	var $lexCompPool;
	var $verbLexPool;
	var $fuseboxLexicon;
	
	function FuseboxFactory() { //I am the constructor.
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		
		$this->lexCompPool = 0;
		$this->verbLexPool = 0;
		
		$this->fuseboxLexicon = array();
		$this->fuseboxLexicon['namespace'] = "\$fusebox";
		$this->fuseboxLexicon['path'] = "verbs/";
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;
		
	}
	
	function &create /*I create a verb object.*/ (
			$verb, //I am the name of the verb to create.
			&$action, //I am the enclosing fuseaction object.
			$attributes, //I am the attributes of this verb.
			$children, //I am the XML representation of this verb's children.
			$global=false //I indicate whether this is part of a regular fuseaction (false) or a global fuseaction (true).
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$verbObject = "";
		$_fbc =& $action->getCircuit();
		$_fba =& $_fbc->getApplication();
		
		/* global pre/post process is a special case: */
		if ( $global ) {
			if ( in_array($verb,array("do","fuseaction")) ) {
				/* this is OK, do is deprecated */
				if ( $verb == "do" and $_fba->strictMode ) {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.deprecated", 
						'message'=>"Deprecated feature",
						'detail'=>"Using the 'do' verb in a global pre/post process was deprecated in Fusebox 4.1."
					));
				}
			} else {
				/* no other verbs are allowed */
				__cfthrow(array( 'type'=>"fusebox.badGrammar.illegalVerb",
					'message'=>"Illegal verb encountered", 
					'detail'=>"The '{$verb}' verb is illegal in a global pre/post process."
				));
			}
		} else {
			if ( in_array($verb,array("fuseaction")) ) {
				/* verbs that are only legal in global pre/post process */
				__cfthrow(array( 'type'=>"fusebox.badGrammar.illegalVerb",
					'message'=>"Illegal verb encountered", 
					'detail'=>"The '{$verb}' verb is only legal in a global pre/post process."
				));
			}
		}
		if ( count(explode(":",str_replace(".",":",$verb))) == 2 ) {
			/* must be namespace.verb or namespace:verb */
			require_once("fuseboxVerb.php");
			if ( !isPHP5() ) { eval('$verbObject =& new FuseboxVerb($action, $verb, $attributes, $children);'); } else { $verbObject = new FuseboxVerb($action, $verb, $attributes, $children); }
		} elseif ( in_array($verb,array("do","fuseaction")) ) {
			/* built-in verbs that cannot be implemented as a lexicon */
			require_once("fuseboxDoFuseaction.php");
			if ( !isPHP5() ) { eval('$verbObject =& new FuseboxDoFuseaction($action,$attributes,$children,$verb);'); } else { $verbObject = new FuseboxDoFuseaction($action,$attributes,$children,$verb); }
		} else {
			/* builtin verb implemented as a lexicon */
			require_once("fuseboxVerb.php");
			if ( !isPHP5() ) { 
				eval('$verbObject =& new FuseboxVerb($action, $this->fuseboxLexicon["namespace"] . ":" . $verb,$attributes, $children);'); 
			} else { 
				$verbObject = new FuseboxVerb($action, $this->fuseboxLexicon['namespace'] . ":" . $verb,
							$attributes, $children); 
			}
		}
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $verbObject;
	}
	
	function &createLexiconCompiler() { //I return a lexicon compiler context (either from the pool or a newly created instance).
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( !is_object($this->lexCompPool) ) {
			require_once("fuseboxLexiconCompiler.php");
			if ( !isPHP5() ) { eval('$obj =& new FuseboxLexiconCompiler();'); } else { $obj = new FuseboxLexiconCompiler(); }
		} else {
			$obj =& $this->lexCompPool;
			$this->lexCompPool =& $obj->_next;
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $obj;
	
	}
	
	function freeLexiconCompiler /*I return the lexicon compiler context to the pool.*/ (
			$lexComp //I am the lexicon compiler context to be returned. I am required but it's faster to specify that I am not required.
		) {
	
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$lexComp->_next =& $this->lexCompPool;
		$this->lexCompPool =& $lexComp;
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function getBuiltinLexicon() { //I return the (magic) builtin lexicon.
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this->fuseboxLexicon;
		
	}
	
}
?>
