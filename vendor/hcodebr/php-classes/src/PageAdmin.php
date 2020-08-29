<?php

namespace Hcode;

class PageAdmin extends Page
{
    public function __construct($assigns = array(), $tpl_dir = '/views/admin/')
    {
        parent::__construct($assigns, $tpl_dir);
    }
}
