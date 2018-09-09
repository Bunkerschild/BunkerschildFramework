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

trait XMLD
{
    public static $exit = false;
    public static $conn = null;
        
    private $socket_handle = null;    
    private $socket_error = null;
    private $socket_errno = null;
    
    private $signature = null;
    private $tcp_accept = null;
    
    private $request_method = null;
    private $request_uri = null;
    private $request_protocol = null;
    private $request_header = null;
    
    private $remote_addr = null;
    private $remote_port = null;
    
    private $server_name = null;
    private $server_port = null;
    
    private $selected_channel = null;
    private $selected_serial = null;
    
    private $data = null;
    private $post = null;
    private $get = null;
    
    public function register_available_methods()
    {
      $this->register_method("daemon", "Start the Bunkerschild xml daemon");
    }

    public function register_available_signals()
    {
      $this->register_signal(SIGTERM, array("\\".get_class($this), "daemon_exit"));
      $this->register_signal(SIGALRM, array("\\".get_class($this), "daemon_alarm"));
      $this->register_signal(SIGINT, array("\\".get_class($this), "daemon_exit"));
    }
    
    public static function daemon_exit()
    {
      self::$exit = true;
    }
    
    public static function daemon_alarm()
    {
      @fclose(self::$conn);
    }
    
    private function daemon()
    {    
      $this->signature = $this->bunkerschild->get_program_name()." ".$this->bunkerschild->get_version()." (Build: ".$this->bunkerschild->get_version_timestamp().")";
      $this->tcp_accept = "0.0.0.0:".(($__CONFIG->xml_port) ? $__CONFIG->xml_port : 888);
            
      $this->daemon_log("Listening on ".$this->tcp_accept);
      
      while (!self::$exit)
      {
        $this->socketserver_create();
        $this->socketserver_accept();
        $this->socketserver_close();
      }
    }
    
    private function get_requested_device()
    {
      if (!$this->selected_serial)
        return null;
        
      $db = $this->bunkerschild->get_db_instance();

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
      $select .= "device.emulation != 'none' AND ";
      $select .= "device.serial = '".$db->real_escape_string($this->selected_serial)."' ";
      $select .= "GROUP BY device.uid";

      $result = $db->query($select);
      $device = new \stdClass;
      
      if ((is_object($result)) && ($result->num_rows))
      {
        $device->details = $result->fetch_object();
        $result->free_result();
        
        $device->actors = array();
        
        $result = $db->query("SELECT * FROM actor WHERE device_uid = ".$device->details->uid." AND flag_enabled = 1");
        
        if ((is_object($result)) && ($result->num_rows))
        {
          while ($actor = $result->fetch_object())
          {
            $device->actors[$actor->channel] = $actor;
          }
          
          $result->free_result();
        }

        return $device;
      }
      
      return null;
    }
    
    private function get_error_document(&$response_status, &$response_header, &$response_body)
    {
      $error_document = implode("", file(PATH_STATIC.DS."errorpage.shtml"));
      
      switch ($response_status)
      {
        case 400:
          $errormsg = "Bad Request";
          break;
        case 401:
          $errormsg = "Unauthorized";
          break;
        case 402:
          $errormsg = "Payment Required";
          break;
        case 403:
          $errormsg = "Forbidden";
          break;
        case 404:
          $errormsg = "Not Found";
          break;
        case 405:
          $errormsg = "Method Not Allowed";
          break;
        case 406:
          $errormsg = "Not Acceptable";
          break;
        case 410:
          $errormsg = "Gone";
          break;
        case 411:
          $errormsg = "Length Required";
          break;
        case 412:
          $errormsg = "Precondition Failed";
          break;
        case 501:
          $errormsg = "Not Implemented";
          break;
        case 503:
          $errormsg = "Service Unavailable";
          break;
        default:
          $response_status = 500;
          $errormsg = "Internal Server Error";
          break;      
      }

      $title = $response_status." ".$errormsg;
      $errormsg = "Error ".$response_status." &raquo; ".$errormsg;
      
      $footer = $this->bunkerschild->get_program_name()." ".$this->bunkerschild->get_version()." (Build: ".$this->bunkerschild->get_version_timestamp().")";
      $footer .= " &middot; Copyright ".$this->bunkerschild->get_copyright();
      
      $response_body = str_replace(
        "%TITLE%", $title, str_replace (
          "%ERRORMSG%", $errormsg, str_replace(
            "%FOOTER%", $footer, $error_document
          )
        )
      );
      
      $response_header = array("Content-Type" => "text/plain", "Content-Length" => strlen($response_body));
      $response_status = strtoupper($title);
    }
    
