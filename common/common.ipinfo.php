<?php
/**
 *
 * IP SPIDER
 *
 * Created by PhpStorm.
 * User: Hi Yen Wong
 * Date: 2018/4/24
 * Time: 1:54 PM
 */

class IpInfo
{
    static $fields = IP_FIELDS;     // refer to http://ip-api.com/docs/api:returned_values#field_generator
    static $use_xcache = true;  // set this to false unless you have XCache installed (http://xcache.lighttpd.net/)
    static $api = IP_API;

    public $status, $country, $countryCode, $region, $regionName, $city, $zip,
        $lat, $lon, $timezone, $isp, $org, $as, $reverse, $query, $message;

    /**
     * query func
     *
     * @param $q
     * @return static
     */
    public static function query($q)
    {
        $data = self::__communicate($q);
        $result = new static;
        foreach ($data as $key => $val) {
            $result->$key = $val;
        }
        return $result;
    }

    /**
     * communicate func
     *
     * @param $q
     * @return mixed
     */
    private function __communicate($q)
    {
        if (is_callable('curl_init')) {

            try {
                $c = curl_init();
                curl_setopt($c, CURLOPT_URL, self::$api . $q . '?fields=' . self::$fields);
                curl_setopt($c, CURLOPT_HEADER, false);
                curl_setopt($c, CURLOPT_TIMEOUT, 30);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                $result_array = unserialize(curl_exec($c));
                curl_close($c);
            } catch (Exception $e) {
                return null;
            }

        } else {
            try {
                $result_array = unserialize(file_get_contents(self::$api . $q . '?fields=' . self::$fields));
            } catch (Exception $e) {
                return null;
            }
        }

        return $result_array;
    }

}