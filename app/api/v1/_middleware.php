<?php

use Phlo\Core\Context;


function _global_init(Context &$ctx): void
{
	$ctx->set("date", date("Y-m-d H:i:s"));
}
