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

namespace BunkerschildFramework\traits\driver;

trait Sonoff
{
  private $device = null;
  
  function __construct(\BunkerschildFramework\device $device)
  {
    $this->device = $device;
  }
  
  public function act($actorname, $actorvalue)
  {
    $uri = sprintf(
     self::SONOFF_CMND_REQUEST_URI, 
     $this->device->ipaddress, 
     rawurlencode(self::SONOFF_LOGIN_USERNAME), 
     rawurlencode(self::SONOFF_LOGIN_PASSWORD), 
     rawurlencode($actorname." ".$actorvalue)
    );
    
    return json_decode(file_get_contents($uri));    
  }
  
  public function pull()
  {
    $uri = sprintf(
     self::SONOFF_CMND_REQUEST_URI, 
     $this->device->ipaddress, 
     rawurlencode(self::SONOFF_LOGIN_USERNAME), 
     rawurlencode(self::SONOFF_LOGIN_PASSWORD), 
     rawurlencode(self::SONOFF_STATUS_ANY)
    );
    
    return json_decode(file_get_contents($uri));
  }
}
