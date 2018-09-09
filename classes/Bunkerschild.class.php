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

namespace BunkerschildFramework;

final class Bunkerschild
{
    const BUNKERSCHILD_PROGRAM_NAME = "Bunkerschild";
    
    const BUNKERSCHILD_VERSION_MAJOR = 2;
    const BUNKERSCHILD_VERSION_MINOR = 10;
    const BUNKERSCHILD_VERSION_REVISION = 1847;
    const BUNKERSCHILD_VERSION_TIMESTAMP = "2018-02-22 18:25:17";
    
    const BUNKERSCHILD_COPYRIGHT = "(c)2018 Oliver Welter <contact@verbotene.zone>";
    const BUNKERSCHILD_LICENSE = "CC BY-NC-ND 4.0 LICENSE";

    use traits\GlobalObjectStore;    
    use traits\Bunkerschild;
    
    function __construct()
    {
        self::global_object_initialize();
        
        $this->database_initialize();
    }    
    
    function __destruct()
    {
        $this->database_close();
    }
}
