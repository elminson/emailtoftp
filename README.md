# emailtoftp
 Upload files to ftp using email

Server need lib imap_open enable 
##Installation

Untill Big o’ 2.0 is ready (ssilence/php-imap-client), use the following command to install PHP-imap-library:
``` 
composer require ssilence/php-imap-client dev-master
```
After install <b>ssilence/php-imap-client dev-master</b>
```sql
composer create-project elminson/emailtoftp
```
or
```sql
composer require elminson/emailtoftp
```
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

#Dependencies
```php
"ssilence/php-imap-client": "dev-master",
"nicolab/php-ftp-client": "^1.4"
