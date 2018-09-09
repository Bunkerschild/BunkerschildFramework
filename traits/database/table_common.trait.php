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

namespace BunkerschildFramework\traits\database;

trait table_common
{
  private $uid = "";
  private $timestamp_last_update = "";
  private $timestamp_registration = "";
  private $flag_enabled = "";
  
  public function insert(\MySQLi $db)
  {
    $insert = "INSERT INTO `".$this->__table_name."` (";
        
    foreach ($this as $key => $val)
    {
      if (($key == "__table_name") || ($key == "uid") || ($key == "timestamp_last_update"))
        continue;
        
      $insert1 .= ",`".$key."`";
      
      if ($key == "timestamp_registration")
        $insert2 .= ",NOW()";
      elseif (($key == "flag_enabled") && ($val == ""))
        $insert2 .= ",1";
      else
        $insert2 .= ",'".$db->real_escape_string($val)."'";
    }
    
    $insert .= substr($insert1, 1).") VALUES (".substr($insert2, 1).")";
    
    if ($db->query($insert))
      return $db->insert_id;
      
    return null;
  }
  
  public function update(\MySQLi $db)
  {
    $update = "";
        
    foreach ($this as $key => $val)
    {
      if (($key == "__table_name") || ($key == "uid") || ($key == "timestamp_last_update") || ($key == "timestamp_registration"))
        continue;
        
      $update .= ", `".$key."` = '".$db->real_escape_string($val)."'";
    }
    
    $update = "UPDATE `".$this->__table_name."` SET ".substr($update, 2)." WHERE `uid` = '".$db->real_escape_string($this->uid)."' LIMIT 1";

    if ($db->query($update))
      return $this->uid;
      
    return null;
  }

  public function delete(\MySQLi $db)
  {
    $delete = "";
        
    foreach ($this as $key => $val)
    {
      if ($key == "__table_name")
        continue;
        
      $delete .= "AND `".$key."` = '".$db->real_escape_string($val)."'";
    }
    
    $delete = "DELETE FROM `".$this->__table_name."` WHERE ".substr($delete, 4)." LIMIT 1";

    if ($db->query($delete))
      return $this->uid;
      
    return null;
  }

  public function select(\MySQLi $db)
  {
    $select = "";
        
    foreach ($this as $key => $val)
    {
      if ($key == "__table_name")
        continue;
        
      if ($val == "")
        continue;
        
      $select .= "AND `".$key."` = '".$db->real_escape_string($val)."'";
    }
    
    $select = "SELECT * FROM `".$this->__table_name."` WHERE ".substr($select, 4)." LIMIT 1";
    
    if ($result = $db->query($select))
    {
      if ($result->num_rows == 0)
        return null;
      
      $obj = $result->fetch_object();
      $result->free_result();
      
      foreach ($obj as $key => $val)
      {
        $this->$key = $val;
      }
        
      return $this->uid;
    }
      
    return null;
  }
}
