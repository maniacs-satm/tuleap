<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2010
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * This script extract the version of a package from the sources.
 */

$basedir=dirname(__FILE__).'/../..';

ini_set('include_path', ini_get('include_path').':'.$basedir.'/src');

require('common/language/BaseLanguage.class.php');

$GLOBALS['codendi_cache_dir'] = '/tmp';
$GLOBALS['sys_incdir']        = $basedir.'/site-content';
$GLOBALS['sys_pluginsroot']   = $basedir.'/plugins';

$Language = new BaseLanguage('en_US', 'en_US');
$Language->lang = 'en_US';
$Language->compileLanguage('en_US');

// Load plugin
$desc = loadDesc($basedir, $argv[1]);

// Show version
echo $desc->getVersion().PHP_EOL;

// Show Desc
if (isset($argv[2])) {
    echo $desc->getDescription().PHP_EOL;
}

function loadDesc($basedir, $pluginName) {
    $className = $pluginName.'PluginDescriptor';

    if (is_dir($basedir.'/plugins/'.$pluginName)) {
        include $basedir.'/plugins/'.$pluginName.'/include/'.$className.'.class.php';
    } else {
        $lc = strtolower($pluginName);
        include $basedir.'/plugins/'.$lc.'/include/'.$className.'.class.php';
    }

    return new $className();
}
