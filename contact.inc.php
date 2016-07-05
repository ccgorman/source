<h2>Contact</h2>
<p>If you would like to contact <?php PRINT($site["title"]); ?> please email <?php

$email="mail@".preg_replace("/www\./","",$site["url"]);
PRINT("<a href=\"mailto:".$email."\">".$email."</a>");

?>. We hope to respond to all queries within one to two working days.</p>