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

trait MSGD
{
    public static $exit = false;
    
    private $socket_fd = null;
    private $socket_ip = null;
    private $socket_port = null;
    
    private $devices = null;
    
    private $timer_last_update_offline_devices = null;
    
    public function register_available_methods()
    {
      $this->register_method("daemon", "Start the Bunkerschild message daemon");
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
      $this->socket_open();
      $this->socket_bind();
      $this->socket_loop();
      $this->socket_close();
    }
    
    private function discover_device($hostname, $ipaddress)
    {
      $devices = $this->bunkerschild->discover_devices();
      
      if (!$devices)
        return false;
        
      foreach ($devices as $hwaddress => $device)
      {
        if (($device->ipaddress == $ipaddress) && ($device->hostname == $hostname))
        {
          if (!($ping = $this->bunkerschild->ping($device->ipaddress)))
            return false;
            
          $this->daemon_log("======= Discovered device: ".$hwaddress." - ".$hostname."@".$ipaddress." =======");
          
          $this->devices[$ipaddress] = $device;
          $details = $this->bunkerschild->pull_device($device);
          
          $this->bunkerschild->register_device($device, $details);
          return true;
        }
      }
      
      return false;
    }
    
    private function process_result_data($hostname, $ipaddress, $json)
    {
      if (!isset($this->devices[$ipaddress]))
        return false;      

      if ($this->devices[$ipaddress]->hostname != $hostname)
        return false;
        
      if (!is_object($this->devices[$ipaddress]->dataset))
        return false;
        
      if (!is_array($this->devices[$ipaddress]->actors))
        return false;

      foreach ($json as $key => $val)
      {
        if (isset($this->devices[$ipaddress]->actors[$key]))
        {
          if ($val != $this->devices[$ipaddress]->actors[$key]->value)
          {
            $this->devices[$ipaddress]->actors[$key]->value = $val;
            $this->devices[$ipaddress]->actors[$key]->timestamp_last_set_actor = date("Y-m-d H:i:s");          
          
            $this->devices[$ipaddress]->actors[$key]->update($this->bunkerschild->get_db_instance());
          }
        }
      }
      
      return true;
    }
    
    private function process_state_data($hostname, $ipaddress, $json)
    {
      if (!isset($this->devices[$ipaddress]))
        return false;      

      if ($this->devices[$ipaddress]->hostname != $hostname)
        return false;
        
      if (!is_object($this->devices[$ipaddress]->dataset))
        return false;
        
      if (!is_array($this->devices[$ipaddress]->actors))
        return false;

      for ($i = 0; $i < 17; $i++)
      {
        $label = (($i == 0) ? "POWER" : "POWER".$i);
        $channel = (($i == 0) ? 1 : $i);
        
        if (isset($json->$label))
        {
          if (isset($this->devices[$ipaddress]->actors[$label]))
          {
            if (($this->devices[$ipaddress]->actors[$label]->name == $label) && ($this->devices[$ipaddress]->actors[$label]->channel == $channel))
            {
              if ($json->$label != $this->devices[$ipaddress]->actors[$label]->value)
              {
                $this->devices[$ipaddress]->actors[$label]->value = $json->$label;
                $this->devices[$ipaddress]->actors[$label]->timestamp_last_set_actor = date("Y-m-d H:i:s");

                $this->devices[$ipaddress]->actors[$label]->update($this->bunkerschild->get_db_instance());
              }
            }
          }
        }
      }
      
      return true;              
    }
    
    private function process_sensor_data($hostname, $ipaddress, $json)
    {
      if (!isset($this->devices[$ipaddress]))
        return false;
        
      if ($this->devices[$ipaddress]->hostname != $hostname)
        return false;
        
      if (!is_object($this->devices[$ipaddress]->dataset))
        return false;
        
      $tempunit = ((isset($json->TempUnit)) ? $json->TempUnit : "");
        
      foreach ($json as $sensortype => $sensordata)
      {
        if (($sensordata == "Time") || ($sensordata == "TempUnit"))
          continue;
          
        if (is_object($sensordata))
        {
          $sensors = array($sensordata);
        }
        elseif (is_array($sensordata))
        {
          $sensors = $sensordata;
        }
        else
        {
          continue;
        }

        foreach ($sensors as $sensorindex => $sensor)
        {
          $name = "";
          
          foreach ($sensor as $keyname => $data)
          {
            $name = $sensortype." ".$keyname." ".($sensorindex + 1);
            
            if (!isset($this->devices[$ipaddress]->sensors[$name]))
              continue;
              
            $unit = $this->bunkerschild->translate_unit($keyname, $sensortype, $tempunit);
              
            $this->devices[$ipaddress]->sensors[$name]->value = $data;
            $this->devices[$ipaddress]->sensors[$name]->unit = $unit;
            
            $this->devices[$ipaddress]->sensors[$name]->update($this->bunkerschild->get_db_instance());
          }
        }
      }

      return true;      
    }
    
    private function process_data($hostname, $ipaddress, $port, $datatype, $json)
    {
      if (isset($this->devices[$ipaddress]))
      {
        if ($this->devices[$ipaddress]->next_discovery < time())
          unset($this->devices[$ipaddress]);
      }
      
      if (!isset($this->devices[$ipaddress]))
      {
        if (($this->timer_last_update_offline_devices + 180) < time())
        {
          $this->timer_last_update_offline_devices = time();
          $this->bunkerschild->update_offline_devices();
        }
        
        if (!$this->discover_device($hostname, $ipaddress))
          return;
      }
      
      switch($datatype)
      {
        case "RESULT":
          $this->process_result_data($hostname, $ipaddress, $json);
          break;
        case "STATE":
          $this->process_state_data($hostname, $ipaddress, $json);
          break;
        case "SENSOR":
          $this->process_sensor_data($hostname, $ipaddress, $json);
          break;
      }
      
      if ($this->devices[$ipaddress]->dataset)
        $this->daemon_log($hostname."\t".$ipaddress.":".$port."\t".$datatype."\t".json_encode($json));
    }
    
    private function socket_open()
    {
      $this->socket_fd = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
      
      if (!$this->socket_fd)
      {
        $this->echo("FATAL: Unable to create socket\n", true);
        exit(1);
      }
    }  
    
    private function socket_bind()
    {
      $this->socket_ip = ((isset($this->arguments->ip)) ? $this->arguments->ip : "0.0.0.0");
      $this->socket_port = ((isset($this->arguments->port)) ? $this->arguments->port : 888);
      
      if (!@socket_bind($this->socket_fd, $this->socket_ip, $this->socket_port))
      {
        $this->echo("FATAL: Unable to bind port ".$this->socket_ip.":".$this->socket_port."\n", true);
        exit(2);
      }
    }
    
    private function socket_loop()
    {  
      $from_ip = "";
      $from_port = "";
      $buffer = "";

      while (!self::$exit)
      {
        @socket_recvfrom($this->socket_fd, $buffer, 512, 0, $from_ip, $from_port);
        
        if (!$this->bunkerschild->allowed_device_ip($from_ip))
        {
          $this->daemon_log("===== Message from disallowed ip ".$from_ip." ignored =====");
          continue;
        }
        
        $json = explode(" = ", $buffer, 2);
        $json = json_decode(trim($json[1]));

        if (!$json)
                continue;

        $data = explode(" ", $buffer, 4);

        $hostname = $data[0];
        
        if (isset($data[2]))
          $datatype = $data[2];
        else
          $datatype = null;

        $this->process_data($hostname, $from_ip, $from_port, $datatype, $json);
      }
    }
    
    private function socket_close()
    {  
      @socket_close($this->socket_fd);
    }
}
