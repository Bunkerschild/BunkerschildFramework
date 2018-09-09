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

trait Bunkerschild
{
  private $db = null;
  
  public function escape_string($str)
  {
    return $this->db->real_escape_string(iconv("UTF-8", "ISO-8859-1", $str));
  }
  
  public function get_db_instance()
  {
    return $this->db;
  }
  
  public function get_program_name()
  {
    return self::BUNKERSCHILD_PROGRAM_NAME;
  }
  
  public function get_version_timestamp()
  {
    return self::BUNKERSCHILD_VERSION_TIMESTAMP;
  }
  
  public function get_version()
  {
    return self::BUNKERSCHILD_VERSION_MAJOR.".".self::BUNKERSCHILD_VERSION_MINOR."-".self::BUNKERSCHILD_VERSION_REVISION;
  }
  
  public function get_copyright()
  {
    return self::BUNKERSCHILD_COPYRIGHT;
  }
  
  public function get_license()
  {
    return self::BUNKERSCHILD_LICENSE;
  }
  
  public function update_offline_devices()
  {
    $this->db->query("UPDATE `device` SET flag_online = 1 WHERE timestampadd(SECOND, 1, timestamp_last_seen) < NOW() AND flag_online = 0");
    $this->db->query("UPDATE `device` SET flag_online = 0 WHERE timestampadd(MINUTE, 5, timestamp_last_seen) < NOW() AND flag_online = 1");
    
    return true;
  }
  
  public function allowed_device_ip($ip) 
  {
    global $__CONFIG;
    
    // Currently only supports IPv4
    if (strstr($ip, ":"))
      return false;
    
    $hit = false;
    
    foreach ($__CONFIG->allowed_device_networks as $range)
    {
	if (!strpos($range, '/')) 
		$range .= '/32';
			
	list($range, $netmask) = explode('/', $range, 2);
	
	$range_decimal = ip2long($range);	
	$ip_decimal = ip2long($ip);
	
	$wildcard_decimal = pow(2, (32 - $netmask)) - 1;
	$netmask_decimal = ~ $wildcard_decimal;
	
	if (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal))
	{
	  $hit = true;
	  break;
	}
    }
    
