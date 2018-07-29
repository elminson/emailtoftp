<?php
namespace Elminson\EmailTopFtp;

require_once __DIR__ . '/vendor/autoload.php';

try {
    $config = [
        'server' => '67.222.134.19',
        'user' => 'emailtoftp@rlist.net',
        'password' => 'KF4zp7IX1a',
        'port' => 993
    ];
    $email = new Emailreader($config);
    $email->getEmails();
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}

?>
