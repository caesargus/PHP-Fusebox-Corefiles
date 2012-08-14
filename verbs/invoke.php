<?php
/*
Copyright 2006 TeraTech, Inc. http://teratech.com/


Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/
if ( $fb_['verbInfo']['executionMode'] == "start" ) {
	// validate attributes
	$fb_['cir'] =& $fb_['verbInfo']['action']->getCircuit();
	$fb_['app'] =& $fb_['cir']->getApplication();
	$fb_['nAttrs'] = 0;
	// class - string default ""
	// object - string default ""
	// webservice - string default ""
	// one of class / object / webservice must be present
	if ( !array_key_exists("class",$fb_['verbInfo']['attributes']) ) {
		if ( !array_key_exists("object",$fb_['verbInfo']['attributes']) ) {
			if ( !array_key_exists("webservice",$fb_['verbInfo']['attributes']) ) {
				// error: class or object or webservice must be present
				$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
							"Required attribute is missing",
							"One of the attributes 'class', 'object' or 'webservice' is required, for a 'invoke' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
			} else {
				// webservice
			}
		} else {
			if ( !array_key_exists("webservice",$fb_['verbInfo']['attributes']) ) {
				// object
			} else {
				// error: only one of class or object or webservice may be present
				$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
							"Required attribute is missing",
							"One of the attributes 'class', 'object' or 'webservice' is required, for a 'invoke' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
			}
		}
	} else {
		if ( array_key_exists("object",$fb_['verbInfo']['attributes']) ||
					array_key_exists("webservice",$fb_['verbInfo']['attributes']) ) {
				// error: only one of class or object or webservice may be present
				$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
							"Required attribute is missing",
							"One of the attributes 'class', 'object' or 'webservice' is required, for a 'invoke' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		} else {
			// class
		}
	}
	$fb_['nAttrs']++;	// for any one of class, object or webservice
	// methodcall - string default \"
	// method - string default \" (new in FB5)
	// one of methodcall or method must be present
	if ( !array_key_exists("methodcall",$fb_['verbInfo']['attributes']) ) {
		if ( !array_key_exists("method",$fb_['verbInfo']['attributes']) ) {
			$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
						"Required attribute is missing",
						"One of the attributes 'methodcall' or 'method' is required, for a 'invoke' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		} else {
			// method - prepare to gather up <argument> tags:
			$fb_['verbInfo']['data']['arguments'] = "";
			$fb_['verbInfo']['data']['separator'] = "";
		}
	} else {
		if ( !array_key_exists("method",$fb_['verbInfo']['attributes']) ) {
			// methodcall - FB41 compatible
			if ( $fb_['verbInfo']['hasChildren'] ) {
				$this->fb_throw("fusebox.badGrammar.unexpectedChildren",
							"Unexpected child verbs",
							"The 'invoke' verb cannot have children when using the 'methodcall' attribute, in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
			}
		} else {
			// error: only one of methodcall or method may be present
			$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
						"Required attribute is missing",
						"One of the attributes 'methodcall' or 'method' is required, for a 'invoke' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	}
	$fb_['nAttrs']++;	// for either one of methodcall or method
	// overwrite - boolean default true (if returnvariable is present)
	if ( array_key_exists("overwrite",$fb_['verbInfo']['attributes']) ) {
		if ( !in_array($fb_['verbInfo']['attributes']['overwrite'],array("true","false")) ) {
			$this->fb_throw("fusebox.badGrammar.invalidAttributeValue",
						"Attribute has invalid value",
						"The attribute 'overwrite' must either be \"true\" or \"false\", for a 'invoke' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	} else {
		if ( array_key_exists("returnvariable",$fb_['verbInfo']['attributes']) ) {
			$fb_['verbInfo']['attributes']['overwrite'] = "true";
		} else {
			$fb_['verbInfo']['attributes']['overwrite'] = "false";
		}
	}
	$fb_['nAttrs']++;	// for overwrite - since we default it
	// returnvariable - string - required if overwrite is true
	if ( !array_key_exists("returnvariable",$fb_['verbInfo']['attributes']) ) {
		if ( $fb_['verbInfo']['attributes']['overwrite'] == "true" ) {
			$this->fb_throw("fusebox.badGrammar.requiredAttributeMissing",
						"Required attribute is missing",
						"The attribute 'returnvariable' is required if 'overwrite' is 'true', for a 'invoke' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
		// default to "" to make subsequent code easier
		$fb_['verbInfo']['attributes']['returnvariable'] = "";
	}
	$fb_['nAttrs']++;	// for returnvariable - since we default it
	// strict mode - check attribute count:
	if ( $fb_['app']->strictMode ) {
		if ( count($fb_['verbInfo']['attributes']) != $fb_['nAttrs'] ) {
			$this->fb_throw("fusebox.badGrammar.unexpectedAttributes",
						"Unexpected attributes",
						"Unexpected attributes were found in a 'invoke' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
	}
	
} else {	// compile the code on the end tag:

	// check whether we're using the old-style methodcall or the new-style method / argument form:
	if ( array_key_exists("methodcall",$fb_['verbInfo']['attributes']) ) {
		$fb_['methodcall'] = $fb_['verbInfo']['attributes']['methodcall'];
	} else {
		// complete the method call:
		$fb_['methodcall'] = $fb_['verbInfo']['attributes']['method'] . "(" . $fb_['verbInfo']['data']['arguments'] . ")";
	}
	// compile <invoke>
	$fb_['ret'] = $fb_['verbInfo']['attributes']['returnvariable'];
	if ( array_key_exists("object",$fb_['verbInfo']['attributes']) ) {
		// handled
		$fb_['obj'] = $fb_['verbInfo']['attributes']['object'];
		$fb_['instObj'] = "";
	} else if ( array_key_exists("class",$fb_['verbInfo']['attributes']) ) {
		// look it up
		$fb_['classDef'] = $fb_['app']->getClassDefinition($fb_['verbInfo']['attributes']['class']);
		$fb_['obj'] = $fb_['ret'];
		$fb_['instObj'] = 'require_once($application[$FUSEBOX_APPLICATION_KEY]->WebRootPathToappRoot."'.$fb_['classDef']->classpath.'"); $'.$fb_['ret'].' = new '.$fb_['classDef']->alias.'();';
		$fb_['obj'] .= ( property_exists("type",$fb_['classDef']) && $fb_['classDef']->type == 'singleton' ) ? '::' : '->';
	} else if ( structKeyExists($fb_['verbInfo']['attributes'],"webservice") ) {
		// this makes no sense but it's what the FB41 core files do:
		$fb_['obj'] = $fb_['verbInfo']['attributes']['webservice'];
	}
	/*
	if ( find("##",fb_.ret) gt 0 ) {
		$this->fb_.ret = '"' & fb_.ret & '"';
	}
	*/
	if ( $fb_['verbInfo']['attributes']['overwrite'] == "true" ) {
		$this->fb_appendLine('$'.$fb_['ret'].' = $'.$fb_['obj'].'->'.$fb_['methodcall'].';');
	} else {
		if ( $fb_['verbInfo']['attributes']['returnvariable'] != "" ) {
			$this->fb_appendLine('if ( !isset($'.$fb_['verbInfo']['attributes']['returnvariable'].') ) { $'.$fb_['ret'].' = $'.$fb_['obj'].'->'.$fb_['methodcall'].'; }');
		} else {
			$this->fb_appendLine('$'.$fb_['obj'].'->'.$fb_['methodcall'].';');
		}
	}
}
?>
