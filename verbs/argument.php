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
	if ($fb_['verbInfo']['executionMode'] == "start") {
		// validate attributes
		// name - string - optional
		// value - string - required
		if ( !array_key_exists("value",$fb_['verbInfo']['attributes']) ) {
			fb_throw("fusebox.badGrammar.requiredAttributeMissing",
						"Required attribute is missing",
						"The attribute 'value' is required, for a 'argument' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
		// must be nested inside an <invoke> that uses the method attribute
		// or an <instantiate> that does not use the arguments attribute
		$fb_['validParent'] = false;
		if ( array_key_exists("parent",$fb_['verbInfo']) ) {
			if ( $fb_['verbInfo']['parent']['lexiconVerb'] == "invoke" && array_key_exists("method",$fb_['verbInfo']['parent']['attributes']) ) {
				$fb_['validParent'] = true;
			} else if ( $fb_['verbInfo']['parent']['lexiconVerb'] == "instantiate" && !array_key_exists("arguments",$fb_['verbInfo']['parent']['attributes']) ) {
				$fb_['validParent'] = true;
			}
		}
		if ( !$fb_['validParent']) {
			fb_throw("fusebox.badGrammar.argumentInvalidParent",
						"Verb 'argument' has invalid parent verb",
						"Found 'argument' verb with no valid parent verb (either 'invoke' with 'method' attribute or 'instantiate' without 'arguments' attribute) in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
		}
		// strict mode - check attribute count:
		$fb_['theCircuit'] =& $fb_['verbInfo']['action']->getCircuit();
		$fb_['theApp'] =& $fb_['theCircuit']->getApplication();
		if ( $fb_['theApp']->strictMode ) {
			if ( array_key_exists("name",$fb_['verbInfo']['attributes']) ) {
				if (count($fb_['verbInfo']['attributes']) != 2 ) {
					fb_throw("fusebox.badGrammar.unexpectedAttributes",
								"Unexpected attributes",
								"Unexpected attributes were found in a 'argument' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
				}
			} else {
				if ( count($fb_['verbInfo']['attributes']) != 1 ) {
					fb_throw("fusebox.badGrammar.unexpectedAttributes",
								"Unexpected attributes",
								"Unexpected attributes were found in a 'arguments' verb in fuseaction {$fb_['verbInfo']['circuit']}.{$fb_['verbInfo']['fuseaction']}.");
				}
			}
		}
		// append this argument to the parent data:
		$fb_['data'] = $fb_['verbInfo']['parent']['data'];
		if ( array_key_exists("name",$fb_['verbInfo']['attributes']) ) {
			// named argument:
			$fb_['data']['arguments'] .= $fb_['data']['separator'] . $fb_['verbInfo']['attributes']['name'];
			$fb_['data']['arguments'] .= ( substr($fb_['verbInfo']['attributes']['value'],0,1) != '$' ) ? 
				'="'.$fb_['verbInfo']['attributes']['value'].'"' :
				'='.$fb_['verbInfo']['attributes']['value'];
		} else {
			// positional argument:
			$fb_['data']['arguments'] .= $fb_['data']['separator'];
			$fb_['data']['arguments'] .= ( substr($fb_['verbInfo']['attributes']['value'],0,1) != '$' ) ? 
				'"'.$fb_['verbInfo']['attributes']['value'].'"' :
				$fb_['verbInfo']['attributes']['value'];
		}
		$fb_['data']['separator'] = ",";
		$fb_['verbInfo']['parent']['data'] = $fb_['data'];
	}
?>