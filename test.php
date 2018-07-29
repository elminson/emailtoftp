<?php
namespace Elminson\EmailTopFtp;

require_once __DIR__ . '/vendor/autoload.php';

try {
    $config = [
        'server' => 'domain.net',
        'user' => 'emailtoftp@domain.net',
        'password' => '',
        'port' => 993
    ];
    $email = new Emailreader($config);
    $email->getEmails();
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}

?>
