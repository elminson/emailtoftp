<?php

namespace Elminson\EmailTopFtp;

require_once __DIR__ . '/vendor/autoload.php';

try {
    $config = [
        'validCredential' => 'validemail@gmail.com', //Valid Email to upload
        'smtp' =>
            [
                'server' => 'mail.gmail.net', //mail server
                'user' => 'emailtoftp@gmail.net', //mail user
                'password' => 'Securepassword'//mail passowrd
            ],
        'ftp' =>
            [
                'upload_folder' => 'destination/', //destination folder
                'host' => 'gmail.net', //ftp server
                'userftp' => 'ftouser',
                'passwordftp' => 'secondsecurepassword'
            ]

    ];

    $email = new Emailreader($config);
    echo "Total Files Uploaded : " . $email->total_files;

} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
