<?php

/**
 * A simple adapter to plug in a timer to measure UserAgentInfo parsing performance.
 * 
 * If you don't want to measure the performance, just leave the methods empty.
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 *
 */
class myUAITimerAdapter
{
  protected static $list = array();
  
  public static function start($name)
  {
    self::$list[$name] = sfTimerManager::getTimer($name);
  }
  
  public static function stop($name)
  {
    self::$list[$name]->addTime();
  }
}