    return $hit;
  }

  public function discover_devices($without_datasets = false)
  {
    global $__CONFIG;
    
    if (!file_exists($__CONFIG->dhcp_lease_file))
    {
      throw new \exception("DHCP lease file not found");
      return;
    }
    
    $devices = array();
    
    srand((integer)((double)microtime(true) * 928349787284));
    
    foreach (file($__CONFIG->dhcp_lease_file) as $lease)
    {
      $field = explode(" ", trim($lease));
      
      $device = new \BunkerschildFramework\device;
      
      $device->next_discovery = (time() + 120 + rand(5, 45));
      $device->lease_expire = $field[0];
      $device->hwaddress = $field[1];
      $device->ipaddress = $field[2];
      $device->hostname = $field[3];
      $device->fixed_ipaddr = $field[4];
      $device->serial = strtoupper(substr(str_replace(":", "", $device->hwaddress), 0, 6))."00FF".strtoupper(substr(str_replace(":", "", $device->hwaddress), 6));
      
      $vendorid = strtolower(substr($device->hwaddress, 0, 8));
      
      if (!isset($__CONFIG->register_devices[$vendorid]))
        continue;
        
      $device->vendor = $__CONFIG->register_devices[$vendorid]["vendor"];
      $device->model = $__CONFIG->register_devices[$vendorid]["model"];
      $device->passive = $__CONFIG->register_devices[$vendorid]["passive"];

      $device_type = array();      
      $type = $__CONFIG->register_devices[$vendorid]["type"];

      $actors = array();
      $sensors = array();
      
      if (!$without_datasets)
      {
        $select = "SELECT device.* FROM location, level, room, vendor, model, device WHERE ";
        $select .= "device.hwaddress = '".$this->escape_string($device->hwaddress)."' AND ";
        $select .= "device.serial = '".$this->escape_string($device->serial)."' AND ";
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
        $select .= "location.flag_enabled = 1 LIMIT 1";
        
        $dev = $this->db->query($select);
        
        if ((is_object($dev)) && ($dev->num_rows == 1))
        {
          $device->dataset = $dev->fetch_object("\\BunkerschildFramework\\database\\table\\device");
          $dev->free_result();
          
          if (in_array("actor", $type))
          {
            $act = $this->db->query("SELECT * FROM actor WHERE device_uid = ".$device->dataset->uid);
          
            if ((is_object($act)) && ($act->num_rows))
            {            
              while ($actor = $act->fetch_object("\\BunkerschildFramework\\database\\table\\actor"))
              {
                $index = $actor->name;
                $actors[$index] = $actor;
              }
              
              $act->free_result();
            }
          }
          
          if (in_array("sensor", $type))
          {
            $sen = $this->db->query("SELECT * FROM sensor WHERE device_uid = ".$device->dataset->uid);
          
            if ((is_object($sen)) && ($sen->num_rows))
            { 
              while ($sensor = $sen->fetch_object("\\BunkerschildFramework\\database\\table\\sensor"))
              {
                $index = $sensor->name;
                $sensors[$index] = $sensor;
              }
              
              $sen->free_result();
            }
          }
        }
        else
        {
          $device->dataset = "";
          $device->actors = "";
          $device->sensors = "";
        }
        
        if (in_array("actor", $type))
        {
          if (count($actors) > 0)
          {
            $device->actors = $actors;
            array_push($device_type, "actor");
          }
        }
        
        if (in_array("sensor", $type))
        {
          if (count($sensors) > 0)
          {
            $device->sensors = $sensors;
            array_push($device_type, "sensor");
          }
        }
        
        $device->type = $device_type;
      }
      else
      {
        $device->type = $type;
      }

      $devices[$device->hwaddress] = $device;
    }
    
    return $devices;
  }
  
  public function add_dataset($table, $data)
  {
    $objname = "\\BunkerschildFramework\\database\\table\\".$table;
    
    $obj = new $objname;
    
    foreach ($data as $key => $val)
      $obj->$key = $val;
    
    return $obj->insert($this->db);
  }
  
  public function get_datasets($table)
  {
    $result = $this->db->query("SELECT * FROM `".$table."`");
    
    if ($result->num_rows == 0)
      return null;

    $results = array();
    
    while ($obj = $result->fetch_object("\\BunkerschildFramework\\database\\table\\".$table))
    {
      array_push($results, $obj);
    }
    
    $result->free_result();
    
    return $results;
  }
  
  public function get_dataset($table, $where_array)
  {
    $objname = "\\BunkerschildFramework\\database\\table\\".$table;
    $obj = new $objname;
    
    foreach ($where_array as $key => $val)
      $obj->$key = $val;
      
    if ($obj->select($this->db))
      return $obj;
    
    return null;
  }
  
  public function delete_dataset($table, $where_array)
  {
    $obj = $this->get_dataset($table, $where_array);
    
    if (!$obj)
      return null;
      
    if ($obj->delete($this->db))
      return true;
    
    return false;  
  }
  
  public function update_dataset($table, $uid, $update_array)
  {
    $obj = $this->get_dataset($table, array("uid" => $uid));
    
    if (!$obj)
      return null;
    
    foreach ($update_array as $key => $val)
    {
      if ($key == "uid")
        continue;
        
      $obj->$key = $val;
    }
      
    if ($obj->update($this->db))
      return true;
    
    return false;  
  }
  
  public function set_flag($flag_suffix, $flag_value, $table, $where_key, $where_val)
  {
    return $this->db->query("UPDATE `".$table."` SET `flag_".$flag_suffix."` = '".(($flag_value) ? "1" : "0")."' WHERE `".$where_key."` = '".$this->escape_string($where_val)."' LIMIT 1");
  }
  
  public function get_salt()
  {
    srand((integer)((double)microtime(true) * 982394782734));
    
    $salt = array();
    
    for ($i = 0; $i < 64; $i++)
    {
      array_push($salt, chr(rand(0, 255)));
      usleep(rand(1, 64));
    }
    
    return substr(sha1(implode("", $salt)), rand(0, 16), 8);
  }
  
  public function password_hash($password)
  {
    $salt = $this->get_salt();
    
    return substr($salt, 0, 4).md5($salt.$password).substr($salt, 4);
  }
  
  public function ping($host, $timeout_sec = 1, $timeout_usec = 0)
  {
    if ((file_exists("/bin/ping")) && (defined("BUNKERSCHILD_USE_OS_PING")))
    {
      if (strstr($host, ":"))
        $ipv6 = true;
      else
        $ipv6 = false;
      
      $ts = microtime(true);
      
      $pingcmd = "/bin/ping ".(($ipv6) ? "-6" : "-4")." -c 1 -s 16 -t 8 -W ".$timeout_sec." ".$host." >/dev/null 2>&1 && echo 1 || echo 0";
      $pingreg = trim(shell_exec($pingcmd));

      if ($pingreg == 1)
      {
        $result = microtime(true) - $ts;
        
        if ((!$result) || ($result < 0))
          $result = 1;
      }
      else
      {
        $result = false;
      }
    }
    else
    {
      $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
    
      $socket = @socket_create(AF_INET, SOCK_RAW, 1);
    
      @socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout_sec, 'usec' => $timeout_usec));
      @socket_connect($socket, $host, null);
    
      $ts = microtime(true);
    
      @socket_send($socket, $package, strlen($package), 0);
    
      if (socket_read($socket, 255)) 
      {
          $result = microtime(true) - $ts;

          if ((!$result) || ($result < 0))
            $result = 1;
      } 
      else 
      {
          $result = false;
      }
    
      @socket_close($socket);
    }
      
    return $result;
  }
  
  public function translate_unit($keyname, $sensortype = false, $tempunit = "C")
  {
    if (strstr($keyname, "Temperature"))
      $unit = iconv("UTF-8", "ISO-8859-1", html_entity_decode("&deg;")).$tempunit;
    elseif (strstr($keyname, "Humidity"))
      $unit = "%";
    elseif (strstr($keyname, "Voltage"))
      $unit = "V";
    elseif (strstr($keyname, "Power"))
      $unit = "W";
    elseif (strstr($keyname, "Current"))
      $unit = "A";
    elseif (strstr($keyname, "Resistence"))
      $unit = "Ohm";
    elseif (strstr($keyname, "Price"))
      $unit = "EUR";
    elseif ((strstr($keyname, "Today")) && ($sensortype == "ENERGY"))
      $unit = "KW/h";
    elseif ((strstr($keyname, "Yesterday")) && ($sensortype == "ENERGY"))
      $unit = "KW/h";
    elseif ((strstr($keyname, "Total")) && ($sensortype == "ENERGY"))
      $unit = "KW/h";
    elseif ((strstr($keyname, "Value")) && ($sensortype == "ENERGY"))
      $unit = "KW/h";
    elseif ((strstr($keyname, "Yearly")) && ($sensortype == "ENERGY"))
      $unit = "KW/h";
    elseif ((strstr($keyname, "Monthly")) && ($sensortype == "ENERGY"))
      $unit = "KW/h";
    elseif ((strstr($keyname, "Daily")) && ($sensortype == "ENERGY"))
      $unit = "KW/h";
    else
      $unit = "";
    
    return $unit;  
  }
  
  public function pull_device(\BunkerschildFramework\device $device)
  {
      $drivername = "\\BunkerschildFramework\\driver\\".$device->vendor;
      
      $driver = new $drivername($device);
      
      return $driver->pull();
  }
  
  public function register_device(\BunkerschildFramework\device $device, $details)
  {
    $res = $this->db->query("SELECT * FROM `vendor` WHERE `name` = '".$this->escape_string($device->vendor)."' LIMIT 1");
    
    if (!$res)
      return false;
    
    if ($res->num_rows == 0)
    {
      $this->db->query("INSERT INTO `vendor` (timestamp_registration,name) VALUES (NOW(),'".$this->escape_string($device->vendor)."')");      
      $res = $this->db->query("SELECT * FROM `vendor` WHERE uid = ".$this->db->insert_id." LIMIT 1"); 
    }
    
    $vendor = $res->fetch_object("\\BunkerschildFramework\\database\\table\\vendor");
    $res->free_result();
    
    if (!$vendor->flag_enabled)
      return false;
      
    $res = $this->db->query("SELECT * FROM `model` WHERE `name` = '".$this->escape_string($device->model)."' AND `vendor_uid` = ".$vendor->uid." LIMIT 1");
    
    if (!$res)
      return false;
    
    if ($res->num_rows == 0)
    {
      $this->db->query("INSERT INTO `model` (timestamp_registration,vendor_uid,name) VALUES (NOW(),".$vendor->uid.",'".$this->escape_string($device->model)."')");      
      $res = $this->db->query("SELECT * FROM `model` WHERE uid = ".$this->db->insert_id." LIMIT 1"); 
    }
    
    $model = $res->fetch_object("\\BunkerschildFramework\\database\\table\\model");
    $res->free_result();
    
    if (!$model->flag_enabled)
      return false;
      
    $res = $this->db->query("SELECT * FROM `device` WHERE `hwaddress` = '".$this->escape_string($device->hwaddress)."' LIMIT 1");
    
    if (!$res)
      return false;
    
    if ($res->num_rows == 0)
    {
      $this->db->query("INSERT INTO `device` (timestamp_registration,timestamp_last_seen,flag_online,serial,model_uid,hwaddress,ipaddress,name,hostname,type) VALUES (NOW(),NOW(),1,'".$this->escape_string($device->serial)."',".$model->uid.",'".$this->escape_string($device->hwaddress)."','".$this->escape_string($device->ipaddress)."','".$this->escape_string($device->hostname)."','".$this->escape_string($device->hostname)."','".implode(",", $device->type)."')");
      $res = $this->db->query("SELECT * FROM `device` WHERE uid = ".$this->db->insert_id." LIMIT 1"); 
    }
    
    $dev = $res->fetch_object("\\BunkerschildFramework\\database\\table\\device");
    $res->free_result();
    
    if (!$dev->flag_enabled)
      return false;
      
    $topicname = "";
    $groupname = "";
    $friendlyname = "";
    $firmware = "";
    $uptime = 0;
    $bootcount = 0;
    $timestamp_device_rtc = "0000-00-00 00:00:00";
      
    if (isset($details->Status))
    {
      $topicname = $details->Status->Topic;
      $friendlyname = $details->Status->FriendlyName;
    }
    
    if (isset($details->StatusPRM))
    {
      $groupname = $details->StatusPRM->GroupTopic;
      $uptime = $details->StatusPRM->Uptime;
      $bootcount = $details->StatusPRM->BootCount;
    }
    
    if (isset($details->StatusFWR))
    {
      $firmware = $details->StatusFWR->Version." (".$details->StatusFWR->BuildDateTime.")";
    }
    
    if (isset($details->StatusTIM))
    {
      $timestamp_device_rtc = date("Y-m-d H:i:s", strtotime($details->StatusTIM->Local));
    }
    
    $this->db->query("UPDATE `device` SET timestamp_device_rtc = '".$timestamp_device_rtc."', uptime = '".$this->escape_string($uptime)."', bootcount = '".$this->escape_string($bootcount)."', firmware = '".$this->escape_string($firmware)."', groupname = '".$this->escape_string($groupname)."', topicname = '".$this->escape_string($topicname)."', friendlyname = '".$this->escape_string($friendlyname)."', timestamp_last_seen = NOW(), flag_online = 1, serial = '".$this->escape_string($device->serial)."', hostname = '".$this->escape_string($device->hostname)."', ipaddress = '".$this->escape_string($device->ipaddress)."', type = '".implode(",", $device->type)."', model_uid = ".$model->uid." WHERE uid = ".$dev->uid." LIMIT 1");
    
    if (isset($details->StatusSTS))
    {
      if (isset($details->StatusSTS->Wifi))
      {
        $res = $this->db->query("SELECT * FROM `wifi` WHERE `device_uid` = ".$dev->uid." LIMIT 1");
    
        if (!$res)
          return false;
    
        if ($res->num_rows == 0)
        {
          $this->db->query("INSERT INTO `wifi` (timestamp_registration,device_uid,ssid,hwaddr_ap,rssi,flag_ap) VALUES (NOW(),".$dev->uid.",'".$this->escape_string($details->StatusSTS->Wifi->SSId)."','".$this->escape_string($details->StatusSTS->Wifi->APMac)."','".$this->escape_string($details->StatusSTS->Wifi->RSSI)."','".(($details->StatusSTS->Wifi->AP == 1) ? 1 : 0)."')");
          $res = $this->db->query("SELECT * FROM `wifi` WHERE uid = ".$this->db->insert_id." LIMIT 1"); 
        }
        
        $wifi = $res->fetch_object("\\BunkerschildFramework\\database\\table\\wifi");
        $res->free_result();
        
        if (!$wifi->flag_enabled)
          return false;
          
        $this->db->query("UPDATE `wifi` SET ssid = '".$this->escape_string($details->StatusSTS->Wifi->SSId)."', hwaddr_ap = '".$this->escape_string($details->StatusSTS->Wifi->APMac)."', rssi = '".$this->escape_string($details->StatusSTS->Wifi->RSSI)."', flag_ap = '".(($details->StatusSTS->Wifi->AP == 1) ? 1 : 0)."' WHERE uid = ".$wifi->uid." LIMIT 1");            
      }
      
      for ($i = 0; $i < 17; $i++)
      {
        $label = (($i == 0) ? "POWER" : "POWER".$i);
        $channel = (($i == 0) ? 1 : $i);
        
        if (isset($details->StatusSTS->$label))
        {
          $res = $this->db->query("SELECT * FROM `actor` WHERE `name` = '".$this->escape_string($label)."' AND `channel` = ".$channel." AND `device_uid` = ".$dev->uid." LIMIT 1");
    
          if (!$res)
            continue;
    
          if ($res->num_rows == 0)
          {
            $this->db->query("INSERT INTO `actor` (timestamp_registration,device_uid,channel,name,value) VALUES (NOW(),".$dev->uid.",".$channel.",'".$this->escape_string($label)."','".$this->escape_string($details->StatusSTS->$label)."')");
            $res = $this->db->query("SELECT * FROM `actor` WHERE uid = ".$this->db->insert_id." LIMIT 1"); 
          }
        
          $actor = $res->fetch_object("\\BunkerschildFramework\\database\\table\\actor");
          $res->free_result();
        
          if (!$actor->flag_enabled)
            continue;
          
          $this->db->query("UPDATE `actor` SET channel = ".$channel.", name = '".$this->escape_string($label)."', value = '".$this->escape_string($details->StatusSTS->$label)."' WHERE uid = ".$actor->uid." LIMIT 1");
        }
      }
    }
    
    if (isset($details->StatusSNS))
    {
      $tempunit = ((isset($details->StatusSNS->TempUnit)) ? $details->StatusSNS->TempUnit : null);
      
      foreach ($details->StatusSNS as $sensortype => $sensordata)
      {
        if (($sensortype == "TempUnit") || ($sensortype == "Time"))
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
        
        foreach ($sensors as $i => $s)
        {
          if (!is_object($s))
            continue;
            
          foreach ($s as $keyname => $data)
          {
            $res = $this->db->query("SELECT * FROM `sensor` WHERE `groupname` = '".$this->escape_string($sensortype)."' AND `keyname` = '".$this->escape_string($keyname)."' AND `sensor_index` = ".$i." AND `device_uid` = ".$dev->uid." LIMIT 1");

            if (!$res)
              continue;
              
            $unit = $this->translate_unit($keyname, $sensortype, $tempunit);
              
            if ($res->num_rows == 0)
            {
              $this->db->query("INSERT INTO `sensor` (timestamp_registration,device_uid,sensor_index,groupname,keyname,name,unit,value) VALUES (NOW(),".$dev->uid.",".$i.",'".$this->escape_string($sensortype)."','".$this->escape_string($keyname)."','".$this->escape_string($sensortype." ".$keyname." ".($i + 1))."','".$this->escape_string($unit)."','".$this->escape_string($data)."')");
              $res = $this->db->query("SELECT * FROM `sensor` WHERE uid = ".$this->db->insert_id." LIMIT 1"); 
            }
          
            $sensor = $res->fetch_object("\\BunkerschildFramework\\database\\table\\sensor");
            $res->free_result();
          
            if (!$sensor->flag_enabled)
              continue;
            
            $this->db->query("UPDATE `sensor` SET sensor_index = ".$i.", name = '".$this->escape_string($sensortype." ".$keyname." ".($i + 1))."', keyname = '".$this->escape_string($keyname)."', groupname = '".$this->escape_string($sensortype)."', unit = '".$this->escape_string($unit)."', value = '".$this->escape_string($data)."' WHERE uid = ".$sensor->uid." LIMIT 1");          
          }
        }
      }
    }
    
    return true;
  }
    
  public function database_initialize()
  {
    global $__CONFIG;
    
    if (is_object($this->db))
    {
      throw new \exception("Database already initialized");
      return;
    }
    
    $this->db = new \MySQLi(
      $__CONFIG->mysql_hostname, 
      $__CONFIG->mysql_username, 
      $__CONFIG->mysql_password, 
      $__CONFIG->mysql_database, 
      $__CONFIG->mysql_port,
      $__CONFIG->mysql_socket
    );
  }
  
  public function database_close()
  {
    $this->db->close();
  }
}
