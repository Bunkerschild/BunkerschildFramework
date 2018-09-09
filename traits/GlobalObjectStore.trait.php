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

namespace BunkerschildFramework\traits;

trait GlobalObjectStore
{
    private static $global_object_storage = null;
    private static $global_object_session = null;
    
    public static function global_object_initialize()
    {
        self::$global_object_storage = null;
        self::$global_object_session = null;
        
        self::global_object_storage_create();
    }
    
    public static function global_object_storage_create()
    {
        if (!is_array(self::$global_object_storage))
            self::$global_object_storage = array();
            
        while (true)
        {
            self::$global_object_session = md5(uniqid(microtime(true)));
            
            if (!isset(self::$global_object_storage[self::$global_object_session]))
                break;
                
            usleep(25);
        }
        
        self::$global_object_storage[self::$global_object_session] = array();
    }
    
    public static function global_object_storage_destroy()
    {
        unset(self::$global_object_storage[self::$global_object_session]);
        
        self::global_object_storage_create();
    }
    
    public static function global_object_set($key, $val)
    {
        self::$global_object_storage[self::$global_object_session][$key] = $val;        
    }
    
    public static function global_object_get($key)
    {
        if (isset(self::$global_object_storage[self::$global_object_session][$key]))
            return self::$global_object_storage[self::$global_object_session][$key];
        
        return null;
    }
    
    public static function global_object_free($key)
    {
        unset(self::$global_object_storage[self::$global_object_session][$key]);
    }

    public static function global_object_iterate()
    {
        return self::$global_object_storage[self::$global_object_session];
    }
}
