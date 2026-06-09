<?php
/**
 * Remote update check stub
 * All updates disabled
 */

function zib_theme_update_check()
{
    return false;
}

function zib_get_remote_version()
{
    return false;
}

function zib_theme_update_notice()
{
    return '';
}

function zib_check_remote_update()
{
    return false;
}