    private function get_application(&$response_status, &$response_header, &$response_body)
    {
      global $__CONFIG;

      $response_status = 500;
      $response_body = "";
      $response_header = array();
      
      if ($this->server_name != $__CONFIG->xml_server)
      {
        $response_status = 406;
      }
      elseif ($this->selected_serial)
      {
        $path = explode("/", $this->request_uri);
        
        switch ($path[1])
        {
          case "serverinfo.xml":
            $response_body = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n";
            $response_body .= '<html><head><title>'.$this->signature.'</title></head><body><h1>'.$this->signature.'</h1></body></html>'."\n";
      
            $response_status = "200 OK";
            $response_header = array("Content-Length" => strlen($body));
            break;
          case "api":
            if (!isset($path[3]))
            {
              $response_code = 412;
              break;
            }
            
            $this->hue_bridge("api", $response_status, $response_header, $response_body, $path[2], $path[3], ((isset($path[4])) ? $path[4] : false), ((isset($path[5])) ? $path[5] : false));
            break;
          case "description.xml":
            $this->hue_bridge("description", $response_status, $response_header, $response_body);
            break;
          case "upnp":
            if (!isset($path[2]))
            {
              $response_status = 404;
            }
            else
            {
              switch($path[2])
              {
                case "control":
                case "event":
                case "bunkerschild":
                  if (!isset($path[3]))
                  {
                    $response_status = 412;
                  }
                  else
                  {
                    $this->wemo_bridge($path[2], $path[3], $response_status, $response_header, $response_body);
                  }
                  break;
                default:
                  $response_status = 404;
                  break;
              }
            }
            break;
          default:
            $response_status = 404;
            break;            
        }
      }
      else
      {
        $response_status = 403;
      }
      
      if ((is_numeric($response_status)) || ($response_body == ""))
        $this->get_error_document($response_status, $response_header, $response_body);
    }
    
