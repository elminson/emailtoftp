<?php

namespace Elminson\EmailTopFtp;

use FtpClient\FtpClient;
use phpseclib\Net\SFTP;
use SSilence\ImapClient\ImapClientException;
use SSilence\ImapClient\ImapConnect;
use SSilence\ImapClient\ImapClient as Imap;

class EmailReader
{

    // imap server connection
    public $conn;

    // inbox storage and inbox message count
    private $inbox;
    private $msg_cnt;

    // email login credentials
    private $server = null;
    private $user = null;
    private $password = null;
    private $encryption = null;
    private $validCredential = null;
    private $upload_folder = null;
    private $port = 143; // adjust according to server settings
    private $ftp = null; // adjust according to server settings
    private $host = null; // adjust according to server settings
    private $userftp = null; // adjust according to server settings
    private $passwordftp = null; // adjust according to server settings
    public $total_files = 0;

    // connect to the server and get the inbox emails

    /**
     * EmailReader constructor.
     * @param array $config
     * @throws \Exception
     */
    function __construct($config = [])
    {
        $this->encryption = ImapConnect::ENCRYPT_SSL;
        $this->setup($config);
        $this->connect();
        $this->readUploadFiles();
    }

    /**
     * @param array $config
     * @throws \Exception
     */
    function setup($config = [])
    {
        $this->server = (isset($config['smtp']['server'])) ? $config['smtp']['server'] : null;
        $this->user = (isset($config['smtp']['user'])) ? $config['smtp']['user'] : null;
        $this->password = (isset($config['smtp']['password'])) ? $config['smtp']['password'] : null;
        $this->upload_folder = (isset($config['ftp']['upload_folder'])) ? $config['ftp']['upload_folder'] : null;
        $this->validCredential = (isset($config['validCredential'])) ? $config['validCredential'] : null;
        $this->userftp = (isset($config['ftp']['userftp'])) ? $config['ftp']['userftp'] : null;
        $this->passwordftp = (isset($config['ftp']['passwordftp'])) ? $config['ftp']['passwordftp'] : null;
        $this->host = (isset($config['ftp']['host'])) ? $config['ftp']['host'] : null;
        if ($this->server == null || $this->user == null || $this->password == null || $this->validCredential == null) {
            throw  new \Exception("Setup is not completed!");
        }
    }

    function connect()
    {
        try {
            $this->conn = new Imap($this->server, $this->user, $this->password, $this->encryption);
        } catch (ImapClientException $error) {
            echo $error->getMessage() . PHP_EOL;
            die(); // Oh no :( we failed
        }
    }

    // close the server connection
    function close()
    {
        $this->inbox = array();
        $this->msg_cnt = 0;

        imap_close($this->conn);
    }

    /**
     * @throws \FtpClient\FtpException
     */
    function readUploadFiles()
    {
        //need a cleanup
        //MOVE EMAIL AFTER UPLOAD
        $all_attachments = [];
        foreach ($this->conn->getMessages() as $message) {
            if ($this->getEmail($message->header->from) == $this->validCredential) {
                //MOVE MSGNO
                //print_r($message->header->msgno);
                //$this->conn->moveMessage($message->header->msgno,"EMAILTOFTP");
                foreach ($message->attachments as $attachment) {
                    file_put_contents("tmp/" . $attachment->name, $attachment->body);
                    $all_attachments[] = $attachment->name;
                }
            }
        }
        $this->uploadFiles($all_attachments);
    }

