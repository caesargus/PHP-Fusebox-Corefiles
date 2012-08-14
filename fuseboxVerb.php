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
class FuseboxVerb { //I represent a verb that is implemented as part of a lexicon.
	
	var $action;
	var $attributes;
	var $verb;
	var $children;
	var $factory;
	var $nChildren;
	var $fb41style;
	var $lexicon;
	
	function FuseboxVerb /*I am the constructor.*/ (
			&$action, //I am the enclosing fuseaction object.
			$customVerb, //I am the name of this (custom) verb.
			$attributes, //I am the attributes for this verb.
			&$children //I am the XML representation of any children this verb has.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		list($ns,$this->verb) = explode(':',str_replace('.',':',$customVerb));
		$i = 0;
		$verb = "";
		$thisCircuit =& $action->getCircuit();
		$thisApp =& $thisCircuit->getApplication();
		$this->factory =& $thisApp->getFuseactionFactory();
		
		$this->action =& $action;
		$this->attributes = $attributes;
		/* we will create our children below */
		$this->children = array();
		
		$this->nChildren = count($children);
		
		for ( $i = 0 ; $i < $this->nChildren ; $i++ ) {
			$verb = $children[$i]['xmlName'];
			$this->children[$i] =& $this->factory->create($verb,
						$this->action,
							$children[$i]['xmlAttributes'],
								$children[$i]['xmlChildren']);
		}
		$this->fb41style = (count(explode(".",$customVerb)) == 2);
		if ( $this->fb41style ) {
			$this->lexicon = $thisApp->getLexiconDefinition($ns);
		} else {
			$this->lexicon = $thisCircuit->getLexiconDefinition($ns);
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;
		
	}
	
	function compile /*I compile a custom lexicon verb. I create the thread-safe context and perform the start and end execution, as well as compiling any children.*/ (
			&$writer, //I am the parsed file writer object. I am required but it's faster to specify that I am not required.
			&$context //I am the context in which this verb is compiled. I can be omitted if the verb has no enclosing parent.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		// if ( array_key_exists('home',$GLOBALS['application']['fusebox']->circuits) ) echo "<br />".get_class($this).'.compile start: '.$GLOBALS['_fba']->circuits['home']->alias;
		/*
			the following is purely a device to allow nested custom verbs:
			we pass the struct reference into the lexicon compiler but then we
			fill in the fields here *afterwards* - relies on pass by reference!
			because we are recursive, we need to create a new lexicon compiler
			on each 'call' of the (static) compiler (i.e., this method)
			trust me! -- sean corfield
		*/
		$verbInfo = array();
		$verbInfo['lexicon'] = $this->lexicon['namespace'];
		$verbInfo['lexiconVerb'] = $this->verb;
		$verbInfo['attributes'] = $this->attributes;
		/*
			change to FB41 lexicons (but needed for FB5):
				circuit - alias of current circuit
				fuseaction - name of current fuseaction
				action - fuseaction object for more complex usage
		*/
		$circuit =& $this->action->getCircuit();
		$verbInfo['circuit'] = $circuit->getAlias();
		$verbInfo['fuseaction'] = $this->action->getName();
		$verbInfo['action'] =& $this->action;

		$oCompiler =& $this->factory->createLexiconCompiler();
		$compiler = $oCompiler->init($writer,$verbInfo,$this);
		$i = 0;
		
		if ( $this->fb41style ) {

			/* FB41: just compile the lexicon once with no executionMode */
			$compiler->fb_['verbInfo'] =& $verbInfo;
			$compiler->compile();

		} else {

			/*
				FB5 has new fields in verbInfo:
				skipBody - false, can be set to true by start tag to skip compilation of child tags
				hasChildren - true if there are nested tags, else false
				parent - present if we are nested (the verbInfo of the parent tag)
				executionMode - start|inactive|end, just like custom tags
			*/
			$verbInfo['skipBody'] = false;
			$verbInfo['hasChildren'] = ( $this->nChildren != 0 );

			if ( ( is_string($context) && strlen($context) > 0 ) || ( is_array($context) && count($context) > 0 ) ) {
				$verbInfo['parent'] =& $context;
			}

			$verbInfo['executionMode'] = "start";
			$compiler->fb_['verbInfo'] =& $verbInfo;
			$compiler->compile();

			if ( array_key_exists("skipBody",$verbInfo) && is_bool($verbInfo['skipBody']) && $verbInfo['skipBody'] ) {
				/* the verb decided not to compile its children */
			} else {
				if ( $this->nChildren > 0 ) {
					$verbInfo['executionMode'] = "inactive";
					$compiler->fb_['verbInfo'] =& $verbInfo;
					for ( $i = 0 ; $i < $this->nChildren ; $i++ ) {
						$this->children[$i]->compile($writer,$verbInfo);
					}		
				}
			}

			$verbInfo['executionMode'] = "end";
			$compiler->fb_['verbInfo'] =& $verbInfo;
			$compiler->compile();

		}
		
		$this->factory->freeLexiconCompiler($compiler);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
}
?>