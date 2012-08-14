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

If one or more of the above conditions are violated, then this license == immediately revoked and 
can be re-instated only upon prior written authorization of the Fusebox Corporation.

THIS SOFTWARE == PROVIDED "AS IS" AND ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
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
if ($fb_['verbInfo']['executionMode'] == "start") {
	$fb_['cir'] =& $fb_['verbInfo']['action']->getCircuit();
	$fb_['app'] =& $fb_['cir']->getApplication();
	// validate attributes
	$fb_['nAttrs'] = 0;
	// required - boolean - default true
	if ( array_key_exists("required",$fb_['verbInfo']['attributes']) ) {
		if ( !in_array($fb_['verbInfo']['attributes']['required'],array("true","false")) ) {
			$this->fb_throw("fusebox.badGrammar.invalidAttributeValue",
						"Attribute has invalid value",
						"The attribute 'required' must either be \"true\" or \"false\", for a 'include' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	} else {
		$fb_['verbInfo']['attributes']['required'] = "true";
	}
	$fb_['nAttrs']++;	// for required - since we default it
	// template - string - required
	if ( !array_key_exists("template",$fb_['verbInfo']['attributes']) ) {
		$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
					"Required attribute is missing",
					"The attribute 'template' is required, for a 'include' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
	}
	$fb_['nAttrs']++;	// for template
	// contentvariable - string - default ""
	if ( !array_key_exists("contentvariable",$fb_['verbInfo']['attributes']) ) {
		$fb_['verbInfo']['attributes']['contentvariable'] = "";
	}
	$fb_['nAttrs']++;	// for contentvariable - since we default it
	// overwrite - boolean - default true
	if ( array_key_exists("overwrite",$fb_['verbInfo']['attributes']) ) {
		if ( !in_array($fb_['verbInfo']['attributes']['overwrite'],array("true","false")) ) {
			$this->fb_throw("fusebox.badGrammar.invalidAttributeValue",
						"Attribute has invalid value",
						"The attribute 'overwrite' must either be \"true\" or \"false\", for a 'include' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	} else {
		$fb_['verbInfo']['attributes']['overwrite'] = "true";
	}
	$fb_['nAttrs']++;	// for overwrite - since we default it
	// append - boolean - default false
	if ( array_key_exists("append",$fb_['verbInfo']['attributes']) ) {
		if ( !in_array($fb_['verbInfo']['attributes']['append'],array("true","false")) ) {
			$this->fb_throw("fusebox.badGrammar.invalidAttributeValue",
						"Attribute has invalid value",
						"The attribute 'append' must either be \"true\" or \"false\", for a 'include' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	} else {
		$fb_['verbInfo']['attributes']['append'] = "false";
	}
	$fb_['nAttrs']++;	// for append - since we default it
	// prepend - boolean - default false
	if ( array_key_exists("prepend",$fb_['verbInfo']['attributes']) ) {
		if ( !in_array($fb_['verbInfo']['attributes']['prepend'],array("true","false")) ) {
			$this->fb_throw("fusebox.badGrammar.invalidAttributeValue",
						"Attribute has invalid value",
						"The attribute 'prepend' must either be \"true\" or \"false\", for a 'include' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	} else {
		$fb_['verbInfo']['attributes']['prepend'] = "false";
	}
	$fb_['nAttrs']++;	// for prepend - since we default it
	// circuit - string - default circuit circuit alias
	// FB5: official support for this undocumented feature of FB4.x
	if ( array_key_exists("circuit",$fb_['verbInfo']['attributes']) ) {
		if ( array_key_exists($fb_['verbInfo']['attributes']['circuit'],$fb_['app']->circuits) ) {
			//$fb_['targetCircuit'] =& $fb_['app']->circuits[$fb_['verbInfo']['attributes']['circuit']];
		} else {
			$this->fb_throw("fusebox.undefinedCircuit",
						"undefined Circuit",
						"The attribute 'circuit' (which was '{$fb_['verbInfo']['attributes']['circuit']}') must specify an existing circuit alias, for a 'include' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	} else {
		$fb_['verbInfo']['attributes']['circuit'] = $fb_['verbInfo']['circuit'];
	}
	$fb_['nAttrs']++;	// for circuit - since we default it
	// strict mode - check attribute count:
	if ( $fb_['app']->strictMode ) {
		if ( count($fb_['verbInfo']['attributes']) != $fb_['nAttrs'] ) {
			$this->fb_throw("fusebox.badGrammar.unexpectedAttributes",
						"Unexpected attributes",
						"Unexpected attributes were found in a 'include' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	}
			
	// auto-append script extension:
	$fb_['standardExtension'] = $fb_['app']->scriptFileDelimiter;
	$fb_['arTemplate'] = explode(".",$fb_['verbInfo']['attributes']['template']);
	$fb_['extension'] = array_pop($fb_['arTemplate']);
	$fb_['arExt'] = explode(',',$fb_['app']->maskedFileDelimiters);
	if ( !in_array($fb_['extension'],$fb_['arExt']) && 
			!in_array('*',$fb_['arExt']) ) {
		$fb_['template'] = $fb_['verbInfo']['attributes']['template'] . '.' . $fb_['standardExtension'];
	} else {
		$fb_['template'] = $fb_['verbInfo']['attributes']['template'];
	}
	
	if ( $fb_['app']->debug ) {
		// trace inclusion of this fuse:
		$this->fb_appendLine('$myFusebox->trace("Runtime","&lt;include template=\"'.$fb_['template'].'\" circuit=\"'.$fb_['verbInfo']['attributes']['circuit'].'\"/&gt;");');
	}
	
	// if there are children, assume we need a stack frame:
	if ( $fb_['verbInfo']['hasChildren'] ) {
		$this->fb_appendLine('$myFusebox->enterStackFrame();');
		// this == where the child <parameter> verbs will store the variable names:
		$fb_['verbInfo']['parameters'] = array();
	}
	
} else {
	// any child <parameter> verbs will have been compiled by now
	
	// compile <include>
	if ( $fb_['verbInfo']['attributes']['contentvariable'] != "" && $fb_['verbInfo']['attributes']['overwrite'] == "false" ) {
		$this->fb_appendLine('if ( !isset($'.$fb_['verbInfo']['attributes']['contentvariable'].') ) {');
	}
	$this->fb_appendLine('if ( file_exists($application[$FUSEBOX_APPLICATION_KEY]->approotdirectory."'.$fb_['app']->circuits[$fb_['verbInfo']['attributes']['circuit']]->getRelativePath().$fb_['template'].'") ) {');
	if ( $fb_['verbInfo']['attributes']['contentvariable'] != "" ) {
		if ( $fb_['verbInfo']['attributes']['append'] == "true" ) {
			$this->fb_appendLine('if ( !isset($'.$fb_['verbInfo']['attributes']['contentvariable'].') ) $'.$fb_['verbInfo']['attributes']['contentvariable'].' = ""; ob_start(); echo $'.$fb_['verbInfo']['attributes']['contentvariable'].'; include($application[$FUSEBOX_APPLICATION_KEY]->WebRootPathToappRoot."'.$fb_['app']->circuits[$fb_['verbInfo']['attributes']['circuit']]->getRelativePath().$fb_['template'].'"); $'.$fb_['verbInfo']['attributes']['contentvariable'].' = ob_get_contents(); ob_end_clean();');
		} else if ( $fb_['verbInfo']['attributes']['prepend'] == "true" ) {
			$this->fb_appendLine('if ( !isset($'.$fb_['verbInfo']['attributes']['contentvariable'].') ) $'.$fb_['verbInfo']['attributes']['contentvariable'].' = ""; ob_start(); include($application[$FUSEBOX_APPLICATION_KEY]->WebRootPathToappRoot."'.$fb_['app']->circuits[$fb_['verbInfo']['attributes']['circuit']]->getRelativePath().$fb_['template'].'"); echo $'.$fb_['verbInfo']['attributes']['contentvariable'].'; $'.$fb_['verbInfo']['attributes']['contentvariable'].' = ob_get_contents(); ob_end_clean();');
		} else {
			$this->fb_appendLine('ob_start(); include($application[$FUSEBOX_APPLICATION_KEY]->WebRootPathToappRoot."'.$fb_['app']->circuits[$fb_['verbInfo']['attributes']['circuit']]->getRelativePath().$fb_['template'].'"); $'.$fb_['verbInfo']['attributes']['contentvariable'].' = ob_get_contents(); ob_end_clean();');
		}
	} else {
		$this->fb_appendLine('include($application[$FUSEBOX_APPLICATION_KEY]->WebRootPathToappRoot."'.$fb_['app']->circuits[$fb_['verbInfo']['attributes']['circuit']]->getRelativePath().$fb_['template'].'");');
	}
	$this->fb_appendLine('}');
	if ( $fb_['verbInfo']['attributes']['required'] == "true" ) {
		$this->fb_appendLine('else { __cfthrow(array( "type"=>"fusebox.missingFuse", "message"=>"missing Fuse", ' .
				'"detail"=>"You tried to include a fuse '.$fb_['template'].' in circuit ' .
					$fb_['app']->circuits[$fb_['verbInfo']['attributes']['circuit']]->getAlias().' which does not exist (from fuseaction '.$fb_['verbInfo']['circuit'].'.'.$fb_['verbInfo']['fuseaction'].').")); }');
	}
	if ( $fb_['verbInfo']['attributes']['contentvariable'] != "" && $fb_['verbInfo']['attributes']['overwrite'] == "false" ) {
		$this->fb_appendLine('}');
	}
	
	// clean up any stack frame:
	if ( $fb_['verbInfo']['hasChildren'] ) {
		// unwind the stack:
		for ( $i = count($fb_['verbInfo']['parameters']) - 1 ; $i >= 0 ; $i-- ) {
			$fb_['name'] = $fb_['verbInfo']['parameters'][$i];
			$this->fb_appendLine('if ( array_key_exists("'.$fb_['name'].'",$myFusebox->stack) ) { ' .
						'$'.$fb_['name'].' = $myFusebox->stack["'.$fb_['name'].'"]; }' .
						' else { ' . 
						'unset($'.$fb_['name'].'); }');
		}
		$this->fb_appendLine('$myFusebox->leaveStackFrame();');
	}
}
?>