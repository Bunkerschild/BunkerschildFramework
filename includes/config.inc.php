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
    
if (defined("BUNKERSCHILD_CONFIG"))
    die("You may not include this file, twice\n");
else
    define("BUNKERSCHILD_CONFIG", true);
    
// Uncomment to use OS version of PING otherwise use internal routine
// define("BUNKERSCHILD_USE_OS_PING", true);

final class configuration
{
    /*********************\
    * BEGIN CONFIGURATION *
    \*********************/
    
    private $posix_username = "bunkerschild";
    private $posix_group = "bunkerschild";
    
    private $xml_server = "bunkerschild.hostname.tld";
    private $xml_port = 888;

    private $mysql_socket = "/var/run/mysqld/mysqld.sock";
    private $mysql_username = "bunkerschild";
    private $mysql_password = "password";
    private $mysql_hostname = "localhost";
    private $mysql_database = "bunkerschild_db";
    private $mysql_port = 3306;
    
    private $dhcp_lease_file = "/tmp/dhcp.leases";
    
    private $allowed_device_networks = array(
      "172.17.32.0/24"
    );
    
    private $register_devices = array(
        "60:01:94" => array(
          "vendor" => "Sonoff", 
          "model" => "T1",
          "passive" => false,
          "type" => array(
            'actor','sensor'
          )
        ),
        "2c:3a:e8" => array(
          "vendor" => "Sonoff", 
          "model" => "B10/POW",
          "passive" => false,
          "type" => array(
            'actor','sensor'
          )
        ),
        "5c:cf:7f" => array(
          "vendor" => "Sonoff", 
          "model" => "BASIC",
          "passive" => false,
          "type" => array(
            'actor','sensor'
          )
        ),
        "ec:fa:bc" => array(
          "vendor" => "Sonoff", 
          "model" => "S20",
          "passive" => false,
          "type" => array(
            'actor','sensor'
          )
        ),
        "68:c6:3a" => array(
          "vendor" => "Bunkerschild", 
          "model" => "NodeMCU",
          "passive" => false,
          "type" => array(
            'actor','sensor','fingerprinter-hid','rfid-hid','nfc-hid','display'
          )
        ),
        "78:e1:03" => array(
          "vendor" => "Amazon", 
          "model" => "Echo DOT",
          "passive" => true,
          "type" => array(
            'audio-hid'
          )
        ),
        "38:f7:3d" => array(
          "vendor" => "Amazon", 
          "model" => "Echo SHOW",
          "passive" => true,
          "type" => array(
            'audio-hid','camera','display'
          )
        ),
        "00:0d:c5" => array(
          "vendor" => "INSTAR", 
          "model" => "IN-2905",
          "passive" => true,
          "type" => array(
            'camera'
          )
        ),
        "78:a5:dd" => array(
          "vendor" => "Unknown", 
          "model" => "Camera",
          "passive" => true,
          "type" => array(
            'camera'
          )
        ),
        "00:30:d6" => array(
          "vendor" => "EnBW", 
          "model" => "Smartmeter",
          "passive" => true,
          "type" => array(
            'sensor'
          )
        )
    );

    /*********************\
    *  END CONFIGURATION  *
    \*********************/
    
    function __get($key)
    {
      if (!isset($this->$key))
      {
        throw new exception("Missing configuration key ".$key);
        return null;
      }
      
      $retval = null;
      
      if ($key == "mysql_password")
      {
        $retval = base64_decode(str_rot13($this->$key));
        
        if (!defined("BUNKERSCHILD_HOLD_SQL_PASSWD"))
          $this->$key = "****************";
      }
      else
      {
        $retval = $this->$key;
      }
      
      return $retval;
    }
    
    function __set($key, $val)
    {
      throw new exception("Setting configuration keys is not allowed.");
    }
}

$__CONFIG = new configuration;
