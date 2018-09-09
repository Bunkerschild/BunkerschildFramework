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

trait SSDP
{
    private $ssdp_server = null;
    private $event_dispatcher = null;
        
    public function register_available_methods()
    {
      $this->register_method("daemon", "Start the Bunkerschid ssdp daemon");
    }

    public function register_available_signals()
    {
      $this->register_signal(SIGTERM, array("\\".get_class($this), "daemon_exit"));
      $this->register_signal(SIGINT, array("\\".get_class($this), "daemon_exit"));
    }
    
    public function register_ssdp($ssdp_server)
    {
      $this->ssdp_server = $ssdp_server;
    }
    
    public function register_event_dispatcher($event_dispatcher)
    {
      $this->event_dispatcher = $event_dispatcher;
    }
    
    public static function daemon_exit()
    {
      exit;
    }
    
    public function get_xml_server()
    {
      global $__CONFIG;
      
      return $__CONFIG->xml_server.(($__CONFIG->xml_port) ? ":".$__CONFIG->xml_port : "");
    }
    
    public function get_ssdp_devices()
    {
      $select = "SELECT device.*, vendor.name AS vendorname, model.name AS modelname FROM location, level, room, vendor, model, device WHERE ";
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
      $select .= "device.emulation != 'none' ";
      $select .= "GROUP BY device.uid";

      $db = $this->bunkerschild->get_db_instance();
      
      $result = $db->query($select);
      
      if ((is_object($result)) && ($result->num_rows))
      {
        $devices = array();
        
        while ($device = $result->fetch_object())
        {
          $devices[$device->uid] = new \stdClass;
          $devices[$device->uid]->device = $device;
          $devices[$device->uid]->actors = array();
          $devices[$device->uid]->sensors = array();

          $res = $db->query("SELECT * FROM actor WHERE device_uid = ".$device->uid." AND flag_enabled = 1");
          
          if ((is_object($res)) && ($res->num_rows))
          {
            while ($actor = $res->fetch_object())
            {
              array_push($devices[$device->uid]->actors, $actor);
            }
            
            $res->free_result();
          }

          $res = $db->query("SELECT * FROM sensor WHERE device_uid = ".$device->uid." AND flag_enabled = 1");
          
          if ((is_object($res)) && ($res->num_rows))
          {
            while ($sensor = $res->fetch_object())
            {
              array_push($devices[$device->uid]->sensors, $sensor);
            }
            
            $res->free_result();
          }
        }
        
        $result->free_result();
        
        return $devices;
      }
      
      return null;
    }
    
    private function daemon()
    {
      while ($this->ssdp_server->run())
      {
        sleep(1);
      }
    }
}