    private function hue_bridge($docid, &$response_status, &$response_header, &$response_body, $hueid = false, $type = false, $id = false, $command = false)
    {
      $device = $this->get_requested_device();
      
      if (!$device)
      {
        $response_status = 410;
        return;
      }
      else
      {
        switch ($docid)
        {
          case "api":
            switch ($type)
            {
              case "lights":
                if (($id) && ($command))
                {
                  switch ($command)
                  {
                    case "state":
                      $state = json_decode($this->data);
                      $selected_actor = $device->actors[$id];
                      
                      if (!$state)
                      {
                        $response_code = 400;
                        return;
                      }
                      
                      if (!is_object($selected_actor))
                      {
                        $response_code = 404;
                        return;
                      }
                      
                      if (isset($state->on))
                      {
                        $queue = new \BunkerschildFramework\database\table\queue;
                        
                        $queue->actor_uid = $selected_actor->uid;
                        $queue->value = (($state->on) ? "ON" : "OFF");
                        
                        $queue->enqueue($this->bunkerschild->get_db_instance());
                        
                        $path = "/lights/".$id."/state/on";
                        
                        $result = new \stdClass;
                        $result->success = new \stdClass;
                        $result->success->$path = (($state->on) ? "ON" : "OFF");

                        $response_body = json_encode(array($result));
                        $response_status = "200 OK";
                        $response_header = array("Content-Type" => "application/json", "Content-Length" => strlen($response_body));
                        
                        return;
                      }
                      break;
                    default:
                      $response_code = 403;
                      return;
                  }
                }
                
                $hues = new \stdClass;
                
                foreach ($device->actors as $actor)
                {
                  $channel = $actor->channel;
                    
                  $hue = new \stdClass;
                  $hue->state = new \stdClass;
                  $hue->state->on = (($actor->value == "ON") ? true : false);
                  
                  if ($id)
                  {                  
                    $hue->state->bri = 0; // Brightness
                    $hue->state->hue = 0; // Hue
                    $hue->state->sat = 0; // Saturation                  
                    $hue->state->xy = array(0.5, 0.5); // XY Colorcode
                    $hue->state->ct = 500; // Colortemperature
                    $hue->state->colormode = "hs";
                  }
                  
                  $hue->state->effect = "none";
                  $hue->state->alert = "none";
                  $hue->state->reachable = (($device->details->flag_online) ? true : false);
                  $hue->type = "Extended color light";
                  $hue->name = iconv("UTF-8", "ISO-8859-1", $device->details->friendlyname).((count($device->actors) > 1) ? " ".$actor->channel : "");
                  $hue->manufacturername = $device->details->vendorname;
                  $hue->modelid = "LLM001";
                  $hue->uniqueid = $device->details->hwaddress.":00:11-".$actor->channel;
                  $hue->swversion = "5.50.1.19085";
                  
                  $hues->$channel = $hue;
                  
                  if (!$id)
                    $response_body = json_encode($hues);
                  else
                    $response_body = json_encode($hues->$id);
                    
                  $response_status = "200 OK";
                  $response_header = array("Content-Type" => "application/json", "Content-Length" => strlen($response_body));
                }
                return;
              case "groups":                
                $hues = new \stdClass;
                $hues->name = iconv("UTF-8", "ISO-8859-1", $device->details->friendlyname);
                $hues->lights = array();
                
                $on = 0;
                
                foreach ($device->actors as $actor)
                {
                  array_push($hues->lights, $actor->channel);
                  
                  if ($actor->value == "ON")
                    $on++;
                }
                  
                $hues->type = "LightGroup";
                $hues->action = new \stdClass;
                $hues->action->on = (($on) ? true : false);
                $hues->action->bri = 0;
                $hues->action->hue = 0;
                $hues->action->sat = 0;
                $hues->action->xy = array(0.5, 0.5);
                $hues->action->ct = 500;
                $hues->action->colormode = "hs";
                $hues->action->alert = "none";
                $hues->action->effect = "none";
                $hues->action->reachable = (($device->details->flag_online) ? true : false);
                
                $response_body = json_encode($hues);
                $response_status = "200 OK";
                $response_header = array("Content-Type" => "application/json", "Content-Length" => strlen($response_body));
                return;
              default:
                $response_code = 400;
                break;
            }
            break;
          case "description":
            $checksum = md5("hue".$this->selected_serial);                            
            $udn = "uuid:".substr($checksum, 0, 8)."-".substr($checksum, 8, 4)."-".substr($checksum, 12, 4)."-".substr($checksum, 16, 4)."-".substr($checksum, 20);
                                      
            $response_body = '<?xml version="1.0"?>
              <root xmlns="urn:schemas-upnp-org:device-1-0">
                <specVersion>
                  <major>1</major>
                  <minor>0</minor>
                </specVersion>
                <URLBase>http://'.$this->request_header["Host"].'/</URLBase>
                <device>
                  <deviceType>urn:schemas-upnp-org:device:Basic:1</deviceType>
                  <friendlyName>Amazon-Echo-HA-Bridge (".$this->request_header["Host"].")</friendlyName>
                  <manufacturer>Royal Philips Electronics</manufacturer>
                  <modelDescription>Philips hue Personal Wireless Lighting</modelDescription>
                  <modelName>Philips hue bridge 2012</modelName>
                  <modelNumber>929000226503</modelNumber>
                  <serialNumber>'.$this->selected_serial.'</serialNumber>
                  <UDN>'.$udn.'</UDN>
                </device>
              </root>'."\n";
              
            $response_status = "200 OK";
            $response_header = array("Content-Type" => "text/xml", "Content-Length" => strlen($response_body));              
            return;
          default:
            $response_status = 412;
            return;
        }
      }
    }
    
