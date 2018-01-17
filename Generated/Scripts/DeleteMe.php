<?php
/**
 * Created by PhpStorm.
 * User: hfnov
 * Date: 17-Jan-18
 * Time: 22:44
 */

include_once("T1.php");
include_once("T2.php");

echo T1::getByID(18)->jsonSerialize();

echo "<BR>";

echo T2::getByID("v@v.com")->jsonSerialize();

echo "<BR>";

$arr = T2::getMultiple(0);

echo T2::toJSONArray($arr);

$x = json_decode(T1::getByID(18)->jsonSerialize());

echo $x->{'Name'};