# emailtoftp
 Upload files to ftp using email

Server need lib imap_open enable 

## Configuration
```php
 $config = [
        'validCredential' => 'valid@gmail.net',
        'smtp' =>
            [
                'server' => 'mail.gmail.net',
                'user' => 'emailtoftp@gmail.net',
                'password' => 'securepassword'
            ],
        'ftp' =>
            [
                'upload_folder' => 'aqui/',
                'host' => 'ftpdomain.net',
                'userftp' => 'ftpuser',
                'passwordftp' => 'ftppasswrod'
            ]

    ]; 
```

##Usage
```php
$email = new Emailreader($config);
echo "Total Files Uploaded : ".$email->total_files;
```