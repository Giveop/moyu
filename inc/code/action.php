<?php
/**
 * AJAX 授权处理存根
 * curl_aut、delete_aut 等全部返回成功
 */

function zib_admin_aut_add_action()
{
    // 已授权，无需操作
}

function zib_admin_curl_aut()
{
    header('Content-Type: application/json');
    echo json_encode(array(
        'error' => false,
        'msg'   => '授权成功',
        'data'  => array('status' => 'active'),
    ));
    exit();
}

function zib_admin_delete_aut()
{
    header('Content-Type: application/json');
    echo json_encode(array(
        'error' => false,
        'msg'   => '操作成功',
    ));
    exit();
}

function zib_is_local()
{
    return false;
}