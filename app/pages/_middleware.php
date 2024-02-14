
<?php

// Yes, you can use middlewares in pages!
function _global_init($_): void
{
  session_start();
}