    /**
     * @param $email
     * @return mixed
     */
    function getEmail($email)
    {
        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $email, $matches);
        return $matches[0][0];

    }

    /**
     * @param $attachments_name
     * @throws \FtpClient\FtpException
     */
    function uploadFiles($attachments)
    {
        //Upload file
        $this->setupFtp();
        if (!$this->ftp->isDir($this->upload_folder)) {
            $this->ftp->mkdir($this->upload_folder, true);
        }

        foreach ($attachments as $attachment) {
            $this->ftp->put('tmp/' . $attachment, 'tmp/' . $attachment, true, 1);
            $this->total_files++;
        }
        $this->ftp->close();
    }

    /**
     * @throws \FtpClient\FtpException
     * @throws \Exception
     */
    function setupFtp()
    {
        $this->ftp = new FtpClient();
        $this->ftp->connect($this->host);
        try {
            $this->ftp->login($this->userftp, $this->passwordftp);
        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }

    }

    // move the message to a new folder

    /**
     * @param $msg_index
     * @param string $folder
     */
    function move($msg_index, $folder = 'INBOX.Processed')
    {
        // move on server
        imap_mail_move($this->conn, $msg_index, $folder);
        imap_expunge($this->conn);

        // re-read the inbox
        $this->inbox();
    }

    // get a specific message (1 = first email, 2 = second email, etc.)

    /**
     * @param null $msg_index
     * @return array
     */
    function get($msg_index = null)
    {
        if (count($this->inbox) <= 0) {
            return array();
        } elseif (!is_null($msg_index) && isset($this->inbox[$msg_index])) {
            return $this->inbox[$msg_index];
        }

        return $this->inbox[0];
    }

    // read the inbox

    /**
     *
     */
    function inbox()
    {
        $this->msg_cnt = imap_num_msg($this->conn);

        $in = array();
        for ($i = 1; $i <= $this->msg_cnt; $i++) {
            $in[] = array(
                'index' => $i,
                'header' => imap_headerinfo($this->conn, $i),
                'body' => imap_body($this->conn, $i),
                'structure' => imap_fetchstructure($this->conn, $i)
            );
        }

        $this->inbox = $in;
    }

    /**
     *
     */
    function getEmails()
    {
        $emails = imap_search($this->inbox, 'ALL');
        /* useful only if the above search is set to 'ALL' */
        $max_emails = 16;
        /* if any emails found, iterate through each email */
        if ($emails) {
            $count = 1;
            /* put the newest emails on top */
            rsort($emails);
            /* for every email... */
            foreach ($emails as $email_number) {
                /* get information specific to this email */
                $overview = imap_fetch_overview($this->inbox, $email_number, 0);
                /* get mail message */
                $message = imap_fetchbody($this->inbox, $email_number, 2);
                echo $message;

                if ($count++ >= $max_emails) {
                    break;
                }
            }
        }

    }

    function email_pull()
    {
        // load the Email_reader library from previous post
        $this->load->library('email_reader');

        // load the meals_model to store meal information
        $this->load->model('meals_model');

        // this method is run on a cronjob and should process all emails in the inbox
        while (1) {
            // get an email
            $email = $this->email_reader->get();

            // if there are no emails, jump out
            if (count($email) <= 0) {
                break;
            }

            $attachments = array();
            // check for attachments
            if (isset($email['structure']->parts) && count($email['structure']->parts)) {
                // loop through all attachments
                for ($i = 0; $i < count($email['structure']->parts); $i++) {
                    // set up an empty attachment
                    $attachments[$i] = array(
                        'is_attachment' => false,
                        'filename' => '',
                        'name' => '',
                        'attachment' => ''
                    );

                    // if this attachment has idfparameters, then proceed
                    if ($email['structure']->parts[$i]->ifdparameters) {
                        foreach ($email['structure']->parts[$i]->dparameters as $object) {
                            // if this attachment is a file, mark the attachment and filename
                            if (strtolower($object->attribute) == 'filename') {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['filename'] = $object->value;
                            }
                        }
                    }

                    // if this attachment has ifparameters, then proceed as above
                    if ($email['structure']->parts[$i]->ifparameters) {
                        foreach ($email['structure']->parts[$i]->parameters as $object) {
                            if (strtolower($object->attribute) == 'name') {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['name'] = $object->value;
                            }
                        }
                    }

                    // if we found a valid attachment for this 'part' of the email, process the attachment
                    if ($attachments[$i]['is_attachment']) {
                        // get the content of the attachment
                        $attachments[$i]['attachment'] = imap_fetchbody($this->email_reader->conn, $email['index'],
                            $i + 1);

                        // check if this is base64 encoding
                        if ($email['structure']->parts[$i]->encoding == 3) { // 3 = BASE64
                            $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                        } // otherwise, check if this is "quoted-printable" format
                        elseif ($email['structure']->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                            $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                        }
                    }
                }
            }

            // for My Slow Low, check if I found an image attachment
            $found_img = false;
            foreach ($attachments as $a) {
                if ($a['is_attachment'] == 1) {
                    // get information on the file
                    $finfo = pathinfo($a['filename']);

                    // check if the file is a jpg, png, or gif
                    if (preg_match('/(jpg|gif|png)/i', $finfo['extension'], $n)) {
                        $found_img = true;
                        // process the image (save, resize, crop, etc.)
                        $fname = $this->_process_img($a['attachment'], $n[1]);

                        break;
                    }
                }
            }

            // if there was no image, move the email to the Rejected folder on the server
            if (!$found_img) {
                $this->email_reader->move($email['index'], 'INBOX.Rejected');
                continue;
            }

            // get content from the email that I want to store
            $addr = $email['header']->from[0]->mailbox . "@" . $email['header']->from[0]->host;
            $sender = $email['header']->from[0]->mailbox;
            $text = (!empty($email['header']->subject) ? $email['header']->subject : '');

            // move the email to Processed folder on the server
            $this->email_reader->move($email['index'], 'INBOX.Processed');

            // add the data to the database
            $this->meals_model->add(array(
                'username' => $sender,
                'email' => $addr,
                'photo' => $fname,
                'description' => ($text == '' ? null : $text)
            ));

            // don't slam the server
            sleep(1);
        }

        // close the connection to the IMAP server
        $this->email_reader->close();
    }
}