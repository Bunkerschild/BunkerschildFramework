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

trait CLI
{
    private function discover_devices()
    {
      $this->echo("Discovering devices...");
      
      $devices = $this->bunkerschild->discover_devices(true);
      
      if (count($devices) == 0)
      {
        $this->echo("nothing found\n");
        exit(1);
      }
      
      $this->echo("found ".count($devices)." device".((count($devices) > 1) ? "s" : "")."\n");
      
      foreach ($devices as $device)
      {
        echo " - ".$device->hwaddress." ".$device->vendor." ".$device->model." (".$device->hostname."/".$device->ipaddress.")\n";
      }
      
      exit;
    }
    
    private function pull_device()
    {
      if (!isset($this->arguments->hwaddr))
      {
        $this->echo("Missing argument --hwaddr <mac>\n", true);
        exit(1);
      }
      
      $this->echo("Searching device ".$this->arguments->hwaddr."...");
      
      $devices = $this->bunkerschild->discover_devices();
      
      if (count($devices) == 0)
      {
        $this->echo("nothing found\n");
        exit(1);
      }
      
      if (!isset($devices[$this->arguments->hwaddr]))
      {
        $this->echo("not found\n");
        exit(2);
      }
      
      $this->echo($devices[$this->arguments->hwaddr]->vendor." ".$devices[$this->arguments->hwaddr]->model."\n");
      $this->echo("Checking online status for device ".$this->arguments->hwaddr."...");
      
      if (!($ping = $this->bunkerschild->ping($devices[$this->arguments->hwaddr]->ipaddress)))
      {
        $this->echo("unreachable\n");
        exit(3);
      }
      
      $this->echo("responded after ".round(($ping * 1000))." msecs.\n");
      
      if ($this->arguments->online_state_only)
	exit;
	
      $this->echo("Pulling device ".$this->arguments->hwaddr."...");
      
      $info = $this->bunkerschild->pull_device($devices[$this->arguments->hwaddr]);
      
      if (!$info)
      {
        $this->echo("failed\n");
        exit(4);
      }
      
      $this->echo("done\n");
      
      print_r($info);
      exit;
    }
    
