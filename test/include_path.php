<?php

$include_path = get_include_path();
print_r($include_path);
print_r("<br/>");
set_include_path("/Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_wms/");
$include_path = get_include_path();
print_r($include_path);

