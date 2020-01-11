<?php

/**
 * ������������� ������ ��� �������.
 * 
 * - ����� ������������� ������������ ���
 * - �������� ����� � �������� ������ ���������
 * - �� ����������������
 * 
 * �������� �������:
 * ReCaptcha\
 *      ReCaptcha.php
 * autoload.php
 */
spl_autoload_register(function($class)
{
    $namespace = 'ReCaptcha\\';

    if (substr($class, 0, strlen($namespace)) !== $namespace) return;

    $class = str_replace('\\', '/', $class);

    $path = __DIR__ .'/'. $class .'.php';
    if (is_readable($path)) require_once $path;
});

require_once __DIR__ .'/GoogleCaptcha.php';