    private function add_dataset($table)
    {
      if (!isset($this->arguments->name))
      {
        $this->echo("Missing argument --name <".$table."s name>\n");
        exit(1);
      }
      
      $data = array("name" => $this->arguments->name);
      
      if ($table == "user")
      {
        if (!isset($this->arguments->username))
        {
          $this->echo("Missing argument --username <".$table."s username>\n");
          exit(3);
        }
        elseif (!isset($this->arguments->password))
        {
          $this->echo("Missing argument --password <".$table."s password>\n");
          exit(4);
        }
        elseif (!isset($this->arguments->pin))
        {
          $this->echo("Missing argument --pin <".$table."s pin>\n");
          exit(5);
        }        
        
        $data["username"] = $this->arguments->username;
        $data["password"] = $this->bunkerschild->password_hash($this->arguments->password);
        $data["pin"] = $this->bunkerschild->password_hash($this->arguments->pin);
      }
      elseif ($table == "location")
      {
        if (!isset($this->arguments->address))
        {
          $this->echo("Missing argument --address <".$table."s address>\n");
          exit(3);
        }
        elseif (!isset($this->arguments->zipcode))
        {
          $this->echo("Missing argument --zipcode <".$table."s zipcode>\n");
          exit(4);
        }
        elseif (!isset($this->arguments->city))
        {
          $this->echo("Missing argument --city <".$table."s city>\n");
          exit(5);
        }
        elseif (!isset($this->arguments->country))
        {
          $this->echo("Missing argument --country <".$table."s country>\n");
          exit(6);
        }
        
        foreach (array("address", "zipcode", "city", "country") as $f)
          $data[$f] = $this->arguments->$f;
      }
      elseif ($table == "level")
      {
        if (!isset($this->arguments->value))
        {
          $this->echo("Missing argument --value <".$table."s value>\n");
          exit(3);
        }
        elseif (!isset($this->arguments->locationname))
        {
          $this->echo("Missing argument --locationname <".$table."s locationname>\n");
          exit(4);
        }
        
        $select_array = array("name" => $this->arguments->locationname);
        $loc = $this->bunkerschild->get_dataset("location", $select_array);
        
        if (!is_object($loc))
        {
          $this->echo("Location ".$this->arguments->locationname." not found\n");
          exit(5);
        }
        
        $data["name"] = $this->arguments->name;
        $data["value"] = $this->arguments->value;
        $data["location_uid"] = $loc->uid;
      }
      elseif ($table == "room")
      {
        if (!isset($this->arguments->levelname))
        {
          $this->echo("Missing argument --levelname <".$table."s levelname>\n");
          exit(3);
        }
        elseif (!isset($this->arguments->locationname))
        {
          $this->echo("Missing argument --locationname <".$table."s locationname>\n");
          exit(4);
        }
        
        $select_array = array("name" => $this->arguments->locationname);
        $loc = $this->bunkerschild->get_dataset("location", $select_array);
        
        if (!is_object($loc))
        {
          $this->echo("Location ".$this->arguments->locationname." not found\n");
          exit(5);
        }
        
        $select_array = array("name" => $this->arguments->levelname, "location_uid" => $loc->uid);
        $lvl = $this->bunkerschild->get_dataset("level", $select_array);
        
        if (!is_object($lvl))
        {
          $this->echo("Level ".$this->arguments->levelname." not found\n");
          exit(6);
        }
        
        $data["name"] = $this->arguments->name;
        $data["level_uid"] = $lvl->uid;
      }
      
      $this->echo("Registering ".$table." ".$this->arguments->name."...");
      
      if ($this->bunkerschild->add_dataset($table, $data))
      {
        $this->echo("done\n");
        exit;
      }
      else
      {
        $this->echo("failed\n");
        exit(2);
      }
    }
    
    private function get_datasets($table)
    {
      $this->echo("Getting registered ".$table."s...");
      $results = $this->bunkerschild->get_datasets($table);
      
      if (is_array($results))
      {
        $this->echo(count($results)." ".$table.((count($results) > 1) ? "s" : "")."\n");
        
        foreach ($results as $result)
        {
          $line = "";
          
          foreach (json_decode($result->to_json()) as $key => $val)
          {
            if (substr($key, 0, 2) == "__")
              continue;
              
            if ($key == "password")
              $val = "********";
              
            if ($key == "pin")
              $val = "****";
              
            $line .= ", ".$key.": ".$val;
          }
            
          $this->echo(" - ".substr($line, 2)."\n");
        }
        
        exit;
      }
      else
      {
        $this->echo("none found\n");
        exit(2);
      }
    }
    
    private function update_dataset($table, $update_array)
    {
      if (!isset($this->arguments->uid))
      {
        $this->echo("Missing argument --uid <".$table."s uid>\n");
        exit(1);
      }      
      
      $this->echo("Updating ".$table." with uid ".$this->arguments->uid."...");
      $retval = $this->bunkerschild->update_dataset($table, $this->arguments->uid, $update_array);
      
      if ($retval)
      {
        $this->echo("done\n");
        exit;
      }
      elseif ($retval === null)
      {
        $this->echo("not found\n");
        exit(4);
      }
      else
      {
        $this->echo("failed\n");
        exit(2);
      }      
    }
    
