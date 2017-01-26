<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-upload-status module provides an interface to
 *  the ingester status reporting backend, allowing users to view
 *  the current state of any uploads they may have performed, as
 *  well as enabling the download and retrieval of that data.
 *
 *  This file contains a number of common functions related to
 *  file info and handling.
 *
 * PHP version 5.5
 *
 * @package Pacifica-upload-status
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 *
 * @link http://github.com/EMSL-MSC/Pacifica-reporting
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  Formats a time as a loose human readable approximation
 *  for display purposes ('a few minutes ago', 'about a month ago')
 *
 *  @param datetime $datetime_object the object to format
 *  @param datetime $base_time_obj   the time to which to
 *                                   compare the main datetime
 *                                   object
 *  @param boolean  $use_ago         should we include the word
 *                                   ago in the returned value?
 *
 *  @return string
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function friendlyElapsedTime($datetime_object, $base_time_obj = FALSE, $use_ago = TRUE)
{
    if(!$base_time_obj) {
        $base_time_obj = new DateTime();
    }
    //convert to time object if string
    if(is_string($datetime_object)) { $datetime_object = new DateTime($time);
    }

    $nowTime = $base_time_obj;

    $diff = $nowTime->getTimestamp() - $datetime_object->getTimestamp();

    $result = "";

    //calc and subtract years
    $years = floor($diff/60/60/24/365);
    if($years > 0) { $diff -= $years*60*60*24*365;
    }

    //calc and subtract months
    $months = floor($diff/60/60/24/30);
    if($months > 0) { $diff -= $months*60*60*24*30;
    }

    //calc and subtract weeks
    $weeks = floor($diff/60/60/24/7);
    if($weeks > 0) { $diff -= $weeks*60*60*24*7;
    }

    //calc and subtract days
    $days = floor($diff/60/60/24);
    if($days > 0) { $diff -= $days*60*60*24;
    }

    //calc and subtract hours
    $hours = floor($diff/60/60);
    if($hours >0) { $diff -= $hours*60*60;
    }

    //calc and subtract minutes
    $min = floor($diff/60);
    if($min > 0) { $diff -= $min*60;
    }

    $qualifier = "about";



    if($years > 0) {
        $unit = $years > 1 ? "years" : "year";
        $result[] = "{$years} {$unit}";
    }
    if($months > 0) {
        $unit = $months > 1 ? "months" : "month";
        $result[] = "{$months} {$unit}";
    }
    if($weeks > 0) {
        $unit = $weeks > 1 ? "weeks" : "week";
        $result[] = "{$weeks} {$unit}";
    }
    if($days > 0) {
        $unit = $days > 1 ? "days" : "day";
        $result[] = "{$days} {$unit}";
    }
    if($hours > 0) {
        $unit = $hours > 1 ? "hrs" : "hr";
        $result[] = "{$hours} {$unit}";
    }
    if($min > 0) {
        $unit = $min > 1 ? "min" : "min";
        $result[] = "{$min} {$unit}";
    }
    if($diff > 0) {
        $unit = $diff > 1 ? "sec" : "sec";
        if(empty($result)) {
            $result[] = "{$diff} {$unit}";
        }
    }else{
        $result[] = "0 seconds";
    }
    $ago = $use_ago ? " ago" : "";
    //format string
    $result_string = sizeof($result) > 1 ? "~".array_shift($result)." ".array_shift($result)."{$ago}" : "~".array_shift($result)."{$ago}";
    return $result_string;
}

/**
 *  Generate an appropriate HTML5 time object containing
 *  a nicely formatted time string in the display area,
 *  and an ISO-formatted string in the datetime object
 *
 *  @param datetime $time_obj object to be formatted
 *
 *  @return string
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function format_cart_display_time_element($time_obj)
{
    $elapsed_time = friendlyElapsedTime($time_obj);
    $formatted_time = $time_obj->format('d M Y g:ia');
    $iso_time = $time_obj->getTimestamp();

    return "<time title='{$formatted_time}' datetime='{$iso_time}'>{$elapsed_time}</time>";

}

/**
 * Convert local time to UTC for backend storage
 *
 * @param string $time          a strtotime parseable datetime string
 * @param string $string_format Output format for the new timestring
 *
 * @return string new timestring in UTC
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function local_time_to_utc($time, $string_format=FALSE)
{
    $tz_utc = new DateTimeZone('UTC');
    if(is_string($time) && strtotime($time)) {
        $time = new Datetime($time);
    }
    if(is_a($time, 'DateTime')) {
        $time->setTimeZone($tz_utc);
    }
    if($string_format) {
        $time = $time->format($string_format);
    }
    return $time;
}

/**
 * Convert UTC to local time for end user display
 *
 * @param string $time          a strtotime parseable datetime string
 * @param string $string_format Output format for the new timestring
 *
 * @return string new timestring in local timezone time
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function utc_to_local_time($time, $string_format=FALSE)
{
    $CI =& get_instance();
    $tz_local = new DateTimeZone($CI->config->item('local_timezone'));
    $tz_utc = new DateTimeZone('UTC');
    if(is_string($time) && strtotime($time)) {
        $time = new Datetime($time, $tz_utc);
    }
    if(is_a($time, 'DateTime')) {
        $time->setTimeZone($tz_local);
    }
    if($string_format) {
        $time = $time->format($string_format);
    }
    return $time;
}

?>
