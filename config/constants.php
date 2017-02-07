<?php

// https://laracasts.com/discuss/channels/laravel/l51-how-i-can-define-my-own-constants

/*

From Evan's email

Type:1 

addr: 1  => coil values for heating and cooling.

Type:3

addr: 40002 => Thermostat mode, could be  off, heat, cool, auto

addr: 40003 =>  Fan mode.  could be auto or on 

addr: 40011 => Day cool setting temperature. 

addr: 40012 =>  Day heat setting temperature

addr: 40013 => Night cool setting temperature

addr: 40014 => Night heat setting temperature

addr: 40015 => Single setting temperature, manual mode. 

addr: 40054 => day/night mode change

addr: 40354 => Celsius degree of current temperature

addr: 40355 => Fahrenheit degree of current temperature


*/

return [
  "wifi_thermo" => [
    "str" => "smt_770", //
    "heat_or_cool_str" => "heat_or_cool", // 1
    "thermo_mode_str" => "thermo_mode", // 40002
    "fan_mode_str" => "fan_mode", // 40003
    
    "day_cool_set_temp_str" => "day_cool_set_temp", // 40011
    "day_heat_set_temp_str" => "day_heat_set_temp", // 40012
    "night_cool_set_temp_str" => "night_cool_set_temp", // 40013
    "night_heat_set_temp_str" => "night_heat_set_temp", // 40014

    "single_temp_set_manual_str" => "single_temp_set_manual", // 40015
    "day_night_mode_str" => "day_night_mode", // 40054    
    "curr_temp_celsius_str" => "curr_temp_celsius", // 40354
    "curr_temp_fahrenheit_str" => "curr_temp_fahrenheit", // 40355

    
    "unknown" => "unknown_contact_gary" // unknown    
  ]
];