    private function get_dataset($table, $by_id = false, $extended_select = false)
    {
      if ($by_id)
      {
        if (!isset($this->arguments->uid))
        {
          $this->echo("Missing argument --uid <".$table."s uid>\n");
          exit(1);
        }      
      }
      else
      {
        if (!isset($this->arguments->name))
        {
          $this->echo("Missing argument --name <".$table."s name>\n");
          exit(1);
        }
      }

      $this->echo("Getting ".$table." ".(($by_id) ? " with uid ".$this->arguments->uid : $this->arguments->name)."...");

      if ($by_id)     
        $select_array = array("uid" => $this->arguments->uid);
      else
        $select_array = array("name" => $this->arguments->name);
        
      if ($extended_select)
        $select_array = array_merge($select_array, $extended_select);
      
      $result = $this->bunkerschild->get_dataset($table, $select_array);
      
      if (is_object($result))
      {
        $this->echo("done\n");

        foreach (json_decode($result->to_json()) as $key => $val)
        {
          if (substr($key, 0, 2) == "__")
            continue;
              
          echo " - ".$key.": ".$val."\n";
        }
        
        exit;
      }
      else
      {
        $this->echo("failed\n");
        exit(2);
      }      
    }
    
    private function delete_dataset($table)
    {
      if (!isset($this->arguments->name))
      {
        $this->echo("Missing argument --name <".$table."s name>\n");
        exit(1);
      }
      elseif (!isset($this->arguments->uid))
      {
        $this->echo("Missing argument --uid <".$table."s uid>\n");
        exit(3);
      }

      $this->echo("Deleting ".$table." ".$this->arguments->name."...");
      
      if ($this->bunkerschild->delete_dataset($table, array("uid" => $this->arguments->uid, "name" => "this->arguments->name")))
      {
        $this->echo("done\n");
        exit;
      }
      else
      {
        $this->echo("failed\n");
        exit(2);
      }      
    }
    
    private function set_flag($flag_suffix, $flag_value, $table, $where_key, $where_val)
    {
      if (!isset($this->arguments->$where_key))
      {
        $this->echo("Missing argument --".$where_key." <".$table."s ".$where_key.">\n");
        exit(1);
      }
      
      if ($flag_suffix == "enabled")
        $this->echo((($flag_value) ? "En" : "Dis")."abling ".$table." ".$where_val."...");
      else
        $this->echo("Setting flag ".$flag_suffix." to ".$flag_value." for ".$table." ".$where_val."...");
      
      if ($this->bunkerschild->set_flag($flag_suffix, $flag_value, $table, $where_key, $where_val))
      {
        $this->echo("done\n");
        exit;
      }
      else
      {
        $this->echo("failed\n");
        exit(2);
      }    
    }
    
    private function list_locations()
    {
      $this->get_datasets("location");
    }
    
    private function get_location()
    {      
      $this->get_dataset("location");
    }
    
    private function get_location_by_id()
    {      
      $this->get_dataset("location", true);
    }
    
    private function add_location()
    {
      $this->add_dataset("location");
    }
    
    private function delete_location()
    {
      $this->delete_dataset("location");
    }
    
    private function disable_location()
    {
      $this->set_flag("enabled", false, "location", "uid", $this->arguments->uid);
    }
    
    private function enable_location()
    {
      $this->set_flag("enabled", true, "location", "uid", $this->arguments->uid);
    }
    
    private function change_location_name()
    {
      if (!$this->arguments->name)
      {
        $this->echo("Missing arguments --name <locations name>\n");
        exit(3);
      }
      
      $this->update_dataset("location", array("name" => $this->arguments->name));
    }
    
    private function change_location_address()
    {
      if (!$this->arguments->address)
      {
        $this->echo("Missing arguments --address <locations address>\n");
        exit(3);
      }
      
      $this->update_dataset("location", array("address" => $this->arguments->address));
    }
    
    private function change_location_zipcode()
    {
      if (!$this->arguments->zipcode)
      {
        $this->echo("Missing arguments --zipcode <locations zipcode>\n");
        exit(3);
      }
      
      $this->update_dataset("location", array("zipcode" => $this->arguments->zipcode));
    }
    
    private function change_location_city()
    {
      if (!$this->arguments->city)
      {
        $this->echo("Missing arguments --city <locations city>\n");
        exit(3);
      }
      
      $this->update_dataset("location", array("city" => $this->arguments->city));
    }
    
    private function change_location_country()
    {
      if (!$this->arguments->country)
      {
        $this->echo("Missing arguments --city <locations country>\n");
        exit(3);
      }
      
      $this->update_dataset("location", array("country" => $this->arguments->country));
    }
    
