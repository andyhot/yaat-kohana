<?php

/**
 * Application Name
 */
$config['app.name'] = 'Application Name';

/**
 * Application Version
 */
$config['app.ver'] = '0.85';

$config['app.copyright'] = 'Andreas Andreou';

// The default language for the admin interface. Only set if it differs from
// the one set at config/local.php 
//$config['app.lang'] = 'el';

/**
 * Javascript related
 */
$config['app.js.dojo'] = 'http://ajax.googleapis.com/ajax/libs/dojo/1.3.2';
$config['app.js.dojo.xdomain'] = true;
$config['app.js.dojo.theme'] = 'soria';

/**
 * json string of module namespaces and paths, i.e. "'myapp':'/js/myapp'"
 */
$config['app.js.dojo.namespaces'] = NULL;

/**
 * Array with additional classes to dojo.require
 */
$config['app.js.dojo.requires'] = NULL;

// set to '' to see if auto-sizing works
// and include dijit._editor.plugins.AlwaysShowToolbar in extra plugins
$config['app.js.dojo.editor.height'] = NULL;

/**
 * Editor default plugins - set to customize
 * See: http://docs.dojocampus.org/dijit/Editor
 */
$config['app.js.dojo.editor.plugins'] = NULL;

/**
 * Editor extra plugins - add the class to the requires array if needed
 */
$config['app.js.dojo.editor.extraplugins'] = "['formatBlock', 'foreColor', '|', 'createLink', 'insertImage']";

/**
 * Extra javascript file to load - added before dojo. For add stuff after dojo,
 * just require it.
 */
$config['app.js.extra'] = NULL;

/**
 * Css related
 */
$config['app.css.extra'] = NULL;

$config['summary-in-related'] = true;
$config['records_per_page'] = 15;
$config['add-view-class'] = true;