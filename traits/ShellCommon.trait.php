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

trait ShellCommon
{
    private $log_handle = null;
    
    private $bunkerschild = null;
    private $program_name = null;
    
    private $arguments = null;
    private $execute = null;
    
    private $methods = null;
    private $signals = null;
    
    private $tick_handler = null;
    private $tick_init = false;
    
    private $pid = null;
    private $pid_file = null;
    private $pid_written = false;
            
    function __construct(\BunkerschildFramework\Bunkerschild $__BUNKERSCHILD, $child_classes = null, $child_classes_exec = null)
    {
      global $argv;
      
      if (isset($this->child_classes))
        $this->child_classes = $child_classes;
      
      if (isset($this->child_classes_exec))
        $this->child_classes_exec = $child_classes_exec;
      
      $this->bunkerschild = $__BUNKERSCHILD;
      $this->arguments = new \stdClass;
      
      if (!isset($argv))
      {
        $this->program_name = $this->bunkerschild->get_program_name();
      }
      else
      {
        $this->program_name = basename($argv[0]);
      }
      
      $this->pid = getmypid();      
    }
    
    private function help()
    {
      $this->version(true);
      $this->echo("\nAvailable switches:\n");
      $this->echo(" --help                           This help context\n");
      $this->echo(" --version                        Display version information\n");
      
      foreach ($this->methods as $key => $obj)
      {
        if ($obj->hide_in_help_context)
          continue;
          
        $description = "Undocumented function";
        
        if ($obj->description)
          $description = $obj->description;
                            
        $this->echo(" ".str_pad($key, 32, " ", STR_PAD_RIGHT)." ".$description."\n");
      }
      
      $this->echo("\nThis software is distributed as it is under the ".$this->bunkerschild->get_license()."\n");
      $this->echo("Copyright ".$this->bunkerschild->get_copyright()."\n");
      exit(1);
    }

    private function check_pid()
    {
      if (!$this->pid_file)
        return false;
        
      if (file_exists($this->pid_file))
      {
        $pid = implode("", file($this->pid_file));
      
        if ($pid != $this->pid)
        {
          if (!file_exists(DS."proc".DS.$pid.DS."cmdline"))
          {
            $this->remove_pid();
            return false;
          }
          
          return $pid;
        }
      }
      
      return false;
    }
    
    private function write_pid()
    {
      if ($this->pid_written)
        return false;
        
      $fd = @fopen($this->pid_file, "w");
      @fwrite($fd, $this->pid);
      @fclose($fd);
      
      $this->pid_written = true;
      
      return true;
    }
    
    private function remove_pid()
    {
      if ($this->pid_written)
        @unlink($this->pid_file);
        
      $this->pid_written = false;
    }
    
    private function echo($str, $stderr = false, $binary = false)
    {
      $fd = @fopen("php://std".(($stderr) ? "err" : "out"), "w".(($binary) ? "+" : ""));
      
      if (!is_resource($fd))
      {
        throw new \exception("Unable to access std".(($stderr) ? "err" : "out"));
        return;
      }
      
      @fwrite($fd, $str);
      @fclose($fd);
    }
    
    private function usage()
    {
      $this->echo("Usage: ".$this->program_name." --help\n", true);
      exit(1);
    }
    
    private function version($no_exit = false)
    {
      $this->echo($this->bunkerschild->get_program_name()." v".$this->bunkerschild->get_version()." (build: ".$this->bunkerschild->get_version_timestamp().")\n");
      
      if (!$no_exit)
        exit;    
    }
    
    public function override_program_name($progname)
    {
      $this->program_name = $progname;
    }
    
    public function has_argument($arg)
    {
      if (isset($this->arguments->$arg))
        return true;
        
      return false;
    }
    
    public function get_argument($arg)
    {
      if ($this->has_argument($arg))
        return $this->arguments->$arg;
        
      return null;
    }
    
