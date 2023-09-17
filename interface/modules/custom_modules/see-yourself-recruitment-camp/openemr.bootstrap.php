<?php

/**
 *This is a module that handles printouts, CAIR portal, and other various things for CA peditricians
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Dan Pflieger <daniel@mi-squared.com><daniel@growlingflea.com>
 * @copyright Copyright (c) 2023 Daniel Pflieger <daniel@growlingflea.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */


/**
 * @global EventDispatcher $eventDispatcher Injected by the OpenEMR module loader;
 */


use Mi2\SeeYourselfCampaign\Bootstrap;

$bootstrap = new Bootstrap($eventDispatcher, $GLOBALS['kernel']);
$bootstrap ->subscribeToEvents();










