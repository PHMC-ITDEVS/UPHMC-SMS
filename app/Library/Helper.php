<?php

namespace App\Library;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Foundation\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;

use Cookie;

use App\Models\User;

class Helper
{
    private static $encodedKey = 'CVBcJH3YUIoZXyHJKYReERcaHxQWDFTYIxcRTgKJHgA=';
    private static $encodedIV = '3FUYKMefgiCzHpqW2OwoZA==';
    private static $encrypter = 'aes-256-cbc';

    public static function auth_role_dsp()
    {
        return (Auth::user()->role_name==config("constants.roles.dsp")) ? true : false;
    }

    public static function auth_role_rdo()
    {
        return (Auth::user()->role_name==config("constants.roles.rdo")) ? true : false;
    }

    public static function auth_role_seller()
    {
        return (Auth::user()->role_name==config("constants.roles.seller")) ? true : false;
    }

    public static function auth_role_admin()
    {
        return (Auth::user()->role_name==config("constants.roles.admin")) ? true : false;
    }

    public static function auth_role_bir()
    {
        return (Auth::user()->role_name==config("constants.roles.bir")) ? true : false;
    }

    public static function auth_dsp_list($type = "")
    {   
        if ($type!="")
            return Auth::user()->account->serviceProvider->pluck($type);
        else
            return Auth::user()->account->serviceProvider;
    }

    public static function auth_region_list()
    {   
        return Auth::user()->account->region->pluck("region_code");
    }

    

    public static function ref_number($prefix,$length,$delimeter="")
    {
        $prefix = $prefix.date("ymd").$delimeter;
        $char_length = $length - strlen($prefix);
        return strtoupper($prefix.gen_random_string($char_length));
    }

    public static function useRedis($channel, $payload = array(), $reference = '', $action = '')
	{
        try
        {
            $redis = Redis::connection();
            $redis->publish($channel, json_encode($payload));

            $data = array(
                'channel' => $channel,
                'payload' => $payload
            );

            Log::info("[$reference][$action][REDIS_SUBMIT] :". json_encode($data));
        }
        catch(Exception $ex)
        {
            Log::error("[$reference][$action][REDIS_ERROR] :". $ex->getMessage());
        }
	}

    public static function generateRandomString($length = 10) 
	{
	    $characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    $characters_length = strlen($characters);
	    $random_string = "";

	    for ($i = 0; $i < $length; $i++)
        {
	        $random_string .= $characters[rand(0, $characters_length - 1)];
	    }

	    return $random_string;
	}

    public static function logTransaction($filename = "", $function_name = "", $referrence = "", $log = "", $type = 1) 
    {
        $authenticator = "";
        
        if(Auth::check())
        {
            $auth_user = Auth::user();
            $auth_account = $auth_user->account;

            $authenticator = "[$auth_account->id:$auth_user->username]";
        }
        
        $log_message = "[$filename][$function_name][$referrence]$authenticator: $log";

        if($type)
        {
            Log::info($log_message);
        }
        else
        {
            Log::error($log_message);
        }
    }

    public static function encrypt($args)
    {
        $key = base64_decode(self::$encodedKey);
        $iv = base64_decode(self::$encodedIV);
        $encrypted = openssl_encrypt($args, self::$encrypter, $key, 0, $iv);
        $encrypted = str_replace("/", "-", $encrypted);

        return $encrypted;
    }

    public static function decrypt($args)
    {
        $key = base64_decode(self::$encodedKey);
        $iv = base64_decode(self::$encodedIV);
        $args = str_replace("-", "/", $args);
      	$decrypted = openssl_decrypt($args, self::$encrypter, $key, 0, $iv);

      	return $decrypted;
    }
}