    public function register_signal_defaults()
    {
       $this->register_signal(SIGFPE);
       $this->register_signal(SIGHUP);
       $this->register_signal(SIGINT);
       $this->register_signal(SIGQUIT);
       $this->register_signal(SIGILL);
       $this->register_signal(SIGTRAP);
       $this->register_signal(SIGABRT);
       $this->register_signal(SIGIOT);
       $this->register_signal(SIGBUS);
       $this->register_signal(SIGPOLL);
       $this->register_signal(SIGSYS);
       $this->register_signal(SIGCONT);
       $this->register_signal(SIGUSR1);
       $this->register_signal(SIGUSR2);
       $this->register_signal(SIGSEGV);
       $this->register_signal(SIGPIPE);
       $this->register_signal(SIGALRM);
       $this->register_signal(SIGTERM);
       $this->register_signal(SIGSTKFLT);
       $this->register_signal(SIGCHLD);
       $this->register_signal(SIGIO);
       $this->register_signal(SIGTSTP);
       $this->register_signal(SIGTTIN);
       $this->register_signal(SIGTTOU);
       $this->register_signal(SIGURG);
       $this->register_signal(SIGXCPU);
       $this->register_signal(SIGXFSZ);
       $this->register_signal(SIGVTALRM);
       $this->register_signal(SIGPROF);
       $this->register_signal(SIGWINCH);
       $this->register_signal(SIGPWR);            
    }
    
    public function register_method($method, $description = null, $hide_in_help_context = false)
    {
      $key = strtolower("--".str_replace("_", "-", $method));
      
      $this->methods[$key] = new \stdClass;
      $this->methods[$key]->method = $method;
      $this->methods[$key]->description = $description;
      $this->methods[$key]->hide_in_help_context = $hide_in_help_context;
    }
    
    public function register_signal($signal, $callback = false)
    {
      $this->signals[$signal] = $callback;
    }
    
    public function register_tick_handler($tick_handler = false)
    {
      if (!$this->tick_init)
        $this->tick_handler = $tick_handler;
    }
    
    public function register_args()
    {
      global $argv, $argc;
     
      if ($argc < 2)
        return $this->usage();
     
      for ($i = 1; $i < $argc; $i++)
      {
        switch ($argv[$i])
        {
          case "--usage":
            $this->usage();
            break;
          case "--version":
            $this->version();
            break;
          case "--help":
            $this->help();
            break;
          default:
            if (substr($argv[$i], 0, 2) == "--")
            {	
              if (isset($this->methods[$argv[$i]]))
              {
                $this->execute = $this->methods[$argv[$i]]->method;
              }
              else
              {
                $key = strtolower(str_replace("-", "_", substr($argv[$i], 2)));
                $next = ($i + 1);
                $val = null;
                
                if ((isset($argv[$next])) && (substr($argv[$next], 0, 2) != "--"))
                {
                  $val = $argv[$next];
                  $i++;
                }
              
                $this->arguments->$key = ((!$val) ? true : $val);
              }
            }
            break;
        }    
      }
    }
    
    public function register_pid_file()
    {
      $this->pid_file = PATH_RUN.DS.$this->program_name.".pid";
    }
    
    public function execute()
    {
      cli_set_process_title($this->program_name);
      
      if ($pid = $this->check_pid())
      {
        $this->echo("FATAL: Another process is running at PID #".$pid."\n", true);
        exit(255);
      }      
      elseif ($this->execute == "")
      {
        $this->echo("Missing command to execute\n");
        $this->usage();
      }
      elseif (method_exists($this, $this->execute))
      {
        $this->write_pid();
        $execute = $this->execute;
        $this->$execute();
      }
      else
      {
        $this->echo("Method not found: --".str_replace("_", "-", $this->execute)."\n", true);
        $this->usage();
      }
    }
    
    public static function noop()
    {
      return;
    }
    