    private function list_levels()
    {
      $this->get_datasets("level");
    }
    
    private function get_level()
    {
      if (!$this->arguments->name)
      {
        $this->echo("Missing arguments --name <levels name>\n");
        exit(3);
      }
      elseif (!$this->arguments->locationname)
      {
        $this->echo("Missing arguments --locationname <levels locationname>\n");
        exit(4);
      }
      
      $select_array = array("name" => $this->arguments->locationname);
      $loc = $this->bunkerschild->get_dataset("location", $select_array);
      
      if (!is_object($loc))
      {
        $this->echo("Location ".$this->arguments->locationname." not found\n");
        exit(5);
      }
      
      $extended_select = array("location_uid" => $loc->uid);
      
      $this->get_dataset("level", false, $extended_select);
    }
    
    private function get_level_by_id()
    {      
      $this->get_dataset("level", true);
    }
    
    private function add_level()
    {
      $this->add_dataset("level");
    }
    
    private function delete_level()
    {
      $this->delete_dataset("level");
    }
    
    private function disable_level()
    {
      $this->set_flag("enabled", false, "level", "uid", $this->arguments->uid);
    }
    
    private function enable_level()
    {
      $this->set_flag("enabled", true, "level", "uid", $this->arguments->uid);
    }
    
    private function change_level_name()
    {
      if (!$this->arguments->name)
      {
        $this->echo("Missing arguments --name <levels name>\n");
        exit(3);
      }
      
      $this->update_dataset("level", array("name" => $this->arguments->name));
    }
    
    private function change_level_value()
    {
      if (!$this->arguments->value)
      {
        $this->echo("Missing arguments --value <levels value>\n");
        exit(3);
      }
      
      $this->update_dataset("level", array("value" => $this->arguments->value));
    }
    
    private function change_level_location()
    {
      if (!$this->arguments->locationname)
      {
        $this->echo("Missing arguments --location <levels locationname>\n");
        exit(3);
      }
      
      $select_array = array("name" => $this->arguments->locationname);
      $loc = $this->bunkerschild->get_dataset("location", $select_array);
      
      if (!is_object($loc))
      {
        $this->echo("Location ".$this->arguments->locationname." not found\n");
        exit(4);
      }
            
      $this->update_dataset("level", array("location_uid" => $loc->uid));
    }
    
    private function list_rooms()
    {
      $this->get_datasets("room");
    }
    
    private function get_room()
    {
      if (!$this->arguments->name)
      {
        $this->echo("Missing arguments --name <rooms name>\n");
        exit(3);
      }
      elseif (!$this->arguments->locationname)
      {
        $this->echo("Missing arguments --locationname <rooms locationname>\n");
        exit(4);
      }
      elseif (!$this->arguments->levelname)
      {
        $this->echo("Missing arguments --levelname <rooms levelname>\n");
        exit(5);
      }
      
      $select_array = array("name" => $this->arguments->locationname);
      $loc = $this->bunkerschild->get_dataset("location", $select_array);
      
      if (!is_object($loc))
      {
        $this->echo("Location ".$this->arguments->locationname." not found\n");
        exit(6);
      }
      
      $select_array = array("name" => $this->arguments->levelname, "location_uid" => $loc->uid);
      $lvl = $this->bunkerschild->get_dataset("level", $select_array);
      
      if (!is_object($lvl))
      {
        $this->echo("Level ".$this->arguments->levelname." not found\n");
        exit(6);
      }
      
      $extended_select = array("level_uid" => $lvl->uid);
      
      $this->get_dataset("level", false, $extended_select);
    }
    
    private function get_room_by_id()
    {      
      $this->get_dataset("room", true);
    }
    
    private function add_room()
    {
      $this->add_dataset("room");
    }
    
    private function delete_room()
    {
      $this->delete_dataset("room");
    }
    
    private function disable_room()
    {
      $this->set_flag("enabled", false, "room", "uid", $this->arguments->uid);
    }
    
