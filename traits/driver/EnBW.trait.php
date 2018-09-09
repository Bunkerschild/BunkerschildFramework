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

trait EnBW
{
  private $device = null;
  
  function __construct(\BunkerschildFramework\device $device)
  {
    $this->device = $device;
  }
  
  private function get_request($path)
  {
    $ch = curl_init();
    
    if (substr($path, 0, 1) == "/")
      $path = substr($path, 1);
    
    curl_setopt($ch, CURLOPT_URL, "http://".$this->device->ipaddress."/".$path);    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    $output = curl_exec($ch);
    
    curl_close($ch);
    
    return $output;
  }
  
  private function get_power_profile($timestamp = 0, $number = 1)
  {
    return simplexml_load_string($this->get_request("/InstantView/request/getPowerProfile.html?ts=".$timestamp."&n=".$number."&param=Wirkleistung&format=1"));
  }
  
  private function get_power_meter()
  {
    $pws = $this->get_request("?action=5");
    
    $retval = new \stdClass;
    
    $meter_id = explode('<div class="meter-id">', $pws);
    $meter_id = explode("</div>", $meter_id[1]);
    $meter_id = explode("<br>", $meter_id[0])[1];
    
    $whats = explode('<div class="whats">', $pws);
    $whats = explode("</div>", $whats[1]);
    $whats = explode(" ", $whats[0]);

    $small_whats = explode('<div class="small-whats">', $pws);
    $small_whats = explode("</div>", $small_whats[1]);
    $small_whats = explode(" ", $small_whats[0]);
    
    $retval->Power = $whats[0];
    $retval->Voltage = 230;
    $retval->Current = ($retval->Power / $retval->Voltage);
    $retval->Resistence = ($retval->Voltage / $retval->Current);
    $retval->Power15 = $small_whats[0];
    $retval->Voltage15 = 230;
    $retval->Current15 = ($retval->Power15 / $retval->Voltage15);
    $retval->Resistence15 = ($retval->Voltage15 / $retval->Current15);
    $retval->MeterID = $meter_id;

    $pwc = explode("<h3>", $this->get_request("?action=20"));
    
    $StartTime = new \DateTime(self::ENBW_METER_START_TIMESTAMP);
    $ActualTime = new \DateTime();
    
    $run_time = $StartTime->diff($ActualTime);
    
    $retval->StartTime = date("Y-m-d H:i:s", $StartTime->getTimestamp());
    $retval->ActualTime = date("Y-m-d H:i:s", $ActualTime->getTimestamp());
    $retval->RunTime = $run_time->y.".".$run_time->m.".".$run_time->d." ".$run_time->h.":".$run_time->i.":".$run_time->s;
    
    $ht = explode(" ", explode("</h3>", $pwc[2])[0]);
    $nt = explode(" ", explode("</h3>", $pwc[4])[0]);
       
    $retval->HTValue = $ht[0];
    $retval->HTYearly = ($retval->HTValue / $run_time->y);
    $retval->HTMonthly = ($retval->HTValue / (($run_time->y * 12) + $run_time->m));
    $retval->HTDaily = ($retval->HTValue / $run_time->days);
    $retval->HTKwhPriceValue = self::ENBW_PRICE_EUR_KWH_HT;
    $retval->HTKwhPriceTotal = ($ht[0] * self::ENBW_PRICE_EUR_KWH_HT);
    $retval->HTKwhPriceYearly = ($retval->HTKwhPriceTotal / $run_time->y);
    $retval->HTKwhPriceMonthly = ($retval->HTKwhPriceTotal / (($run_time->y * 12) + $run_time->m));
    $retval->HTKwhPriceDaily = ($retval->HTKwhPriceTotal / $run_time->days);
    
    $retval->NTValue = $nt[0];
    $retval->NTYearly = ($retval->NTValue / $run_time->y);
    $retval->NTMonthly = ($retval->NTValue / (($run_time->y * 12) + $run_time->m));
    $retval->NTDaily = ($retval->NTValue / $run_time->days);
    $retval->NTKwhPriceValue = self::ENBW_PRICE_EUR_KWH_NT;
    $retval->NTKwhPriceTotal = ($ht[0] * self::ENBW_PRICE_EUR_KWH_NT);
    $retval->NTKwhPriceYearly = ($retval->NTKwhPriceTotal / $run_time->y);
    $retval->NTKwhPriceMonthly = ($retval->NTKwhPriceTotal / (($run_time->y * 12) + $run_time->m));
    $retval->NTKwhPriceDaily = ($retval->NTKwhPriceTotal / $run_time->days);
    
    $retval->SumValue = ($nt[0] + $ht[0]);
    $retval->SumYearly = ($retval->SumValue / $run_time->y);
    $retval->SumMonthly = ($retval->SumValue / (($run_time->y * 12) + $run_time->m));
    $retval->SumDaily = ($retval->SumValue / $run_time->days);
    $retval->SumKwhPriceTotal = (($nt[0] * self::ENBW_PRICE_EUR_KWH_NT) + ($ht[0] * self::ENBW_PRICE_EUR_KWH_HT));
    $retval->SumKwhPriceYearly = ($retval->SumKwhPriceTotal / $run_time->y);
    $retval->SumKwhPriceMonthly = ($retval->SumKwhPriceTotal / (($run_time->y * 12) + $run_time->m));
    $retval->SumKwhPriceDaily = ($retval->SumKwhPriceTotal / $run_time->days);
    
    return $retval;
  }
  
  public function pull()
  {
    $power = new \stdClass;
    $power->Status = new \stdClass;
    $power->Status->Topic = "Smartmeter";
    $power->Status->FriendlyName = "Stromzaehler";
    
    $power->StatusPRM = new \stdClass;
    $power->StatusPRM->GroupTopic = "EnBW Smartmeter";
    
    $power->StatusTIM = new \stdClass;
    $power->StatusTIM->Local = date("r");
  
    $power->StatusSNS = new \stdClass;
    $power->StatusSNS->ENERGY = $this->get_power_meter();
        
    return $power;
  }
}