    public function get_signal($signal)
    {
      switch ($signal)
      {
        case SIGFPE:    return 'SIGFPE';
        case SIGSTOP:   return 'SIGSTOP';
        case SIGHUP:    return 'SIGHUP';
        case SIGINT:    return 'SIGINT';
        case SIGQUIT:   return 'SIGQUIT';
        case SIGILL:    return 'SIGILL';
        case SIGTRAP:   return 'SIGTRAP';
        case SIGABRT:   return 'SIGABRT';
        case SIGIOT:    return 'SIGIOT';
        case SIGBUS:    return 'SIGBUS';
        case SIGPOLL:   return 'SIGPOLL';
        case SIGSYS:    return 'SIGSYS';
        case SIGCONT:   return 'SIGCONT';
        case SIGUSR1:   return 'SIGUSR1';
        case SIGUSR2:   return 'SIGUSR2';
        case SIGSEGV:   return 'SIGSEGV';
        case SIGPIPE:   return 'SIGPIPE';
        case SIGALRM:   return 'SIGALRM';
        case SIGTERM:   return 'SIGTERM';
        case SIGSTKFLT: return 'SIGSTKFLT';
        case SIGCHLD:   return 'SIGCHLD';
        case SIGIO:     return 'SIGIO';
        case SIGKILL:   return 'SIGKILL';
        case SIGTSTP:   return 'SIGTSTP';
        case SIGTTIN:   return 'SIGTTIN';
        case SIGTTOU:   return 'SIGTTOU';
        case SIGURG:    return 'SIGURG';
        case SIGXCPU:   return 'SIGXCPU';
        case SIGXFSZ:   return 'SIGXFSZ';
        case SIGVTALRM: return 'SIGVTALRM';
        case SIGPROF:   return 'SIGPROF';
        case SIGWINCH:  return 'SIGWINCH';
        case SIGPWR:    return 'SIGPWR';      
      }
      
      return -1;
    }
    
    public function trap_signals()
    {
      foreach ($this->signals as $signal => $callback)
      {
        pcntl_signal($signal, (($callback === false) ? array("\\".get_class($this), "noop") : $callback));
      }
    }
    
    public function enable_async_signals()
    {
      pcntl_async_signals(true);
    }
    
    public function disable_async_signals()
    {
      pcntl_async_signals(false);
    }
    
    public function dispatch_signals()
    {
      pcntl_signal_dispatch();
    }
    
    public function ticks_init()
    {
      if (!$this->tick_init)
        register_tick_function(array(&$this, (($this->tick_handler) ? $this->tick_handler : "\\".get_class($this)."::noop")));
    }
    
    public function daemon_log_open($option = LOG_PID, $facility = LOG_SYSLOG)
    {
      if (!$this->log_handle)
        $this->log_handle = openlog("Bunkerschild-".strtoupper($this->program_name), $option, $facility);
    }
    
    public function daemon_log_close()
    {
      if ($this->log_handle)
        closelog();
    }
    
    public function daemon_log($str, $priority = LOG_INFO)
    {
      if ($this->log_handle)
        syslog($priority, $str);
      else
        $this->echo($str."\n", true);
    }
    
    public function posix_root()
    {
      posix_setuid(0);
      posix_seteuid(0);
      
      posix_setgid(0);
      posix_setegid(0);
      
      $uid = posix_getuid();
      $euid = posix_geteuid();
      
      if ($uid != 0)
      {
        $this->daemon_log("Process owners UID has to be 0");
        exit(240);
      }
    }
    
    public function posix_defaults()
    {
      global $__CONFIG;

      $user = posix_getpwnam($__CONFIG->posix_username);
      $group = posix_getgrnam($__CONFIG->posix_group);
      
      if ((!$user) || (!$group))
      {
        $this->daemon_log("User or group missing");
        exit(239);
      }
      
      posix_setuid($user["uid"]);
      posix_seteuid($user["uid"]);
      
      posix_setgid($group["gid"]);
      posix_setegid($group["gid"]);
      
      $uid = posix_getuid();
      $euid = posix_geteuid();
      
      if ($uid != $user["uid"])
      {
        $this->daemon_log("Process owners UID has to be ".$user["uid"]);
        exit(240);
      }
    }
    
    function __destruct()
    {
      if ($this->tick_init)
        unregister_tick_function(array(&$this, (($this->tick_handler) ? $this->tick_handler : "\\".get_class($this)."::noop")));
        
      $this->remove_pid();
      $this->daemon_log_close();
    }
}
