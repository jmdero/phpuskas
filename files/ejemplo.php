
<?php
  
$languages = array();
  
$languages['Python'] = array(
    "first_release" => "1991", 
    "latest_release" => "3.8.0", 
    "designed_by" => "Guido van Rossum",
    "description" => array(
        "extension" => ".py", 
        "typing_discipline" => "Duck, dynamic, gradual",
        "license" => "Python Software Foundation License"
    )
);
  
$languages['PHP'] = array(
    "first_release" => "1995", 
    "latest_release" => "7.3.11", 
    "designed_by" => "Rasmus Lerdorf",
    "description" => array(
        "extension" => ".php", 
        "typing_discipline" => "Dynamic, weak",
        "license" => "PHP License (most of Zend engine
             under Zend Engine License)"
    )
);
  
print_r($languages);
  
?>