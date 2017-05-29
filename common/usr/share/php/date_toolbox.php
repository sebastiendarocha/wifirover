<?php


/** Check that the string is format 'HH:mm' in 24H */
function isHour( $var)
{
    return preg_match('/^([0-1]{0,1}[0-9]{1}|[2]{1}[0-3]{1}):[0-5]{1}[0-9]{1}$/', $var);
}


function hourToInt( $var) {
    $result = -1;
    if( isHour( $var))
    {
        $vals = explode( ":", $var);
        $result = 60 * $vals[0] + $vals[1]; 
    }
    return $result;
}

function isOpen( $date_time, $start_time, $stop_time, $is_int=false) {
    if( $is_int == false)
    {
        $start = hourToInt($start_time); 
        $stop = hourToInt($stop_time); 
        $date = hourToInt($date_time); 
    }
    else
    {
        $start = ($start_time); 
        $stop = ($stop_time); 
        $date = ($date_time); 
    }

    /*echo "$start_time: $stop_time => ".
        "$start <= $date : ".($start <= $date? "true":"false")." ; ".
        "$date <= $stop ;".($date <= $stop? "true":"false"). "\n";*/
    $result = false;
    if( $start < $stop) {
        if( $start <= $date && $date <= $stop) {
            $result = true;
        }
    }
    else
    {
        if( $start <= $date || $date <= $stop) {
            $result = true;
        }

    }
    return $result;
}

function getHour()
{
    $out = array();
    exec( 'date +"%H:%M"', $out);
    return $out[0];
}
