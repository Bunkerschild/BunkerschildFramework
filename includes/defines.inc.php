<?php

 /***************************************************************************************************************\
 *                                                                                                               *
 * THIS FILE IS PART OF THE BUNKERSCHILD-FRAMEWORK AND IS PUBLISHED UNDER THE CC BY-NC-ND 4.0 LICENSE            * 
 *                                                                                                               * 
 * AUTHOR, LICENSOR AND COPYRIGHT OWNER (C)2018 Oliver Welter <contact@verbotene.zone>                           *
 *                                                                                                               * 
 * ************************************************************************************************************* *
 *                                                                                                               *
 * THE CC BY-NC-ND 4.0 LICENSE:                                                                                  *
 * For details see also: https://creativecommons.org/licenses/by-nc-nd/4.0/                                      *
 *                                                                                                               *
 * By exercising the Licensed Rights, defined in ./LICENSE/LICENSE.EN                                            *
 * (or in other languages LICENSE.<AR|DE|FI|FR|HR|ID|IT|JA|MI|NL|NO|PL|SV|TR|UK>),                               *
 * You accept and agree to be bound by the terms and conditions of this                                          *
 *                                                                                                               *
 * Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International Public License ("Public License"). * 
 *                                                                                                               *
 * To the extent this Public License may be interpreted as a contract, You are granted the Licensed Rights in    *
 * consideration of Your acceptance of these terms and conditions, and the Licensor grants You such rights in    *
 * consideration of benefits the Licensor receives from making the Licensed Material available under these       *
 * terms and conditions.                                                                                         *
 *                                                                                                               *
 \***************************************************************************************************************/

if (!defined("BUNKERSCHILD_BOOTSTRAP"))
    die("You may not access this file directly\n");

if (defined("BUNKERSCHILD_DEFINES"))
    die("You may not include this file, twice\n");
else
    define("BUNKERSCHILD_DEFINES", true);
    
if (!defined("DIRECTORY_SEPARATOR"))
    define("DIRECTORY_SEPARATOR", "/");
    
if (!defined("DS"))
    define("DS", DIRECTORY_SEPARATOR);

define("PATH_ROOT", DS."home".DS."bunkerschild".DS);
define("PATH_LIB", PATH_ROOT."lib".DS."BunkerschildFramework".DS);

define("PATH_BIN", PATH_ROOT."bin");
define("PATH_RUN", PATH_ROOT."run");
define("PATH_TMP", PATH_ROOT."tmp");
define("PATH_BAK", PATH_ROOT."bak");

define("PATH_INCLUDES",	PATH_LIB."includes");
define("PATH_CLASSES", PATH_LIB."classes");
define("PATH_TRAITS", PATH_LIB."traits");
define("PATH_STATIC", PATH_LIB."static");
define("PATH_IMAGES", PATH_LIB."images");
