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

trait PASVD
{
    public static $exit = false;
        
    private $devices = null;
    
    private $timer_last_loop = null;
    
    public function register_available_methods()
    {
      $this->register_method("daemon", "Start the Bunkerschild passive daemon");
    }

    public function register_available_signals()
    {
      $this->register_signal(SIGTERM, array("\\".get_class($this), "daemon_exit"));
      $this->register_signal(SIGINT, array("\\".get_class($this), "daemon_exit"));
    }
    
    public static function daemon_exit()
    {
      self::$exit = true;
    }
    
    private function daemon()
    {    
      $this->passive_loop();
    }
    
    private function discover_passive_devices()
    {
      $devices = $this->bunkerschild->discover_devices(true);
      
      foreach ($devices as $hwaddress => $device)
      {
        if (!$device->passive)
          continue;
          
        $driverpath = PATH_CLASSES.DS."driver".DS.$device->vendor.".class.php";

        if (!file_exists($driverpath))
          continue;
          
        if (!($ping = $this->bunkerschild->ping($device->ipaddress)))
        {
          if (isset($this->devices[$device->ipaddress]))
          {
            $this->daemon_log("==== Device ".$device->hostname." has gone away ====");
            
            unset($this->devices[$device->ipaddress]);
          }
          else
          {
            $this->daemon_log("==== Device ".$device->hostname." is unreachable! ====");
          }
          
          continue;
        }
        elseif (!isset($this->devices[$device->ipaddress]))
        {
          $this->daemon_log("==== Device ".$device->hostname." is now online ====");        
        }
          
        $details = $this->bunkerschild->pull_device($device);      
        $this->bunkerschild->register_device($device, $details);
        
        foreach ($details as $datatype => $json)
        {
          $this->daemon_log($device->hostname."\t".$device->ipaddress.":PASV\t".$datatype."\t".json_encode($json));        
        }

        $this->devices[$device->ipaddress] = $device;                
      }
    }
            
    private function passive_loop()
    { 
      while (!self::$exit)
      {
        if (($this->timer_last_loop + 30) < time())
        {
          $this->discover_passive_devices();
        
          $this->timer_last_loop = time();          
        }
        
        sleep(1);        
      }
    }
}