    private function enable_room()
    {
      $this->set_flag("enabled", true, "room", "uid", $this->arguments->uid);
    }
    
    private function change_room_name()
    {
      if (!$this->arguments->name)
      {
        $this->echo("Missing arguments --name <rooms name>\n");
        exit(3);
      }
      
      $this->update_dataset("room", array("name" => $this->arguments->name));
    }
    
    private function change_room_level()
    {
      if (!$this->arguments->locationname)
      {
        $this->echo("Missing arguments --location <rooms locationname>\n");
        exit(3);
      }
      elseif (!$this->arguments->levelname)
      {
        $this->echo("Missing arguments --level <rooms levelname>\n");
        exit(4);
      }
      
      $select_array = array("name" => $this->arguments->locationname);
      $loc = $this->bunkerschild->get_dataset("location", $select_array);
      
      if (!is_object($loc))
      {
        $this->echo("Location ".$this->arguments->locationname." not found\n");
        exit(5);
      }
            
      $select_array = array("name" => $this->arguments->levelname, "location_uid" => $loc->uid);
      $lvl = $this->bunkerschild->get_dataset("level", $select_array);
      
      if (!is_object($lvl))
      {
        $this->echo("Level ".$this->arguments->levelname." not found\n");
        exit(7);
      }
            
      $this->update_dataset("room", array("level_uid" => $lvl->uid));
    }
    
    private function add_user_to_group()
    {
      if (!isset($this->arguments->username))
      {
        $this->echo("Missing argument --username <users name>\n");
        exit(1);
      }
      elseif (!isset($this->arguments->groupname))
      {
        $this->echo("Missing argument --groupname <groups name>\n");
        exit(3);
      }
      
      $user = $this->bunkerschild->get_dataset("user", array("name" => $this->arguments->username));
      
      if (!$user)
      {
        $this->echo("User not found\n");
        exit(5);
      }
      
      $group = $this->bunkerschild->get_dataset("group", array("name" => $this->arguments->groupname));
      
      if (!$group)
      {
        $this->echo("Group not found\n");
        exit(6);
      }
      
      $this->echo("Adding user ".$this->arguments->username." to group ".$this->arguments->groupname."...");
      
      if ($this->bunkerschild->add_dataset("usergroup", array("user_uid" => $user->uid, "group_uid" => $group->uid)))
      {
        $this->echo("done\n");
        exit;
      }
      else
      {
        $this->echo("failed\n");
        exit(2);
      }
    }
    
    private function delete_user_from_group()
    {
      if (!isset($this->arguments->username))
      {
        $this->echo("Missing argument --username <users name>\n");
        exit(1);
      }
      elseif (!isset($this->arguments->groupname))
      {
        $this->echo("Missing argument --groupname <groups name>\n");
        exit(3);
      }
      
      $user = $this->bunkerschild->get_dataset("user", array("name" => $this->arguments->username));
      
      if (!$user)
      {
        $this->echo("User not found\n");
        exit(5);
      }
      
      $group = $this->bunkerschild->get_dataset("group", array("name" => $this->arguments->groupname));
      
      if (!$group)
      {
        $this->echo("Group not found\n");
        exit(6);
      }
      
      $this->echo("Deleting user ".$this->arguments->username." from group ".$this->arguments->groupname."...");
      $retval = $this->bunkerschild->delete_dataset("usergroup", array("user_uid" => $user->uid, "group_uid" => $group->uid));
      
      if ($retval)
      {
        $this->echo("done\n");
        exit;
      }
      elseif ($retval === null)
      {
        $this->echo("not found\n");
        exit(4);
      }
      else
      {
        $this->echo("failed\n");
        exit(2);
      }
    }
    
    private function add_group()
    {
      $this->add_dataset("group");
    }
    
    private function list_groups()
    {
      $this->get_datasets("group");
    }
    
    private function get_group()
    {      
      $this->get_dataset("group");
    }
    
    private function get_group_by_id()
    {      
      $this->get_dataset("group", true);
    }
    
