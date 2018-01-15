<?php

    include_once("T1.php");

    $person = new T1("Nicos", 644.34, 654654, 0);
    T1::create($person);

?>