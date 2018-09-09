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

trait QUED
{
    public static $exit = false;
        
    private $devices = null;
    
    public function register_available_methods()
    {
      $this->register_method("daemon", "Start the Bunkerschild queue daemon");
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
      $this->queue_loop();
    }
    
    private function queue_worker()
    {
      $db = $this->bunkerschild->get_db_instance();
      
      $res = $db->query("SELECT * FROM queue");
      
      if ((!$res) || ($res->num_rows == 0))
        return false;
        
      while ($queue = $res->fetch_object("\\BunkerschildFramework\\database\\table\\queue"))
      {
        $select = "SELECT device.*, actor.name AS actorname, vendor.name AS vendorname, model.name AS modelname FROM location, level, room, vendor, model, device, actor WHERE ";
        $select .= "device.flag_enabled = 1 AND ";
        $select .= "model.uid = device.model_uid AND ";
        $select .= "model.flag_enabled = 1 AND ";
        $select .= "vendor.uid = model.vendor_uid AND ";
        $select .= "vendor.flag_enabled = 1 AND ";
        $select .= "room.uid = device.room_uid AND ";
        $select .= "room.flag_enabled = 1 AND ";
        $select .= "level.uid = room.level_uid AND ";
        $select .= "level.flag_enabled = 1 AND ";
        $select .= "location.uid = level.location_uid AND ";
        $select .= "location.flag_enabled = 1 AND ";
        $select .= "actor.uid = ".$queue->actor_uid." AND ";
        $select .= "actor.value != '".$db->real_escape_string($queue->value)."' AND ";
        $select .= "actor.flag_enabled = 1 AND ";
        $select .= "device.uid = actor.device_uid LIMIT 1";
        
        $res2 = $db->query($select);
        
        if ((is_object($res2)) && ($res2->num_rows == 1))
        {
          $result = $res2->fetch_object();
          $res2->free_result();
          
          $vendorname = $result->vendorname;
          $modelname = $result->modelname;
          $actorname = $result->actorname;
          
          $device = new \BunkerschildFramework\device;
          $device->passive = false;
          $device->hostname = $result->hostname;
          $device->hwaddress = $result->hwaddress;
          $device->ipaddress = $result->ipaddress;
          $device->serial = $result->serial;
          $device->vendor = $vendorname;
          $device->model = $modelname;
          $device->type = explode(",", $result->type);
          
          $driverclass = "\\BunkerschildFramework\\driver\\".$vendorname;
          
          $driver = new $driverclass($device);
          
          $this->daemon_log("Setting actor ".$actorname." on device ".$result->name." to value ".$queue->value);
          
          $json = $driver->act($actorname, $queue->value);
          
          if ((!is_object($json)) || (!isset($json->$actorname)) || ($json->$actorname != $queue->value))
          {
            $this->daemon_log("===== FAILED TO SET ACTOR ".$actorname." ON DEVICE ".$result->name." TO ".$queue->value." =====");
            $db->query("UPDATE `actor` SET timestamp_last_error = CURRENT_TIMESTAMP, last_error = 'Unable to set actor' WHERE uid = ".$queue->actor_uid." LIMIT 1");
          }
        }
        
        $db->query("DELETE FROM queue WHERE uid = ".$queue->uid." LIMIT 1");
      }
        
      $res->free_result();
      
      return true;
    }
    
    private function queue_loop()
    { 
      while (!self::$exit)
      {
        $this->queue_worker();        
        
        sleep(1);        
      }
    }
}
