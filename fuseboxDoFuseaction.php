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
class FuseboxDoFuseaction { //I am the representation of the do and fuseaction verbs.
	
	var $action;
	var $attributes;
	var $children;
	var $numChildren;
	var $verb;
	
	function FuseboxDoFuseaction /*I am the constructor.*/ (
			&$action, //I am the enclosing fuseaction object.
			$attributes, //I am the attributes for this verb.
			$children, //I am the XML representation of any children this verb has.
			$verb //I am the name of this verb.
		) {
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
					
		$nAttrs = 1;
		
		$this->action =& $action;
		$this->attributes = array();
		$this->children = $children;
		$this->numChildren = count($this->children);
		$this->verb = $verb;
		
		$circuit =& $this->action->getCircuit();
		$app =& $circuit->getApplication();
		/*
			validate the attributes:
			action - required
			append - boolean - optional
			prepend - boolean - optional
			overwrite - boolean - optional
			contentvariable - optional
		*/
		if ( array_key_exists("action",$attributes) ) {
			$this->attributes['action'] = $attributes['action'];
		} else {
			__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
				'message'=>"Required attribute is missing",
				'detail'=>"The attribute 'action' is required, for a '".$this->verb."' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
			));
		}
		if ( $this->verb == "fuseaction" && count(explode(".",$this->attributes['action'])) != 2 ) {
			/* illegal: there is no circuit associated with a (global) action */
			__cfthrow(array( 'type'=>"fusebox.badGrammar.invalidAttributeValue",
				'message'=>"Attribute has invalid value", 
				'detail'=>"The attribute 'action' must be a fully-qualified fuseaction, for a 'fuseaction' verb in a global pre/post process."
			));
		}		
		
		if ( array_key_exists("append",$attributes) ) {
			$this->attributes['append'] = $attributes['append'];
			$nAttrs++;
			if ( !in_array($this->attributes['append'],array("true","false")) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.invalidAttributeValue",
					'message'=>"Attribute has invalid value",
					'detail'=>"The attribute 'append' must either be \"true\" or \"false\", for a '".$this->verb."' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
				));
			}
			if ( !array_key_exists("contentvariable",$attributes) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'contentvariable' is required when the attribute 'append' is present, for a '".$this->verb."' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()
				));
			}
		} else {
			$this->attributes['append'] = "false";
		}

		if ( array_key_exists("prepend",$attributes) ) {
			$this->attributes['prepend'] = $attributes['prepend'];
			$nAttrs++;
			if ( !in_array($this->attributes['prepend'],array("true","false")) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.invalidAttributeValue",
					'message'=>"Attribute has invalid value",
					'detail'=>"The attribute 'prepend' must either be \"true\" or \"false\", for a '".$this->verb."' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
				));
			}
			if ( !array_key_exists("contentvariable",$attributes) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'contentvariable' is required when the attribute 'append' is present, for a '".$this->verb."' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
				));
			}
		} else {
			$this->attributes['prepend'] = "false";
		}

		if ( array_key_exists("overwrite",$attributes) ) {
			$this->attributes['overwrite'] = $attributes['overwrite'];
			$nAttrs++;
			if ( !in_array($this->attributes['overwrite'],array("true","false")) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.invalidAttributeValue",
					'message'=>"Attribute has invalid value",
					'detail'=>"The attribute 'overwrite' must either be \"true\" or \"false\", for a '".$this->verb."' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
				));
			}
			if ( !array_key_exists("contentvariable",$attributes) ) {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
					'message'=>"Required attribute is missing",
					'detail'=>"The attribute 'contentvariable' is required when the attribute 'append' is present, for a '".$this->verb."' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
				));
			}
		} else {
			$this->attributes['overwrite'] = "true";
		}

		if ( array_key_exists("contentvariable",$attributes) ) {
			$this->attributes['contentvariable'] = $attributes['contentvariable'];
			$nAttrs++;
		}

		if ( $app->strictMode && count($attributes) != $nAttrs ) {
			__cfthrow(array( 'type'=>"fusebox.badGrammar.unexpectedAttributes",
				'message'=>"Unexpected attributes",
				'detail'=>"Unexpected attributes were found in a '".$this->verb."' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
			));
		}
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
		return $this;
		
	}
	
	function compile /*I compile this do/fuseaction verb.*/ (
			&$writer //I am the parsed file writer object. I am required but it's faster to specify that I am not required.
		) {

		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		// if ( array_key_exists('home',$GLOBALS['application']['fusebox']->circuits) ) echo "<br />".get_class($this).'.compile start: '.$GLOBALS['_fba']->circuits['home']->alias;
		$thisCircuit =& $this->action->getCircuit();
		$i = 0;
		$n = 0;
		$myFB =& $writer->getMyFusebox();
		$app =& $writer->fuseboxApplication;
		$plugins =& $app->pluginPhases;
		$c = "";
		$f = "";
		$cDotF = "";
		$old_c = "";
		$old_p = "";
		$circuits =& $app->circuits;
		$needTryOnFuseaction = false;

		$arAction = explode(".",$this->attributes['action']);
		if ( count($arAction) > 1 && strlen($arAction[1]) > 0 ) {
			/* action is a circuit.fuseaction pair somewhere */
			list($c,$f) = $arAction;
			$cDotF = $this->attributes['action'];
		} else {
			$c = $thisCircuit->getAlias();
			$f = $this->attributes['action'];
			$cDotF = $c . "." . $f;
		}
		
		if ( array_key_exists($cDotF,$_REQUEST['__fusebox']['fuseactionsDone']) ) {
			__cfthrow(array( 'type'=>"fusebox.badGrammar.recursiveDo", 
				'message'=>"Recursive do is illegal",
				'detail'=>"An attempt was made to compile a fuseaction '$cDotF' that is already being compiled, in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
			));
		}
		$request['__fusebox']['fuseactionsDone'][$cDotF] = true;
		
		$writer->rawPrintln('/* '.$this->verb.' action="'.$this->attributes['action'].'" */');
		if ( $app->debug ) {
			$writer->rawPrintln('$myFusebox->trace("Runtime","&lt;'.$this->verb.' action=\"'.$this->attributes['action'].'\"/&gt;");');
		}
		$old_c = $writer->setCircuit($c);
		$old_f = $writer->setFuseaction($f);
		
		if ( array_key_exists("fuseactionException",$plugins) &&
				count($plugins["fuseactionException"]) > 0 &&
				!$_REQUEST['__fusebox']['SuppressPlugins'] ) {
			$needTryOnFuseaction = true;
			$writer->rawPrintln("do {");
			$writer->rawPrintln('	$php_errormsg = false;');
		}
		
		if ( array_key_exists("preFuseaction",$plugins) ) {
			$n = count($plugins["preFuseaction"]);
			for ( $i = 0 ; $i < $n ; $i++ ) {
				$plugins["preFuseaction"][$i]->compile($writer);
			}
		}
		
		if ( $this->numChildren > 0 ) {
			$this->enterStackFrame($writer);
		}
		
		if ( array_key_exists("contentvariable",$this->attributes) ) {
			if ( $this->attributes['overwrite'] == "false" ) {
				$writer->println('if ( !isset($'.$this->attributes['contentvariable'].') ) {');
			}
			if ( $this->attributes['append'] == "true" || $this->attributes['prepend'] == "true" ) {
				$writer->println('if ( !isset($'.$this->attributes['contentvariable'].') ) $'.$this->attributes['contentvariable'].'="";');
			}
			$writer->println('ob_start();');
			if ( $this->attributes['append'] == "true" ) {
				$writer->println('echo $'.$this->attributes['contentvariable'].';');
			}
		}

		$arAction = explode('.',$this->attributes['action']);
		if ( count($arAction) > 1 && strlen($arAction[1]) > 0 ) {

			if ( !array_key_exists($c,$circuits) ) {
				__cfthrow(array( 'type'=>"fusebox.undefinedCircuit", 
					'message'=>"undefined Circuit", 
					'detail'=>"The fuseboxDoFuseaction received a compile request for a Circuit of $c which is not defined."
				));
			}
			if ( !array_key_exists($f,$circuits[$c]->fuseactions) ) {
				__cfthrow(array( 'type'=>"fusebox.undefinedFuseaction", 
					'message'=>"undefined Fuseaction", 
					'detail'=>"You specified a Fuseaction of $f which is not defined in Circuit $c."
				));
			}
			/* if not in the same circuit, check access is not private */
			if ( $c != $thisCircuit->getAlias() ) {
				if ( $circuits[$c]->fuseactions[$f]->getAccess() == "private" ) {
					__cfthrow(array( 'type'=>"fusebox.invalidAccessModifier", 
						'message'=>"invalid access modifier", 
						'detail'=>"The fuseaction '$c.$f' has an access modifier of private and can only be called from within its own circuit. Use an access modifier of internal or public to make it available outside its immediate circuit."
					));
				}
			}

			$app->compile($writer,$c,$f);

		} else {

			/* action is a fuseaction in this same circuit */
			if ( !array_key_exists($f,$thisCircuit->fuseactions) ) {
				__cfthrow(array( 'type'=>"fusebox.undefinedFuseaction", 
					'message'=>"undefined Fuseaction", 
					'detail'=>"You specified a Fuseaction of $f which is not defined in Circuit $c."
				));
			}

			$thisCircuit->compile($writer,$f);

		}
		
		if ( array_key_exists("contentvariable",$this->attributes) ) {
			if ( $this->attributes['prepend'] == "true" ) {
				$writer->println('echo $'.$this->attributes['contentvariable'].';');
			}
			$writer->println('$'.$this->attributes['contentvariable'].' = ob_get_contents();');
			$writer->println('ob_end_clean();');
			if ( $this->attributes['overwrite'] == "false" ) {
				$writer->println('}');
			}
		}

		if ( $this->numChildren > 0 ) {
			$this->leaveStackFrame($writer);
		}
		
		if ( array_key_exists("postFuseaction",$plugins) ) {
			$n = count($plugins["postFuseaction"]);
			for ( $i = 0 ; $i < $n ; $i++ ) {
				$plugins["postFuseaction"][$i]->compile($writer);
			}
		}

		if ( $needTryOnFuseaction ) {
			$writer->rawPrintln('} while ( false );');
			$writer->rawPrintln('if ( $php_errormsg ) {');
			$n = count($plugins["fuseactionException"]);
			for ( $i = 0 ; $i < $n ; $i++ ) {
				$plugins["fuseactionException"][$i]->compile($writer);
			}
			$writer->rawPrintln('}');
		}

		$writer->setFuseaction($old_f);
		$writer->setCircuit($old_c);

		unset($_REQUEST['__fusebox']['fuseactionsDone'][$cDotF]);
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function enterStackFrame /*I generate code to create a new stack frame and push the scoped $this->*/ (
			$writer //I am the parsed file writer object. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$i = 0;
		$child = 0;
		$match1 = 0;
		$match2 = 0;
		$nameLen = 0;
		$circuit =& $this->action->getCircuit();
		
		$writer->rawPrintln('$myFusebox->enterStackFrame();');
		for ( $i = 0 ; $i < $this->numChildren ; $i++ ) {
			$child = $this->children[$i];
			/* validate the child: it must be <parameter> and have both name= and value= */
			if ( $child['xmlName'] == "parameter" ) {
				if ( !array_key_exists("name",$child['xmlAttributes']) ) {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.requiredAttributeMissing",
						'message'=>"Required attribute is missing",
						'detail'=>"The attribute 'name' is required, for a 'parameter' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
					));
				}
				//$match2 = REFind("[A-Za-z0-9_]*\[A-Za-z0-9_]*",child.xmlAttributes.name,1,true);
				$nameLen = strlen($child['xmlAttributes']['name']);
				if ( ereg('([A-Za-z0-9_]*)',$child['xmlAttributes']['name'],$match1) && 
						strlen($match1[0]) == $nameLen ) {
					/* simple varname: patch up XML to make leaveStackFrame() simpler */
					$child['xmlAttributes']['name'] = 'GLOBALS[\'' . $child['xmlAttributes']['name'] . '\']';
				} elseif ( ereg('([A-Za-z0-9_]*\[\'[A-Za-z0-9_]*\'\])',$child['xmlAttributes']['name'],$match2) && 
						strlen($match2[0]) == $nameLen ) {
					/* scoped varname.varname: nothing to patch up */
				} else {
					__cfthrow(array( 'type'=>"fusebox.badGrammar.invalidAttributeValue",
						'message'=>"Attribute has invalid value",
						'detail'=>"The attribute 'name' must be a simple variable name, optionally qualified by a scope name, for a 'parameter' verb in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
					));
				}
				$writer->rawPrintln('if ( isset($'.$child['xmlAttributes']['name'].') ) {' .
							'$myFusebox->stack["'.$child['xmlAttributes']['name'].'"] = "'.$child['xmlAttributes']['name'].'"; }');
				if ( array_key_exists("value",$child['xmlAttributes']) ) {
					$writer->rawPrintln('$'.$child['xmlAttributes']['name'].' = "'.$child['xmlAttributes']['value'].'";');
				}
			} else {
				__cfthrow(array( 'type'=>"fusebox.badGrammar.illegalVerb", 
					'message'=>"Illegal verb encountered", 
					'detail'=>"The '{$child['xmlName']}' verb is illegal inside a 'do' verb, in fuseaction ".$circuit->getAlias().".".$this->action->getName()."."
				));
			}
		}
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
	function leaveStackFrame /*I generate code to pop the scoped variables and drop the stack frame.*/ (
			&$writer //I am the parsed file writer object. I am required but it's faster to specify that I am not required.
		) {
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '<ul><li>Starting $'.__CLASS__.'->'.__FUNCTION__.'()';
		$i = 0;
		$child = 0;
		$scope = "";
		$qName = "";
		
		for ( $i = 0 ; $i < $this->numChildren ; $i++ ) {
			$child = $this->children[$i];
			$writer->rawPrintln('if ( array_key_exists("'.$child['xmlAttributes']['name'].'",$myFusebox->stack) ) { ' .
						'$'.$child['xmlAttributes']['name'].' = $myFusebox->stack["'.$child['xmlAttributes']['name'].'"]; }');
			$name = $child['xmlAttributes']['name'];
			$writer->rawPrintln('if ( array_key_exists("'.$child['xmlAttributes']['name'].'",$myFusebox->stack) ) { ' .
							'$'.$child['xmlAttributes']['name'].' = $myFusebox->stack["'.$child['xmlAttributes']['name'].'"]; ' .
							'} else {' . 
							'unset($'.$child['xmlAttributes']['name'].'); }');
		}
		$writer->rawPrintln('$myFusebox->leaveStackFrame();');
		
		if ( isset($GLOBALS['attributes']['fusebox.debug']) && $GLOBALS['attributes']['fusebox.debug'] == 'true' ) echo '</li><li>Ending $'.__CLASS__.'->'.__FUNCTION__.'()</li></ul>';
	}
	
}
?>