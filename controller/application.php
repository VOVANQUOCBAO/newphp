<?php

/** Shared bootstrap for every public entry point. */
final class application
{
    public function run(bool $admin = false): void
    {
        if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params([
                'lifetime'=>0, 'path'=>'/',
                'secure'=>!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS'])!=='off',
                'httponly'=>true, 'samesite'=>'Lax',
            ]);
            session_start();
        }
        require_once BASE_PATH.'/view/helpers.php';
        if ($admin || ($_GET['khu'] ?? '')==='admin') {
            require_once __DIR__.'/admin/request_controller.php';
            (new admin_request_controller())->run();
        } else {
            require_once __DIR__.'/storefront_controller.php';
            (new storefront_controller())->run();
        }
    }

    public function root(): void
    {
        if (($_GET['khu'] ?? '')==='admin' || ($_SERVER['REQUEST_METHOD'] ?? '')==='POST') {
            $this->run();
            return;
        }
        $query=$_GET;
        unset($query['khu']);
        $target='controller/index.php'.($query?'?'.http_build_query($query):(!empty($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:''));
        header('Location: '.$target);
    }
}
