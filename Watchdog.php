<?php
/**
 * User: gandazgul
 * Date: 01/11/15
 */

require "vendor/autoload.php";

class Watchdog
{
    static function send_mail($to, $subject, $body)
    {
        $mailer = new PHPMailer(true);
        $mailer->IsSMTP();
        $mailer->Host = Settings::$smtp_server; // SMTP server
        //$mailer->SMTPDebug = 2; // enables SMTP debug information (for testing)
        $mailer->SMTPAuth = true; // enable SMTP authentication
        $mailer->Port = Settings::$smtp_port; // set the SMTP port for the GMAIL server
        $mailer->Username = Settings::$smtp_username; // SMTP account username
        $mailer->Password = Settings::$smtp_password; // SMTP account password
        $mailer->SMTPSecure = 'tls';
        $mailer->CharSet = "UTF-8";
        $mailer->Timeout = 60;

        $mailer->setFrom(Settings::$from_email, Settings::$from_name);
        $mailer->addAddress($to);
        $mailer->Subject = $subject;
        $mailer->Body = $body;

        return $mailer->send();
    }

    static function check_service()
    {
        $service = Settings::$service;

        if (file_exists("/tmp/watchdog.lock"))
        {
            echo "Lock file exists. Run with 'reset' if \"{$service}\" is back up.";
            return;
        }

        $cardinals = ['First', 'Second'];

        for ($i = 0; $i < 2; $i++)
        {
            $output = [];
            echo "Checking service status\n";
            exec("systemctl status $service", $output);

            $output = join(' ', $output);

            if (strpos($output, Settings::$expected_response) === false)
            {
                echo "Service is not running. Restarting. {$cardinals[$i]} try.\n";
                exec("systemctl restart $service");
                sleep(30);

                if ($i == 1)//second try
                {
                    echo "Service failed to start, executing hook and sending email\n" . //execute hook
                        Settings::on_failure();

                    //send mail
                    $subject = str_replace('{service}', $service, Settings::$subject);

                    $output = [];
                    exec('tail -100 /var/log/syslog', $output);

                    $message = str_replace('{service}', $service, Settings::$message);
                    $message .= implode("\n ", $output);

                    try
                    {
                        if (static::send_mail(Settings::$to, $subject, $message))
                        {
                            echo "Email sent!\n";
                            touch(Settings::$lock_file);
                        } else
                        {
                            echo "There was an error sending the email\n";
                        }
                    } catch (Exception $e)
                    {
                        echo "ERROR: {$e->getMessage()}\n";
                    }
                }
            }
            else
            {
                echo "OK\n";
                Settings::on_success();
                break;
            }
        }
    }

    static function init($argv)
    {
        if (count($argv) == 1)
        {
            echo <<<HELP
Watchdog, the service watcher

Edit the settings file to tell watchdog what to do. (settings.php)
Run with no arguments to print this help
"php watchdog.php check" will check if your service is running. You might want to use this with a cron.
"php watchdog.php reset" will reset after a failure. We recommend you run this manually.

HELP;
        } else switch ($argv[1])
        {
            case 'check':
                static::check_service();
                break;
            case 'reset':
                unlink(Settings::$lock_file);
                exec("service " . Settings::$service . " start");
                Settings::reset();
                break;
        }
    }
}

Watchdog::init($argv);
