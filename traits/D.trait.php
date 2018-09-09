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

trait D
{
    public static $exit = false;
    public static $hup = false;
    
    private $child_pids = null;
    private $child_classes = "";
    private $child_classes_exec = "";
        
    public function register_available_methods()
    {
      $this->register_method("daemon", "Start the Bunkerschild master daemon");
    }

    public function register_available_signals()
    {
      $this->register_signal(SIGTERM, array("\\".get_class($this), "daemon_exit"));          
      $this->register_signal(SIGHUP, array("\\".get_class($this), "daemon_hup"));
    }
    
    public static function daemon_exit()
    {
      self::$exit = true;
    }
    
    public static function daemon_hup()
    {
      if (self::$exit)
        return;
      
      self::$hup = true;
    }
    
    private function terminate_childs()
    {
      if (is_object($this->child_pids))
      {
        foreach ($this->child_pids as $procname => $ppid)
        {
          $this->daemon_log("Terminating child process ".$procname." at PID #".$ppid);
          
          $terminated = false;
          
          for ($i = 5; $i > 0; $i--)
          {
            posix_kill($ppid, SIGTERM);
            sleep(1);
            
            if (!file_exists("/proc/".$ppid))
            {
              $terminated = true;
              break;
            }
          }
          
          if (!$terminated)
          {
            posix_kill($ppid, SIGKILL);
            $this->daemon_log("Child process ".$procname." not terminating after 5 seconds. Killed it!");
          }
          else
          {
            $this->daemon_log("Child process ".$procname." succesfully terminated.");
          }
          
          pcntl_waitpid($ppid, $status);
          unset($this->child_pids->$child_name);
        }
      }
    }
    
    private function daemon()
    {
      $this->child_pids = new \stdClass;
      
      $child_classes = $this->child_classes;
      $child_classes_exec = $this->child_classes_exec;
      
      $npid = pcntl_fork();

      if ($npid == -1)
      {
        $d->echo("Unable to fork\n", true);
        exit(1);
      }
      elseif ($npid)
      {
        // Detach from terminal and get reaped by init
        exit(0);
      }
      else
      {
        // (Re-)Spawn childs
        
        while (!self::$exit)
        {
          if (self::$hup)
          {
            self::$hup = false;

            $this->daemon_log("Received HUP signal - initiate child respawning");
            $this->terminate_childs();
          }
          
          foreach ($child_classes as $child_class => $child_name)
          {
            $child_alive = false;
            
            if (isset($this->child_pids->$child_name))
            {
              if (file_exists("/proc/".$this->child_pids->$child_name))
              {	
                $child_alive = true;
              }
              else
              {
                unset($this->child_pids->$child_name);
              }
            }
            
            if (!$child_alive)
            {
                $pid = pcntl_fork();
                
                if ($pid == -1)
                {
                  $this->daemon_log("Unable to fork child ".$child_class);
                  exit(2);
                }
                elseif ($pid)
                {
                  $this->daemon_log("Child process ".$child_name." spawned at PID #".$pid);
                  $this->child_pids->$child_name = $pid;                        
                }

                if (!$pid)
                {
                  global $child_name, $$child_name;
                  $$child_name = new $child_class($this->bunkerschild);
                        
                  $$child_name->override_program_name($child_name);

                  foreach ($child_classes_exec as $func)
                    $$child_name->$func();

                  $$child_name->execute();
                  
                  exit;
                }
            }
          }

          sleep(1);
        }
        
        $this->daemon_log("Daemon shutdown initiated - waiting for childs to terminate");
        $this->terminate_childs();
        
        $this->daemon_log("All child processes exitted. Daemon suicide!");
        exit;
      }
    }    
}
