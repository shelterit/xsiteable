<?php

class xs_action_instance extends \xs\Action\Generic {

    function ___action () {

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        $csv = file ( 'application/datastore/phones.csv' ) ;
        $result = $record = array () ;
        $oldtype = $oldlocation = $oldphone = '' ;

        foreach ( $csv as $num => $line ) {

            $items = explode ( ',', str_replace ( '"', '',  $line ) ) ;
            array_walk ( $items, 'trim_value') ;

            @list ( $location, $station, $firstname, $lastname, $role, $type, $phone, $mob, $fax ) = $items ;

            if ( $location == '' )
                $location = $oldlocation ;

            if ( $type == '' )
                $type = $oldtype ;

            $record = '' ;

            $record .= "<i style='color:blue'>$location</i>" ;

            if ( $station  == '' ) {
                if ( $firstname != '' ) {
                    // Person
                    $record .= ", <span style='color:green'>$firstname $lastname</span>" ;
                    if ( $role != '' )
                        $record .= " ($role)" ;
                } else {
                    // Location
                }
            } else {
                // Station
                $record .= ' , ' . $station ;
            }

            if ( $phone != '' ) {
                if ( strlen ( trim ( $phone ) ) < 8 ) {
                        $pre = substr ( $oldphone, 0, 4 ) ;
                        $record .= ", phone <b style=''>$phone ($pre$phone) ,</b>" ;
                } else {
                        $record .= ", phone <b style=''>$phone</b>" ;
                }
            }

            // $record .= ', phone: <b style="background-color:#dde;">'.$phone.'</b>' ;
            if ( $mob != '' ) $record .= ' mob '.$mob ;
            if ( $fax != '' ) $record .= ' fax '.$fax ;

            $p = array (
                'value' => $num,
                'name' => $record
            ) ;
            $result['items'][] = $p ;

            $oldlocation = $location ;
            $oldtype = $type ;

            // oldphone only updates if it's not an extension
            if ( strlen ( $phone ) > 7 )
                    $oldphone = $phone ;
        }

        echo @json_encode($result);
        die();

    }
}

function trim_value(&$value) {
    $value = trim($value);
}