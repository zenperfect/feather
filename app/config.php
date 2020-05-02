<?php

return [

    'bot.pattern'           => '/(bot|spider|crawl|scanner|scan|qwant|grab)/i',
    'unusual.agent.pattern' => '/(python|perl|urllib|curl|^\-$|zgrab|postman)/i',
    'resource.pattern'      => '/(.+)(\.\w{1,3}|woff|jpeg|woff2)$/i'

];