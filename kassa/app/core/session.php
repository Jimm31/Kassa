<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 16.01.2016
 * Time: 15:23
 */
namespace core;
class Session
{
    public static function start()
    {
        if (session_id())
            return true;
        else
            session_start();
    }

    public static function destroy()
    {
        if (session_id()) {
            setcookie(session_name(), session_id(), time() - 60 * 60 * 24);
            session_unset();
            session_destroy();
        }
    }

    public static function get($key)
    {
        return $_SESSION[$key];
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }
}