    private function delete_group()
    {
      $this->delete_dataset("group");
    }
    
    private function disable_group()
    {
      $this->set_flag("enabled", false, "group", "uid", $this->arguments->uid);
    }
    
    private function enable_group()
    {
      $this->set_flag("enabled", true, "group", "uid", $this->arguments->uid);
    }
    
    private function change_group_name()
    {
      if (!$this->arguments->name)
      {
        $this->echo("Missing arguments --name <new users name>\n");
        exit(3);
      }
      
      $this->update_dataset("group", array("name" => $this->arguments->name));
    }
    
    private function add_user()
    {
      $this->add_dataset("user");
    }
    
    private function list_users()
    {
      $this->get_datasets("user");
    }
    
    private function get_user()
    {      
      $this->get_dataset("user");
    }
    
    private function get_user_by_id()
    {      
      $this->get_dataset("user", true);
    }
    
    private function delete_user()
    {
      $this->delete_dataset("user");
    }
    
    private function disable_user()
    {
      $this->set_flag("enabled", false, "user", "uid", $this->arguments->uid);
    }
    
    private function enable_user()
    {
      $this->set_flag("enabled", true, "user", "uid", $this->arguments->uid);
    }
    
    private function admin_user()
    {
      $this->set_flag("admin", true, "user", "uid", $this->arguments->uid);
    }
    
    private function noadmin_user()
    {
      $this->set_flag("admin", false, "user", "uid", $this->arguments->uid);
    }
    
    private function change_user_password()
    {
      if (!$this->arguments->password)
      {
        $this->echo("Missing arguments --password <new user password>\n");
        exit(3);
      }
      
      $this->update_dataset("user", array("password" => $this->bunkerschild->password_hash($this->arguments->password)));
    }
    
    private function change_user_pin()
    {
      if (!$this->arguments->pin)
      {
        $this->echo("Missing arguments --pin <new user pin>\n");
        exit(3);
      }
      
      $this->update_dataset("user", array("pin" => $this->bunkerschild->password_hash($this->arguments->pin)));
    }
    
    private function change_user_name()
    {
      if (!$this->arguments->name)
      {
        $this->echo("Missing arguments --name <new users name>\n");
        exit(3);
      }
      
      $this->update_dataset("user", array("name" => $this->arguments->name));
    }
    
    private function change_user_username()
    {
      if (!$this->arguments->username)
      {
        $this->echo("Missing arguments --username <new users username>\n");
        exit(3);
      }
      
      $this->update_dataset("user", array("username" => $this->arguments->username));
    }
    
    private function list_devices()
    {
      $this->get_datasets("device");
    }
    
    private function assign_device_room()
    {
      if (!$this->arguments->deviceuid)
      {
        $this->echo("Missing arguments --deviceuid <uid of device>\n");
        exit(3);
      }
      elseif (!$this->arguments->roomuid)
      {
        $this->echo("Missing arguments --roomuid <uid of room>\n");
        exit(4);
      }
      
      $room = $this->bunkerschild->get_dataset("room", array("uid" => $this->arguments->roomuid));
      
      if (!$room)
      {
        $this->echo("Specified room not found\n");
        exit(5);
      }
      elseif (!$room->flag_enabled)
      {
        $this->echo("Specified room is disabled\n");
        exit(6);
      }
      
      $device = $this->bunkerschild->get_dataset("device", array("uid" => $this->arguments->deviceuid));
      
      if (!$device)
      {
        $this->echo("Specified device not found\n");
        exit(7);
      }
      elseif (!$device->flag_enabled)
      {
        $this->echo("Specified device is disabled\n");
        exit(8);
      }
      
      $device->room_uid = $room->uid;
      
      if (!$device->update($this->bunkerschild->get_db_instance()))
      {
        $this->echo("Unable to update specified device\n");
        exit(9);
      }
      
      $this->echo("Room ".$room->name." has been assigned to device ".$device->name."\n");
      exit;
    }
    