    private function wemo_bridge($type, $docid, &$response_status, &$response_header, &$response_body)
    {
      $device = $this->get_requested_device();
            
      if (!$device)
      {
        $response_status = 410;
        return;
      }
      else
      {
        switch ($type)
        {
          case "control":
            switch ($docid)
            {
              case "metainfo1":
                $response_status = 501;
                break;
              case "basicevent1":
                $sml = simplexml_load_string($this->data);
                $sml->registerXPathNamespace("s", "http://www.w3.org/2003/05/soap-envelope");
                $sml->registerXPathNamespace("u", "urn:Belkin:service:basicevent:1");

                if ((isset($sml->xpath('//u:GetBinaryState')[0]->BinaryState)) || (isset($sml->xpath('//u:SetBinaryState')[0]->BinaryState)))
                {
                  $selected_actor = $device->actors[$this->selected_channel];
                  
                  if (is_object($selected_actor))
                  {
                    if (isset($sml->xpath('//u:SetBinaryState')[0]->BinaryState))
                    {
                      $r = "SET";
                      $set_state = $sml->xpath('//u:SetBinaryState')[0]->BinaryState;
                      
                      if (count($device->actors) > 0)
                      {
                        $queue = new \BunkerschildFramework\database\table\queue;
                        
                        $queue->actor_uid = $selected_actor->uid;
                        $queue->value = (($set_state == "1") ? "ON" : "OFF");
                        
                        $queue->enqueue($this->bunkerschild->get_db_instance());
                      }
                    }
                    else
                    {
                      $r = "GET";
                    }
                    
                    $response_body = '<?xml version="1.0"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                            <s:Body>
                                    <u:'.$r.'BinaryStateResponse xmlns:u="urn:Belkin:service:basicevent:1">
                                            <BinaryState>'.(($selected_actor->value == "ON") ? "1" : "0").'</BinaryState>
                                    </u:'.$r.'BinaryStateResponse>
                            </s:Body>
                    </s:Envelope>'."\n";
                    
                    $response_status = "200 OK";
                    $response_header = array("Content-Type" => "text/plain", "Content-Length" => strlen($response_body));              
                  }
                  else
                  {
                    $response_status = 404;
                  }
                }
                else
                {
                  $response_status = 400;
                }
                break;
              default:
                $response_status = 412;
                return;
            }
            break;
          case "event":
            switch ($docid)
            {
              case "metainfo1":
                $response_status = 501;
                break;
              case "basicevent1":
                $response_status = 501;
                break;
              default:
                $response_status = 412;
                return;
            }
            break;
          case "bunkerschild":
            switch ($docid)
            {
              case "eventservice.xml":
                $response_body = '<scpd xmlns="urn:Belkin:service-1-0">
                        <specVersion>
                                <major>1</major>
                                <minor>0</minor>
                        </specVersion>
                        <actionList>
                                <action>
                                        <name>SetBinaryState</name>
                                        <argumentList>
                                                <argument>
                                                        <retval/>
                                                        <name>BinaryState</name>
                                                        <relatedStateVariable>BinaryState</relatedStateVariable>
                                                        <direction>in</direction>
                                                </argument>
                                        </argumentList>
                                </action>
                                <action>
                                        <name>GetBinaryState</name>
                                        <argumentList>
                                                <argument>
                                                        <retval/>
                                                        <name>BinaryState</name>
                                                        <relatedStateVariable>BinaryState</relatedStateVariable>
                                                        <direction>out</direction>
                                                </argument>
                                        </argumentList>
                                </action>
                        </actionList>
                        <serviceStateTable>
                                <stateVariable sendEvents="yes">
                                        <name>BinaryState</name>
                                        <dataType>Boolean</dataType>
                                        <defaultValue>0</defaultValue>
                                </stateVariable>
                                <stateVariable sendEvents="yes">
                                        <name>level</name>
                                        <dataType>string</dataType>
                                        <defaultValue>0</defaultValue>
                                </stateVariable>
                        </serviceStateTable>
                </scpd>'."\n";

                $response_status = "200 OK";
                $response_header = array("Content-Type" => "text/plain", "Content-Length" => strlen($response_body));
                break;
              case "metainfoservice.xml":
                $response_body = '<scpd xmlns="urn:Belkin:service-1-0">
                        <specVersion>
                                <major>1</major>
                                <minor>0</minor>
                        </specVersion>
                        <actionList>
                                <action>
                                        <name>GetMetaInfo</name>
                                        <argumentList>
                                                <retval />
                                                <name>GetMetaInfo</name>
                                                <relatedStateVariable>MetaInfo</relatedStateVariable>
                                                <direction>in</direction>
                                        </argumentList>
                                </action>
                        </actionList>
                        <serviceStateTable>
                                <stateVariable sendEvents="yes">
                                        <name>MetaInfo</name>
                                        <dataType>string</dataType>
                                        <defaultValue>0</defaultValue>
                                </stateVariable>
                        </serviceStateTable>
                </scpd>'."\n";
                
                $response_status = "200 OK";
                $response_header = array("Content-Type" => "text/plain", "Content-Length" => strlen($response_body));
                break;
              case "setup.xml":
                $response_body = '<?xml version="1.0"?>
                <root xmlns="urn:Belkin:device-1-0">
                        <specVersion>
                                <major>1</major>
                                <minor>0</minor>
                        </specVersion>
                        <URLBase>http://'.$this->header["Host"].'</URLBase>
                        <device>
                                <deviceType>urn:Belkin:device:controllee:1</deviceType>
                                <friendlyName>'.iconv("UTF-8", "ISO-8859-1", $device->details->friendlyname).(($this->selected_channel > 1) ? " ".$this->selected_channel : "").'</friendlyName>
                                <manufacturer>Belkin International Inc.</manufacturer>
                                <modelName>'.$device->details->vendorname.'</modelName>
                                <modelNumber>1.0</modelNumber>
                                <modelDescription>Belkin Bridge Emulation by Bunkerschild</modelDescription>
                                <modelURL>http://'.$device->details->ipaddress.'</modelURL>
                                <UDN>uuid:Socket-1_0-'.$device->details->serial.$this->selected_channel.'</UDN>
                                <serialNumber>'.$device->details->serial.$this->selected_channel.'</serialNumber>
                                <firmwareVersion>'.$device->details->firmware.'</firmwareVersion>
                                <iconVersion>0|49153</iconVersion>
                                <binaryState>0</binaryState>
                                <iconList>
                                        <icon>
                                                <mimetype>image/png</mimetype>
                                                <width>100</width>
                                                <height>100</height>
                                                <depth>100</depth>
                                                <url>icon.png</url>
                                        </icon>
                                </iconList>
                                <serviceList>
                                        <service>
                                                <serviceType>urn:Belkin:service:basicevent:1</serviceType>
                                                <serviceId>urn:Belkin:serviceId:basicevent1</serviceId>
                                                <controlURL>/upnp/control/basicevent1</controlURL>
                                                <eventSubURL>/upnp/event/basicevent1</eventSubURL>
                                                <SCPDURL>/upnp/bunkerschild/eventservice.xml</SCPDURL>
                                        </service>
                                        <service>
                                                <serviceType>urn:Belkin:service:metainfo:1</serviceType>
                                                <serviceId>urn:Belkin:serviceId:metainfo1</serviceId>
                                                <controlURL>/upnp/control/metainfo1</controlURL>
                                                <eventSubURL>/upnp/event/metainfo1</eventSubURL>
                                                <SCPDURL>/upnp/bunkerschild/metainfoservice.xml</SCPDURL>
                                        </service>
                                </serviceList>
                                <presentationURL>/presentation.html</presentationURL>
                        </device>
                </root>'."\n";
                
                $response_status = "200 OK";
                $response_header = array("Content-Type" => "text/xml", "Content-Length" => strlen($response_body));
                break;
              default:
                $response_status = 412;
                return;
            }
            break;
          default:
            $response_status = 412;
            break;
        }
      }
    }
    