    private function backup_db()
    {
      global $__CONFIG;
      
      if (isset($this->arguments->autoclean_days))
      {
        if ($this->arguments->autoclean_days > 0)
        {
          $this->echo("Cleaning up files, older than ".$this->arguments->autoclean_days." days...");
          
          $dir = opendir(PATH_BAK);
          
          if (is_resource($dir))
          {
            while ($file = readdir($dir))
            {
              if (($file == ".") || ($file == "..") || (substr($file, -7) != ".sql.gz"))
                continue;
              
              $filename = PATH_BAK.DS.$file;
              
              if ((filectime($filename) + (86400 * $this->arguments->autoclean_days)) < time())
                @unlink($filename);
            }
            
            closedir($dir);
          }
          
          $this->echo("done\n");
        }
      }
      
      $filename = PATH_BAK.DS.$__CONFIG->mysql_database."-".date("Y-m-d-H-i-s").".sql";
      $exec = "mysqldump -u ".$__CONFIG->mysql_username." --password=".$__CONFIG->mysql_password." -h ".$__CONFIG->mysql_hostname." --port ".$__CONFIG->mysql_port." ".$__CONFIG->mysql_database;
      
      $this->echo("Backing up database to ".$filename."...");
      
      if (trim(shell_exec($exec." > ".$filename." && gzip -9 ".$filename." && echo 1 || echo 0")))
      {
        $this->echo("done\n");
        exit;
      }
      else
      {
        $this->echo("failed\n");
        exit(1);
      }
    }
    
    public function register_available_methods()
    {
          $this->register_method("discover_devices");
          $this->register_method("pull_device");
          $this->register_method("add_group");
          $this->register_method("get_group");
          $this->register_method("get_group_by_id");
          $this->register_method("list_groups");
          $this->register_method("disable_group");
          $this->register_method("enable_group");
          $this->register_method("delete_group");
          $this->register_method("change_group_name");
          $this->register_method("add_user");
          $this->register_method("get_user");
          $this->register_method("get_user_by_id");
          $this->register_method("list_users");
          $this->register_method("disable_user");
          $this->register_method("enable_user");
          $this->register_method("delete_user");
          $this->register_method("admin_user");
          $this->register_method("noadmin_user");
          $this->register_method("change_user_password");
          $this->register_method("change_user_pin");
          $this->register_method("change_user_name");
          $this->register_method("change_user_username");
          $this->register_method("add_user_to_group");
          $this->register_method("delete_user_from_group");
          $this->register_method("add_location");
          $this->register_method("list_locations");
          $this->register_method("get_location");
          $this->register_method("get_location_by_id");
          $this->register_method("enable_location");
          $this->register_method("disable_location");
          $this->register_method("delete_location");
          $this->register_method("change_location_name");
          $this->register_method("change_location_address");
          $this->register_method("change_location_zipcode");
          $this->register_method("change_location_city");
          $this->register_method("change_location_country");
          $this->register_method("add_level");
          $this->register_method("list_levels");
          $this->register_method("get_level");
          $this->register_method("get_level_by_id");
          $this->register_method("enable_level");
          $this->register_method("disable_level");
          $this->register_method("delete_level");
          $this->register_method("change_level_name");
          $this->register_method("change_level_value");
          $this->register_method("change_level_location");
          $this->register_method("add_room");
          $this->register_method("list_rooms");
          $this->register_method("get_room");
          $this->register_method("get_room_by_id");
          $this->register_method("enable_room");
          $this->register_method("disable_room");
          $this->register_method("delete_room");
          $this->register_method("change_room_name");
          $this->register_method("change_room_level");
          $this->register_method("list_devices");
          $this->register_method("assign_device_room");
          $this->register_method("backup_db");
    }
    
    public static function exit_handler()
    {
      exit(255);
    }    
    
    public function register_available_signals()
    {
          $this->register_signal(SIGTERM, array("\\".get_class($this), "exit_handler"));
          $this->register_signal(SIGINT, array("\\".get_class($this), "exit_handler"));
    }
}