    private function socketserver_create()
    {
      global $__CONFIG;
      
      $this->socket_error = null;
      $this->socket_errno = null;

      $this->socketserver_close();
      
      $this->socket_handle = stream_socket_server("tcp://".$this->tcp_accept, $this->socket_errno, $this->socket_error);
      
      if (!$this->socket_handle)
      {
        $this->daemon_log("Socket error ".$this->socket_errno.": ".$this->socket_error);
        exit(1);
      }
    }
    
    private function socketserver_accept()
    {
      if (!$this->socket_handle)
        return;
        
      $defaults = array(
        'Content-Type' => 'text/html',
        'Server' => $this->signature,
        'Expires' => date("r", (time() - 86400)),
        'Cache-Control' => 'no-cache, must-revalidate',
        'Connection' => 'close',
        'Date' => date("r")
      );
      
      $peer = null;
      
      $this->request_method = null;
      $this->request_uri = null;
      $this->request_protocol = null;
      $this->request_header = array();
      
      $this->server_name = null;
      $this->server_port = null;
      
      $this->selected_channel = null;
      $this->selected_serial = null;
      
      $this->data = "";
      $this->post = null;
      $this->get = null;
      
      $header_done = false;

      while (self::$conn = @stream_socket_accept($this->socket_handle, 5, $peer))
      {
        $this->request_method = null;
        $this->request_uri = null;
        $this->request_protocol = null;
        $this->request_header = array();
        
        $this->server_name = null;
        $this->server_port = null;
        
        $this->selected_channel = null;
        $this->selected_serial = null;
        
        $this->data = "";
        $this->post = null;
        $this->get = null;
        
        $header_done = false;

        if (self::$exit)
          break;
        
        pcntl_alarm(15);
        
        $ipport = explode(":", $peer);
        
        $this->remote_port = $ipport[(count($ipport)-1)];
        unset($ipport[(count($ipport)-1)]);
        
        $this->remote_addr = implode(":", $ipport);          
        $this->daemon_log("New connection esthablished from ".$this->remote_addr);
        
        $skip_next = false;
        $set_length = -1;
        $index = 0;        
        $error = 0;
        $code = 0;
          
        while (((!self::$exit) || is_resource(self::$conn)))
        {
          $line = "";
          
          if (!$skip_next)
          {
            while (((!self::$exit) || is_resource(self::$conn)))
            {
              if (!is_resource(self::$conn))
                return;
                
              $c = @fgetc(self::$conn);
              
              $line .= $c;
              
              if ($set_length !== -1)
              {
                $set_length--;
                
                if ($set_length <= 0)
                  break;
              }
              elseif ($c == "\n")
              {
                  break;          
              }
            }
          }
          else
          {
            $skip_next = false;
          }
          
          $index++;
          
          if (!$this->request_protocol)
          {
            if ($index > 1)
              return;
              
            $stateline = explode(" ", trim($line));
            
            $this->request_method = $stateline[0];
            $this->request_uri = $stateline[1];
            $this->request_protocol = $stateline[2];
            
            $uri = explode("?", $this->request_uri, 2);
            
            if (isset($uri[1]))
            {
              $this->get = array();
              
              foreach (explode("&", $uri[1]) as $keyval)
              {
                $field = explode("=", $keyval, 2);
                
                if ($key)
                {
                  $key = $field[0];
                  $val = ((isset($field[1])) ? rawurldecode($field[1]) : "");
                }
                
                $this->get[$key] = $val;
              }
              
              $this->request_uri = $uri[0];              
            }

            $this->daemon_log($this->request_method."-Request for ".$this->request_uri." from ".$this->remote_addr);
          }
          elseif (!$header_done)
          {
            if (trim($line) == "")
            {
              if (isset($this->request_header["Content-Length"]))
                $set_length = $this->request_header["Content-Length"];
                
              if ($set_length == 0)
                $set_length = -1;
                
              $header_done = true;
              
              if ($set_length == -1)
                break;
                
              continue;
            }

            if ($index > 32)
              return;
              
            $field = explode(": ", trim($line), 2);
            
            $key = $field[0];
            $val = $field[1];
            
            $this->request_header[$key] = $val;
            
            if ($key == "Host")
            {
              $server = explode(":", trim($val), 2);

              $this->server_name = $server[0];
              $this->server_port = ((isset($server[1])) ? $server[1] : 888);

              $host = explode(".", $this->server_name, 3);
              
              if ((substr($host[0], 0, 2) == "ch") && (isset($host[1])))
              {
                $this->selected_channel = (double)substr($host[0], 2);
                $this->selected_serial = $host[1];
                
                if (isset($host[2]))
                  $this->server_name = $host[2];
                  
                $this->daemon_log("HOST ".implode(".", $host)." by ".$this->remote_addr);
              }
            }
          }
          elseif ($this->request_method == "HEAD")
          {
            $error = 405;
            break;
          }
          elseif ($this->request_method == "POST")
          { 
            if (!isset($this->request_header["Content-Length"]))
            {
              $error = 411;
              break;
            }
            
            if ($this->request_header["Content-Type"] == "application/x-www-form-urlencoded")
            {           
              if (!$this->post)
              {
                $post = explode("&", trim($line));
                $this->post = array();
                
                foreach ($post as $keyval)
                {
                  $field = explode("=", $keyval, 2);
                  
                  if ($key)
                  {
                    $key = $field[0];
                    $val = ((isset($field[1])) ? rawurldecode($field[1]) : "");
                    
                    $this->post[$key] = $val;
                  }
                }              
              }
            }
            else
            {
              $this->data = $line;
            }
                        
            break;
          }
          elseif ($this->request_method == "PUT")
          {
            $this->data = $line;
            break;
          }
          else
          {
            break;
          }
        }
        
        pcntl_alarm(0);
        
        if (self::$exit)
          break;
          
        if ($error)
          $code = $error;
        else
          $code = 500;
        
        if ($error > 0)
          $this->get_error_document($code, $headers, $body);
        else        
          $this->get_application($code, $headers, $body);
          
        if ($this->request_method == "HEAD")
          $body = "";

        if (!$headers)
          $headers = array();
        
        $headers = array_merge($headers, $defaults);
        $headers['Content-Length'] = strlen($body);
        
        $header = "";
        
        foreach ($headers as $k => $v) 
        {
	  $header .= $k.': '.$v."\r\n";
        }
        
        fwrite(self::$conn, implode("\r\n", array(
	  'HTTP/1.1 '.$code,
	  $header,
	  $body
        )));
        
        $this->daemon_log("HTTP/1.1 ".$code." to ".$this->remote_addr);
        
        fclose(self::$conn);        
      }
    }
    
    private function socketserver_close()
    {
      if (self::$conn)
        @fclose(self::$conn);
        
      if ($this->socket_handle)
        @fclose($this->socket_handle);
    }
}
